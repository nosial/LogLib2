<?php

    namespace LogLib2\Classes\LogHandlers;

    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;
    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Event;
    use LogLib2\Objects\ExceptionDetails;
    use LogLib2\Objects\StackTrace;

    class TelegramHandler implements LogHandlerInterface
    {
        /**
         * Telegram allows up to 4096 characters per message after entity parsing, a margin is kept because Telegram
         * counts UTF-16 code units while mb_strlen() counts code points.
         */
        private const int MAX_MESSAGE_LENGTH = 4000;
        private const int MAX_EVENT_MESSAGE_LENGTH = 1500;
        private const int MAX_EXCEPTION_MESSAGE_LENGTH = 500;
        private const int MAX_PREVIOUS_EXCEPTIONS = 3;

        private static array $curlHandles = [];
        private static array $failedTargets = [];

        /**
         * Checks if cURL is available and the Telegram configuration has a bot token and chat ID set.
         *
         * @return bool True if the handler can send messages, false otherwise.
         */
        public static function isAvailable(Application $application): bool
        {
            if(!function_exists('curl_init'))
            {
                return false;
            }

            if(!extension_loaded('mbstring'))
            {
                return false;
            }

            $configuration = $application->getTelegramConfiguration();
            if(empty($configuration->getBotToken()) || empty($configuration->getChatId()))
            {
                return false;
            }

            if(!filter_var($configuration->getApiEndpoint(), FILTER_VALIDATE_URL))
            {
                return false;
            }

            return !isset(self::$failedTargets[self::getTargetKey($application)]);
        }

        /**
         * @inheritDoc
         */
        public static function handleEvent(Application $application, Event $event): void
        {
            $configuration = $application->getTelegramConfiguration();
            if(!$configuration->getLogLevel()->levelAllowed($event->getLevel()))
            {
                return;
            }

            $target = self::getTargetKey($application);
            if(isset(self::$failedTargets[$target]))
            {
                return;
            }

            $message = self::formatMessage($application, $event);
            $status = self::sendMessage($application, $message, true);

            // Telegram rejects messages it cannot parse with a 400, retry once as plain text so the event is not lost.
            if($status === 400)
            {
                $status = self::sendMessage($application, self::toPlainText($message), false);
            }

            // Transport failures and client errors (invalid token, chat or topic) will not resolve themselves, rate
            // limits (429) and server errors (5xx) only drop the current message.
            if($status === null || ($status >= 400 && $status < 500 && $status !== 429))
            {
                self::$failedTargets[$target] = true;
            }
        }

        /**
         * Sends a message to the configured chat and topic using the Telegram Bot API.
         *
         * @param Application $application The application's instance.
         * @param string $text The text of the message.
         * @param bool $html True if the text should be parsed as HTML.
         * @return int|null The HTTP status code returned by Telegram, or null if the request failed.
         */
        private static function sendMessage(Application $application, string $text, bool $html): ?int
        {
            $configuration = $application->getTelegramConfiguration();
            $token = $configuration->getBotToken();

            if(!isset(self::$curlHandles[$token]))
            {
                $handle = curl_init();
                if($handle === false)
                {
                    return null;
                }

                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($handle, CURLOPT_CONNECTTIMEOUT_MS, 3000);
                curl_setopt($handle, CURLOPT_TIMEOUT_MS, 5000);
                curl_setopt($handle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                self::$curlHandles[$token] = $handle;
            }

            $payload = [
                'chat_id' => $configuration->getChatId(),
                'text' => $text,
                'disable_notification' => $configuration->isDisableNotification(),
                'link_preview_options' => ['is_disabled' => true],
            ];

            if($configuration->getTopicId() !== null)
            {
                $payload['message_thread_id'] = $configuration->getTopicId();
            }

            if($html)
            {
                $payload['parse_mode'] = 'HTML';
            }

            $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if($body === false)
            {
                return null;
            }

            $handle = self::$curlHandles[$token];
            curl_setopt($handle, CURLOPT_URL, sprintf('%s/bot%s/sendMessage', $configuration->getApiEndpoint(), $token));
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);

            if(@curl_exec($handle) === false)
            {
                unset(self::$curlHandles[$token]);
                return null;
            }

            return (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        }

        /**
         * Formats the event as a Telegram HTML message, stack frames are dropped until the message fits within
         * Telegram's message length limit.
         *
         * @param Application $application The application's instance.
         * @param Event $event The event to format.
         * @return string The formatted HTML message.
         */
        private static function formatMessage(Application $application, Event $event): string
        {
            $maxFrames = PHP_INT_MAX;
            $message = self::renderMessage($application, $event, $maxFrames, self::MAX_EVENT_MESSAGE_LENGTH);

            while(self::visibleLength($message) > self::MAX_MESSAGE_LENGTH && $maxFrames > 0)
            {
                $maxFrames = $maxFrames === PHP_INT_MAX ? 20 : intdiv($maxFrames, 2);
                $message = self::renderMessage($application, $event, $maxFrames, self::MAX_EVENT_MESSAGE_LENGTH);
            }

            if(self::visibleLength($message) > self::MAX_MESSAGE_LENGTH)
            {
                $message = self::renderMessage($application, $event, 0, 200);
            }

            return $message;
        }

        /**
         * Renders the event as a Telegram HTML message.
         *
         * @param Application $application The application's instance.
         * @param Event $event The event to render.
         * @param int $maxFrames The maximum number of stack frames to render per exception.
         * @param int $maxMessageLength The maximum length of the event message.
         * @return string The rendered HTML message.
         */
        private static function renderMessage(Application $application, Event $event, int $maxFrames, int $maxMessageLength): string
        {
            $configuration = $application->getTelegramConfiguration();
            $traceFormat = $configuration->getTraceFormat();
            $timestampFormat = $configuration->getTimestampFormat();

            // Hashtags
            $tags = ['#' . $event->getLevel()->value];
            $applicationTag = self::toHashtag($event->getApplicationName());
            if($applicationTag !== null)
            {
                $tags[] = $applicationTag;
            }

            if($event->getException() !== null)
            {
                $exceptionTag = self::toHashtag(self::shortClassName($event->getException()->getName()));
                if($exceptionTag !== null && !in_array($exceptionTag, $tags, true))
                {
                    $tags[] = $exceptionTag;
                }
            }

            $lines = [implode(' ', $tags)];

            // Header
            $lines[] = sprintf('<b>%s</b> in <b>%s</b>', self::getLevelName($event->getLevel()), self::escape($event->getApplicationName()));

            if($timestampFormat !== TimestampFormat::NONE)
            {
                $lines[] = sprintf('<b>Time:</b> <tg-time unix="%d" format="wDT">%s</tg-time>',
                    $event->getTimestamp(), self::escape($timestampFormat->format($event->getTimestamp()))
                );
            }

            if($traceFormat !== TraceFormat::NONE && $event->getFirstTrace() !== null)
            {
                $location = $traceFormat->format($event->getFirstTrace());
                if($location !== '')
                {
                    $lines[] = sprintf('<b>Location:</b> <code>%s</code>', self::escape($location));
                }
            }

            // Message
            $lines[] = '';
            $lines[] = sprintf('<blockquote>%s</blockquote>', self::escape(self::truncate($event->getMessage(), $maxMessageLength)));

            // Exception details, including the chain of previous exceptions
            $exception = $event->getException();
            $depth = 0;
            while($exception !== null && $depth <= self::MAX_PREVIOUS_EXCEPTIONS)
            {
                $lines[] = '';
                $lines[] = self::renderException($exception, $depth === 0 ? 'Exception' : 'Caused by', $traceFormat, $maxFrames);
                $exception = $exception->getPrevious();
                $depth++;
            }

            if($exception !== null)
            {
                $lines[] = '<i>Further previous exceptions omitted</i>';
            }

            return implode("\n", $lines);
        }

        /**
         * Renders the details of an exception as Telegram HTML.
         *
         * @param ExceptionDetails $exception The exception to render.
         * @param string $label The label of the exception section.
         * @param TraceFormat $traceFormat The trace format to use for stack frames.
         * @param int $maxFrames The maximum number of stack frames to render.
         * @return string The rendered HTML.
         */
        private static function renderException(ExceptionDetails $exception, string $label, TraceFormat $traceFormat, int $maxFrames): string
        {
            $header = sprintf('<b>%s:</b> <code>%s</code>', $label, self::escape($exception->getName()));
            if($exception->getCode() !== null && $exception->getCode() !== 0)
            {
                $header .= sprintf(' (code <code>%d</code>)', $exception->getCode());
            }

            $lines = [$header];

            if($exception->getFile() !== null)
            {
                $file = $exception->getFile();
                if($exception->getLine() !== null)
                {
                    $file .= ':' . $exception->getLine();
                }

                $lines[] = sprintf('<b>Thrown at:</b> <code>%s</code>', self::escape($file));
            }

            if($exception->getMessage() !== '')
            {
                $lines[] = sprintf('<blockquote>%s</blockquote>', self::escape(self::truncate($exception->getMessage(), self::MAX_EXCEPTION_MESSAGE_LENGTH)));
            }

            $frames = array_values(array_filter($exception->getTrace() ?? [], fn(StackTrace $trace) => !$trace->isEmpty()));
            if($traceFormat !== TraceFormat::NONE && $maxFrames > 0 && count($frames) > 0)
            {
                $rendered = [];
                foreach(array_slice($frames, 0, $maxFrames) as $index => $frame)
                {
                    $rendered[] = sprintf('#%d %s', $index, self::escape($traceFormat->format($frame)));
                }

                if(count($frames) > $maxFrames)
                {
                    $rendered[] = sprintf('... %d more frame(s)', count($frames) - $maxFrames);
                }

                $lines[] = '<b>Stack Trace:</b>';
                $lines[] = sprintf('<blockquote expandable>%s</blockquote>', implode("\n", $rendered));
            }

            return implode("\n", $lines);
        }

        /**
         * Returns a human-readable name for the given log level.
         *
         * @param LogLevel $level The log level.
         * @return string The name of the log level.
         */
        private static function getLevelName(LogLevel $level): string
        {
            return match($level)
            {
                LogLevel::DEBUG => 'Debug',
                LogLevel::VERBOSE => 'Verbose',
                LogLevel::INFO => 'Information',
                LogLevel::WARNING => 'Warning',
                LogLevel::ERROR => 'Error',
                LogLevel::CRITICAL => 'Critical',
            };
        }

        /**
         * Converts the input into a Telegram hashtag, Telegram only recognizes letters, digits and underscores as part
         * of a hashtag so all other characters are replaced with underscores (e.g. com.example.app => #com_example_app)
         *
         * @param string $input The input to convert.
         * @return string|null The hashtag, or null if the input cannot be represented as a hashtag.
         */
        private static function toHashtag(string $input): ?string
        {
            $tag = trim((string)preg_replace('/[^\p{L}\p{N}_]+/u', '_', $input), '_');

            // Telegram does not recognize hashtags consisting only of digits.
            if($tag === '' || ctype_digit($tag))
            {
                return null;
            }

            return '#' . strtoupper($tag);
        }

        /**
         * Returns the class name without its namespace.
         *
         * @param string $className The fully qualified class name.
         * @return string The short class name.
         */
        private static function shortClassName(string $className): string
        {
            $position = strrpos($className, '\\');
            return $position === false ? $className : substr($className, $position + 1);
        }

        /**
         * Escapes text for use in a Telegram HTML message.
         *
         * @param string $text The text to escape.
         * @return string The escaped text.
         */
        private static function escape(string $text): string
        {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        }

        /**
         * Truncates text to the given number of characters.
         *
         * @param string $text The text to truncate.
         * @param int $length The maximum number of characters.
         * @return string The truncated text.
         */
        private static function truncate(string $text, int $length): string
        {
            if(mb_strlen($text) <= $length)
            {
                return $text;
            }

            return mb_substr($text, 0, max(0, $length - 3)) . '...';
        }

        /**
         * Converts a Telegram HTML message into plain text.
         *
         * @param string $html The HTML message.
         * @return string The plain text message.
         */
        private static function toPlainText(string $html): string
        {
            return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        /**
         * Returns the number of characters Telegram would count after parsing the HTML message.
         *
         * @param string $html The HTML message.
         * @return int The visible length of the message.
         */
        private static function visibleLength(string $html): int
        {
            return mb_strlen(self::toPlainText($html));
        }

        /**
         * Returns a key that uniquely identifies the bot, chat and topic messages are sent to.
         *
         * @param Application $application The application's instance.
         * @return string The target key.
         */
        private static function getTargetKey(Application $application): string
        {
            $configuration = $application->getTelegramConfiguration();
            return sprintf('%s|%s|%s', $configuration->getBotToken(), $configuration->getChatId(), $configuration->getTopicId() ?? '');
        }
    }
