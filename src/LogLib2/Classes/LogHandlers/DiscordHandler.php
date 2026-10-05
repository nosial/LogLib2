<?php

    namespace LogLib2\Classes\LogHandlers;

    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\TraceFormat;
    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Event;
    use LogLib2\Objects\ExceptionDetails;
    use LogLib2\Objects\StackTrace;

    class DiscordHandler implements LogHandlerInterface
    {
        /**
         * Discord limits embeds to 6000 characters in total, with further limits on the individual parts.
         */
        private const int MAX_EMBED_LENGTH = 5900;
        private const int MAX_TITLE_LENGTH = 256;
        private const int MAX_FIELD_VALUE_LENGTH = 1024;
        private const int MAX_CONTENT_LENGTH = 2000;
        private const int MAX_EVENT_MESSAGE_LENGTH = 1500;
        private const int MAX_PREVIOUS_EXCEPTIONS = 3;

        private static array $curlHandles = [];
        private static array $failedWebhooks = [];

        /**
         * Checks if cURL is available and the Discord configuration has a valid webhook URL set.
         *
         * @return bool True if the handler can send messages, false otherwise.
         */
        public static function isAvailable(Application $application): bool
        {
            if(!function_exists('curl_init'))
            {
                return false;
            }

            $webhookUrl = $application->getDiscordConfiguration()->getWebhookUrl();
            if($webhookUrl === null || !filter_var($webhookUrl, FILTER_VALIDATE_URL))
            {
                return false;
            }

            return !isset(self::$failedWebhooks[self::getTargetUrl($application)]);
        }

        /**
         * @inheritDoc
         */
        public static function handleEvent(Application $application, Event $event): void
        {
            $configuration = $application->getDiscordConfiguration();
            if(!$configuration->getLogLevel()->levelAllowed($event->getLevel()))
            {
                return;
            }

            $target = self::getTargetUrl($application);
            if(isset(self::$failedWebhooks[$target]))
            {
                return;
            }

            $status = self::execute($application, ['embeds' => [self::buildEmbed($application, $event)]]);

            // Discord rejects payloads it considers invalid with a 400, retry once as plain content so the event is
            // not lost.
            if($status === 400)
            {
                $status = self::execute($application, ['content' => self::buildContent($event)]);
            }

            // Transport failures and client errors (deleted webhook, invalid token or thread) will not resolve
            // themselves, rate limits (429) and server errors (5xx) only drop the current message.
            if($status === null || ($status >= 400 && $status < 500 && $status !== 429))
            {
                self::$failedWebhooks[$target] = true;
            }
        }

        /**
         * Executes the webhook with the given payload.
         *
         * @param Application $application The application's instance.
         * @param array $payload The message payload.
         * @return int|null The HTTP status code returned by Discord, or null if the request failed.
         */
        private static function execute(Application $application, array $payload): ?int
        {
            $configuration = $application->getDiscordConfiguration();
            $url = self::getTargetUrl($application);

            if(!empty($configuration->getUsername()))
            {
                $payload['username'] = self::truncate($configuration->getUsername(), 80);
            }

            if(!empty($configuration->getAvatarUrl()))
            {
                $payload['avatar_url'] = $configuration->getAvatarUrl();
            }

            // Log messages may contain user input, never let them ping @everyone, roles or users.
            $payload['allowed_mentions'] = ['parse' => []];

            $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if($body === false)
            {
                return null;
            }

            if(!isset(self::$curlHandles[$url]))
            {
                $handle = curl_init($url);
                if($handle === false)
                {
                    return null;
                }

                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($handle, CURLOPT_CONNECTTIMEOUT_MS, 3000);
                curl_setopt($handle, CURLOPT_TIMEOUT_MS, 5000);
                curl_setopt($handle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                self::$curlHandles[$url] = $handle;
            }

            $handle = self::$curlHandles[$url];
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);

            if(@curl_exec($handle) === false)
            {
                unset(self::$curlHandles[$url]);
                return null;
            }

            return (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        }

        /**
         * Builds the embed for the event, stack traces are dropped (last exception first) and the message is
         * shortened until the embed fits within Discord's limits.
         *
         * @param Application $application The application's instance.
         * @param Event $event The event to build the embed for.
         * @return array The embed.
         */
        private static function buildEmbed(Application $application, Event $event): array
        {
            $configuration = $application->getDiscordConfiguration();
            $traceFormat = $configuration->getTraceFormat();

            $embed = [
                'title' => self::truncate(sprintf('%s in %s', self::getLevelName($event->getLevel()), $event->getApplicationName()), self::MAX_TITLE_LENGTH),
                'description' => self::codeBlock(self::truncate($event->getMessage(), self::MAX_EVENT_MESSAGE_LENGTH)),
                'color' => self::getLevelColor($event->getLevel()),
                'fields' => [
                    ['name' => 'Level', 'value' => self::inlineCode($event->getLevel()->value), 'inline' => true],
                    ['name' => 'Application', 'value' => self::inlineCode(self::truncate($event->getApplicationName(), 200)), 'inline' => true],
                    ['name' => 'Host', 'value' => self::inlineCode(self::truncate(gethostname() ?: 'unknown', 200)), 'inline' => true],
                ],
                'footer' => ['text' => 'LogLib2'],
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z', $event->getTimestamp()),
            ];

            if($traceFormat !== TraceFormat::NONE && $event->getFirstTrace() !== null)
            {
                $location = $traceFormat->format($event->getFirstTrace());
                if($location !== '')
                {
                    $embed['fields'][] = ['name' => 'Location', 'value' => self::inlineCode(self::truncate($location, self::MAX_FIELD_VALUE_LENGTH - 2)), 'inline' => false];
                }
            }

            // Exception details, including the chain of previous exceptions
            $exception = $event->getException();
            $depth = 0;
            while($exception !== null && $depth <= self::MAX_PREVIOUS_EXCEPTIONS)
            {
                $embed['fields'][] = ['name' => $depth === 0 ? 'Exception' : 'Caused by', 'value' => self::renderException($exception), 'inline' => false];

                $trace = self::renderTrace($exception, $traceFormat);
                if($trace !== null)
                {
                    $embed['fields'][] = ['name' => 'Stack Trace', 'value' => $trace, 'inline' => false];
                }

                $exception = $exception->getPrevious();
                $depth++;
            }

            if($exception !== null)
            {
                $embed['fields'][] = ['name' => 'Caused by', 'value' => '*Further previous exceptions omitted*', 'inline' => false];
            }

            // Drop stack traces starting from the deepest exception until the embed fits.
            for($index = count($embed['fields']) - 1; $index >= 0 && self::embedLength($embed) > self::MAX_EMBED_LENGTH; $index--)
            {
                if($embed['fields'][$index]['name'] === 'Stack Trace')
                {
                    array_splice($embed['fields'], $index, 1);
                }
            }

            if(self::embedLength($embed) > self::MAX_EMBED_LENGTH)
            {
                $embed['description'] = self::codeBlock(self::truncate($event->getMessage(), 200));
            }

            return $embed;
        }

        /**
         * Renders the name, code, location and message of an exception as an embed field value.
         *
         * @param ExceptionDetails $exception The exception to render.
         * @return string The field value.
         */
        private static function renderException(ExceptionDetails $exception): string
        {
            $value = self::inlineCode(self::truncate($exception->getName(), 200));
            if($exception->getCode() !== null && $exception->getCode() !== 0)
            {
                $value .= sprintf(' (code %s)', self::inlineCode((string)$exception->getCode()));
            }

            if($exception->getFile() !== null)
            {
                $file = $exception->getFile() . ($exception->getLine() !== null ? ':' . $exception->getLine() : '');
                $value .= "\nThrown at " . self::inlineCode(self::truncate($file, 300));
            }

            if($exception->getMessage() !== '')
            {
                $remaining = self::MAX_FIELD_VALUE_LENGTH - mb_strlen($value) - 10;
                $value .= "\n" . self::codeBlock(self::truncate($exception->getMessage(), $remaining));
            }

            return $value;
        }

        /**
         * Renders the stack trace of an exception as an embed field value, frames are omitted once the field value
         * limit is reached.
         *
         * @param ExceptionDetails $exception The exception to render the stack trace of.
         * @param TraceFormat $traceFormat The trace format to use.
         * @return string|null The field value, or null if there is no stack trace to render.
         */
        private static function renderTrace(ExceptionDetails $exception, TraceFormat $traceFormat): ?string
        {
            if($traceFormat === TraceFormat::NONE)
            {
                return null;
            }

            $frames = array_values(array_filter($exception->getTrace() ?? [], fn(StackTrace $trace) => !$trace->isEmpty()));
            if(count($frames) === 0)
            {
                return null;
            }

            // Keep room for the code block fences and the omitted frames line.
            $budget = self::MAX_FIELD_VALUE_LENGTH - 40;
            $lines = [];
            $length = 0;

            foreach($frames as $index => $frame)
            {
                $line = self::truncate(sprintf('#%d %s', $index, $traceFormat->format($frame)), 300);
                if($length + mb_strlen($line) + 1 > $budget)
                {
                    break;
                }

                $lines[] = $line;
                $length += mb_strlen($line) + 1;
            }

            if(count($lines) < count($frames))
            {
                $lines[] = sprintf('... %d more frame(s)', count($frames) - count($lines));
            }

            return self::codeBlock(implode("\n", $lines));
        }

        /**
         * Builds a plain text message for the event, used when Discord rejects the embed.
         *
         * @param Event $event The event to build the message for.
         * @return string The message content.
         */
        private static function buildContent(Event $event): string
        {
            $header = sprintf("**%s** in %s\n", self::getLevelName($event->getLevel()), self::inlineCode(self::truncate($event->getApplicationName(), 200)));
            $body = $event->getMessage();

            if($event->getException() !== null)
            {
                $body .= "\n\n" . $event->getException()->getName() . ': ' . $event->getException()->getMessage();
            }

            return $header . self::codeBlock(self::truncate($body, self::MAX_CONTENT_LENGTH - mb_strlen($header) - 10));
        }

        /**
         * Returns the number of characters Discord counts towards the embed limit.
         *
         * @param array $embed The embed.
         * @return int The length of the embed.
         */
        private static function embedLength(array $embed): int
        {
            $length = mb_strlen($embed['title']) + mb_strlen($embed['description']) + mb_strlen($embed['footer']['text']);
            foreach($embed['fields'] as $field)
            {
                $length += mb_strlen($field['name']) + mb_strlen($field['value']);
            }

            return $length;
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
         * Returns the embed color used for the given log level.
         *
         * @param LogLevel $level The log level.
         * @return int The color as an integer.
         */
        private static function getLevelColor(LogLevel $level): int
        {
            return match($level)
            {
                LogLevel::DEBUG, LogLevel::VERBOSE => 0x71717A,
                LogLevel::INFO => 0x2563EB,
                LogLevel::WARNING => 0xD97706,
                LogLevel::ERROR => 0xDC2626,
                LogLevel::CRITICAL => 0x991B1B,
            };
        }

        /**
         * Wraps text in an inline code span, backticks within the text are replaced so they cannot end the span.
         *
         * @param string $text The text to wrap.
         * @return string The inline code span.
         */
        private static function inlineCode(string $text): string
        {
            return '`' . str_replace('`', "\u{02CB}", $text) . '`';
        }

        /**
         * Wraps text in a code block, triple backticks within the text are broken up so they cannot end the block.
         *
         * @param string $text The text to wrap.
         * @return string The code block.
         */
        private static function codeBlock(string $text): string
        {
            if($text === '')
            {
                $text = ' ';
            }

            return "```\n" . str_replace('```', "`\u{200B}``", $text) . "\n```";
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
         * Returns the webhook URL including the thread ID query parameter if a thread is configured.
         *
         * @param Application $application The application's instance.
         * @return string The URL to execute the webhook with.
         */
        private static function getTargetUrl(Application $application): string
        {
            $configuration = $application->getDiscordConfiguration();
            $url = (string)$configuration->getWebhookUrl();

            if(!empty($configuration->getThreadId()))
            {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'thread_id=' . rawurlencode($configuration->getThreadId());
            }

            return $url;
        }
    }
