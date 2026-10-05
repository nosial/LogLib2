<?php

    namespace LogLib2\Classes\LogHandlers;

    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\SmtpEncryption;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;
    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Configurations\EmailConfiguration;
    use LogLib2\Objects\Event;
    use LogLib2\Objects\ExceptionDetails;
    use LogLib2\Objects\StackTrace;

    class EmailHandler implements LogHandlerInterface
    {
        private const int MAX_SUBJECT_MESSAGE_LENGTH = 100;
        private const int MAX_FRAMES = 100;
        private const int MAX_PREVIOUS_EXCEPTIONS = 5;

        private static array $failedTargets = [];
        private static bool $sending = false;
        private static ?int $lastReplyCode = null;

        /**
         * Checks if the email configuration has a valid sender, at least one valid recipient and an SMTP host set.
         *
         * @return bool True if the handler can send emails, false otherwise.
         */
        public static function isAvailable(Application $application): bool
        {
            $configuration = $application->getEmailConfiguration();
            if(empty($configuration->getHost()) || $configuration->getPort() <= 0)
            {
                return false;
            }

            if($configuration->getFromAddress() === null || !filter_var($configuration->getFromAddress(), FILTER_VALIDATE_EMAIL))
            {
                return false;
            }

            if(count(self::getValidRecipients($configuration)) === 0)
            {
                return false;
            }

            if($configuration->getEncryption() !== SmtpEncryption::NONE && !extension_loaded('openssl'))
            {
                return false;
            }

            return !isset(self::$failedTargets[self::getTargetKey($configuration)]);
        }

        /**
         * @inheritDoc
         */
        public static function handleEvent(Application $application, Event $event): void
        {
            $configuration = $application->getEmailConfiguration();
            if(!$configuration->getLogLevel()->levelAllowed($event->getLevel()))
            {
                return;
            }

            // Errors raised while sending could be logged again by the runtime error handler, avoid recursing into
            // another email while one is already being sent.
            if(self::$sending)
            {
                return;
            }

            $target = self::getTargetKey($configuration);
            if(isset(self::$failedTargets[$target]))
            {
                return;
            }

            self::$sending = true;
            try
            {
                $sent = self::sendMail($configuration, self::buildMessage($configuration, $event));
            }
            finally
            {
                self::$sending = false;
            }

            // Connection, TLS and authentication failures or permanent rejections (5xx) will not resolve themselves,
            // the handler is disabled to avoid blocking every log call on the timeout. Temporary rejections (4xx) only
            // drop the current email.
            if(!$sent && (self::$lastReplyCode === null || self::$lastReplyCode >= 500))
            {
                self::$failedTargets[$target] = true;
            }
        }

        /**
         * Sends the message to the configured recipients using the configured SMTP server.
         *
         * @param EmailConfiguration $configuration The email configuration.
         * @param string $message The full MIME message including headers.
         * @return bool True if the message was accepted by the server, false otherwise.
         */
        private static function sendMail(EmailConfiguration $configuration, string $message): bool
        {
            self::$lastReplyCode = null;

            $context = stream_context_create(['ssl' => [
                'peer_name' => $configuration->getHost(),
                'verify_peer' => $configuration->isVerifyPeer(),
                'verify_peer_name' => $configuration->isVerifyPeer(),
                'allow_self_signed' => !$configuration->isVerifyPeer(),
            ]]);

            $scheme = $configuration->getEncryption() === SmtpEncryption::TLS ? 'tls' : 'tcp';
            $socket = @stream_socket_client(
                sprintf('%s://%s:%d', $scheme, $configuration->getHost(), $configuration->getPort()),
                $errorCode, $errorMessage, $configuration->getTimeout(), STREAM_CLIENT_CONNECT, $context
            );

            if($socket === false)
            {
                return false;
            }

            stream_set_timeout($socket, $configuration->getTimeout());

            try
            {
                if(!self::expect($socket, [220]))
                {
                    return false;
                }

                $capabilities = self::hello($socket);
                if($capabilities === null)
                {
                    return false;
                }

                if($configuration->getEncryption() === SmtpEncryption::STARTTLS)
                {
                    if(!isset($capabilities['STARTTLS']) || !self::command($socket, 'STARTTLS', [220]))
                    {
                        self::$lastReplyCode = null;
                        return false;
                    }

                    if(!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT))
                    {
                        self::$lastReplyCode = null;
                        return false;
                    }

                    // The server's capabilities must be discarded and requested again after the TLS handshake.
                    $capabilities = self::hello($socket);
                    if($capabilities === null)
                    {
                        return false;
                    }
                }

                if(!empty($configuration->getUsername()) && !self::authenticate($socket, $configuration, $capabilities))
                {
                    return false;
                }

                if(!self::command($socket, sprintf('MAIL FROM:<%s>', $configuration->getFromAddress()), [250]))
                {
                    return false;
                }

                foreach(self::getValidRecipients($configuration) as $recipient)
                {
                    if(!self::command($socket, sprintf('RCPT TO:<%s>', $recipient), [250, 251]))
                    {
                        return false;
                    }
                }

                if(!self::command($socket, 'DATA', [354]))
                {
                    return false;
                }

                // Lines beginning with a dot must be escaped, a line containing only a dot ends the message.
                $data = preg_replace('/^\./m', '..', $message) . "\r\n.";
                if(!self::command($socket, $data, [250]))
                {
                    return false;
                }

                self::command($socket, 'QUIT', [221]);
                return true;
            }
            finally
            {
                @fclose($socket);
            }
        }

        /**
         * Sends the EHLO command and parses the capabilities advertised by the server.
         *
         * @param resource $socket The SMTP connection.
         * @return array|null The capabilities keyed by their uppercase name, or null if the command failed.
         */
        private static function hello($socket): ?array
        {
            $hostname = gethostname() ?: 'localhost';
            if(!self::write($socket, sprintf('EHLO %s', $hostname)))
            {
                return null;
            }

            $lines = self::read($socket);
            if($lines === null || self::$lastReplyCode !== 250)
            {
                return null;
            }

            $capabilities = [];
            foreach(array_slice($lines, 1) as $line)
            {
                $parts = preg_split('/\s+/', strtoupper(trim(substr($line, 4))));
                $capabilities[array_shift($parts)] = $parts;
            }

            return $capabilities;
        }

        /**
         * Authenticates with the SMTP server using AUTH PLAIN or AUTH LOGIN.
         *
         * @param resource $socket The SMTP connection.
         * @param EmailConfiguration $configuration The email configuration.
         * @param array $capabilities The capabilities advertised by the server.
         * @return bool True if authentication succeeded, false otherwise.
         */
        private static function authenticate($socket, EmailConfiguration $configuration, array $capabilities): bool
        {
            $mechanisms = $capabilities['AUTH'] ?? ['PLAIN', 'LOGIN'];
            $username = (string)$configuration->getUsername();
            $password = (string)$configuration->getPassword();

            if(in_array('PLAIN', $mechanisms, true))
            {
                return self::command($socket, 'AUTH PLAIN ' . base64_encode("\0" . $username . "\0" . $password), [235]);
            }

            if(in_array('LOGIN', $mechanisms, true))
            {
                return self::command($socket, 'AUTH LOGIN', [334])
                    && self::command($socket, base64_encode($username), [334])
                    && self::command($socket, base64_encode($password), [235]);
            }

            // No supported authentication mechanism, treat it as a permanent failure.
            self::$lastReplyCode = 504;
            return false;
        }

        /**
         * Sends a command and checks that the server replied with one of the expected codes.
         *
         * @param resource $socket The SMTP connection.
         * @param string $command The command to send.
         * @param int[] $codes The expected reply codes.
         * @return bool True if the server replied with an expected code, false otherwise.
         */
        private static function command($socket, string $command, array $codes): bool
        {
            return self::write($socket, $command) && self::expect($socket, $codes);
        }

        /**
         * Reads a reply and checks that the server replied with one of the expected codes.
         *
         * @param resource $socket The SMTP connection.
         * @param int[] $codes The expected reply codes.
         * @return bool True if the server replied with an expected code, false otherwise.
         */
        private static function expect($socket, array $codes): bool
        {
            return self::read($socket) !== null && in_array(self::$lastReplyCode, $codes, true);
        }

        /**
         * Writes a line to the SMTP connection.
         *
         * @param resource $socket The SMTP connection.
         * @param string $line The line to write, without the trailing CRLF.
         * @return bool True if the line was written, false otherwise.
         */
        private static function write($socket, string $line): bool
        {
            $data = $line . "\r\n";
            $length = strlen($data);

            for($written = 0; $written < $length; $written += $result)
            {
                $result = @fwrite($socket, substr($data, $written));
                if($result === false || $result === 0)
                {
                    self::$lastReplyCode = null;
                    return false;
                }
            }

            return true;
        }

        /**
         * Reads a (possibly multi-line) reply from the SMTP connection and stores its reply code.
         *
         * @param resource $socket The SMTP connection.
         * @return string[]|null The lines of the reply, or null if the connection failed or timed out.
         */
        private static function read($socket): ?array
        {
            $lines = [];
            while(($line = @fgets($socket, 4096)) !== false)
            {
                $lines[] = rtrim($line, "\r\n");

                // The last line of a reply has a space after the code, continuation lines have a hyphen.
                if(strlen($line) < 4 || $line[3] === ' ')
                {
                    self::$lastReplyCode = (int)substr($line, 0, 3);
                    return $lines;
                }
            }

            self::$lastReplyCode = null;
            return null;
        }

        /**
         * Builds the full MIME message for the event, containing both a plain text and an HTML version.
         *
         * @param EmailConfiguration $configuration The email configuration.
         * @param Event $event The event to build the message for.
         * @return string The MIME message, including headers.
         */
        private static function buildMessage(EmailConfiguration $configuration, Event $event): string
        {
            $boundary = 'loglib2-' . bin2hex(random_bytes(12));
            $fromAddress = (string)$configuration->getFromAddress();
            $domain = substr($fromAddress, strrpos($fromAddress, '@') + 1);

            $from = $fromAddress;
            if(!empty($configuration->getFromName()))
            {
                $from = sprintf('%s <%s>', self::encodeHeader($configuration->getFromName()), $fromAddress);
            }

            $headers = [
                'Date: ' . date('r', $event->getTimestamp()),
                'From: ' . $from,
                'To: ' . implode(', ', self::getValidRecipients($configuration)),
                'Subject: ' . self::encodeHeader(self::buildSubject($event)),
                sprintf('Message-ID: <%s@%s>', bin2hex(random_bytes(16)), $domain),
                'MIME-Version: 1.0',
                'X-Mailer: LogLib2',
                'X-LogLib-Level: ' . $event->getLevel()->value,
                'X-LogLib-Application: ' . self::encodeHeader($event->getApplicationName()),
                sprintf('Content-Type: multipart/alternative; boundary="%s"', $boundary),
            ];

            $parts = [
                'text/plain' => self::renderText($configuration, $event),
                'text/html' => self::renderHtml($configuration, $event),
            ];

            $message = implode("\r\n", $headers) . "\r\n\r\n";
            foreach($parts as $contentType => $content)
            {
                $message .= sprintf("--%s\r\nContent-Type: %s; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n", $boundary, $contentType);
                $message .= rtrim(chunk_split(base64_encode($content), 76, "\r\n")) . "\r\n";
            }

            return $message . sprintf('--%s--', $boundary);
        }

        /**
         * Builds the subject line of the email, for example "[ERR] com.example.app: Failed to process the order"
         *
         * @param Event $event The event to build the subject for.
         * @return string The subject line.
         */
        private static function buildSubject(Event $event): string
        {
            $message = trim(strtok($event->getMessage(), "\r\n") ?: '');
            if($message === '' && $event->getException() !== null)
            {
                $message = $event->getException()->getName();
            }

            return sprintf('[%s] %s: %s', $event->getLevel()->value, $event->getApplicationName(), self::truncate($message));
        }

        /**
         * Returns the details of the event as label/value pairs, shared between the plain text and HTML versions.
         *
         * @param EmailConfiguration $configuration The email configuration.
         * @param Event $event The event.
         * @return array<string, string> The details keyed by their label.
         */
        private static function getDetails(EmailConfiguration $configuration, Event $event): array
        {
            $details = [
                'Level' => sprintf('%s (%s)', self::getLevelName($event->getLevel()), $event->getLevel()->value),
                'Application' => $event->getApplicationName(),
            ];

            if($configuration->getTimestampFormat() !== TimestampFormat::NONE)
            {
                $details['Time'] = $configuration->getTimestampFormat()->format($event->getTimestamp()) . ' ' . date('T', $event->getTimestamp());
            }

            $details['Host'] = gethostname() ?: 'unknown';

            if($configuration->getTraceFormat() !== TraceFormat::NONE && $event->getFirstTrace() !== null)
            {
                $location = $configuration->getTraceFormat()->format($event->getFirstTrace());
                if($location !== '')
                {
                    $details['Location'] = $location;
                }
            }

            return $details;
        }

        /**
         * Returns the exception of the event followed by its previous exceptions.
         *
         * @param Event $event The event.
         * @return ExceptionDetails[] The exception chain, limited to MAX_PREVIOUS_EXCEPTIONS previous exceptions.
         */
        private static function getExceptionChain(Event $event): array
        {
            $chain = [];
            $exception = $event->getException();
            while($exception !== null && count($chain) <= self::MAX_PREVIOUS_EXCEPTIONS)
            {
                $chain[] = $exception;
                $exception = $exception->getPrevious();
            }

            return $chain;
        }

        /**
         * Returns the formatted stack frames of an exception.
         *
         * @param ExceptionDetails $exception The exception.
         * @param TraceFormat $traceFormat The trace format to use.
         * @return string[] The formatted stack frames.
         */
        private static function getFrames(ExceptionDetails $exception, TraceFormat $traceFormat): array
        {
            if($traceFormat === TraceFormat::NONE)
            {
                return [];
            }

            $frames = array_values(array_filter($exception->getTrace() ?? [], fn(StackTrace $trace) => !$trace->isEmpty()));
            $rendered = [];
            foreach(array_slice($frames, 0, self::MAX_FRAMES) as $index => $frame)
            {
                $rendered[] = sprintf('#%d %s', $index, $traceFormat->format($frame));
            }

            if(count($frames) > self::MAX_FRAMES)
            {
                $rendered[] = sprintf('... %d more frame(s)', count($frames) - self::MAX_FRAMES);
            }

            return $rendered;
        }

        /**
         * Returns the file and line an exception was thrown at.
         *
         * @param ExceptionDetails $exception The exception.
         * @return string|null The location, or null if unknown.
         */
        private static function getThrownAt(ExceptionDetails $exception): ?string
        {
            if($exception->getFile() === null)
            {
                return null;
            }

            return $exception->getFile() . ($exception->getLine() !== null ? ':' . $exception->getLine() : '');
        }

        /**
         * Renders the plain text version of the email.
         *
         * @param EmailConfiguration $configuration The email configuration.
         * @param Event $event The event to render.
         * @return string The plain text body.
         */
        private static function renderText(EmailConfiguration $configuration, Event $event): string
        {
            $lines = [];
            foreach(self::getDetails($configuration, $event) as $label => $value)
            {
                $lines[] = sprintf('%-12s %s', $label . ':', $value);
            }

            $lines[] = '';
            $lines[] = 'Message:';
            $lines[] = $event->getMessage();

            foreach(self::getExceptionChain($event) as $index => $exception)
            {
                $lines[] = '';
                $lines[] = sprintf('%s: %s%s', $index === 0 ? 'Exception' : 'Caused by', $exception->getName(),
                    ($exception->getCode() !== null && $exception->getCode() !== 0) ? sprintf(' (code %d)', $exception->getCode()) : ''
                );

                if(($thrownAt = self::getThrownAt($exception)) !== null)
                {
                    $lines[] = 'Thrown at: ' . $thrownAt;
                }

                if($exception->getMessage() !== '')
                {
                    $lines[] = $exception->getMessage();
                }

                $frames = self::getFrames($exception, $configuration->getTraceFormat());
                if(count($frames) > 0)
                {
                    $lines[] = 'Stack Trace:';
                    foreach($frames as $frame)
                    {
                        $lines[] = '  ' . $frame;
                    }
                }
            }

            $lines[] = '';
            $lines[] = '-- ';
            $lines[] = 'Sent by LogLib2';

            return implode("\r\n", $lines);
        }

        /**
         * Renders the HTML version of the email, styles are inlined as most email clients ignore style sheets.
         *
         * @param EmailConfiguration $configuration The email configuration.
         * @param Event $event The event to render.
         * @return string The HTML body.
         */
        private static function renderHtml(EmailConfiguration $configuration, Event $event): string
        {
            $color = self::getLevelColor($event->getLevel());
            $mono = "font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,'Liberation Mono',monospace;font-size:12px;";
            $pre = $mono . 'margin:0;padding:12px;background:#f4f4f5;border:1px solid #e4e4e7;border-radius:4px;white-space:pre-wrap;word-break:break-word;';

            $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>';
            $html .= '<body style="margin:0;padding:16px;background:#f4f4f5;color:#18181b;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;">';
            $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #e4e4e7;border-top:4px solid ' . $color . ';border-radius:6px;">';

            // Header
            $html .= '<tr><td style="padding:20px 24px 8px;">';
            $html .= sprintf('<div style="color:%s;font-size:12px;font-weight:bold;letter-spacing:0.08em;text-transform:uppercase;">%s</div>', $color, self::escape(self::getLevelName($event->getLevel())));
            $html .= sprintf('<div style="font-size:18px;font-weight:bold;word-break:break-word;">%s</div>', self::escape($event->getApplicationName()));
            $html .= '</td></tr>';

            // Details
            $html .= '<tr><td style="padding:8px 24px;"><table role="presentation" style="width:100%;border-collapse:collapse;">';
            foreach(self::getDetails($configuration, $event) as $label => $value)
            {
                $html .= sprintf('<tr><td style="padding:4px 16px 4px 0;color:#71717a;white-space:nowrap;vertical-align:top;">%s</td><td style="padding:4px 0;word-break:break-word;%s">%s</td></tr>',
                    self::escape($label), $label === 'Location' ? $mono : '', self::escape($value)
                );
            }
            $html .= '</table></td></tr>';

            // Message
            $html .= '<tr><td style="padding:8px 24px;">';
            $html .= '<div style="font-weight:bold;margin-bottom:6px;">Message</div>';
            $html .= sprintf('<pre style="%s">%s</pre>', $pre, self::escape($event->getMessage()));
            $html .= '</td></tr>';

            // Exceptions
            foreach(self::getExceptionChain($event) as $index => $exception)
            {
                $html .= '<tr><td style="padding:8px 24px;">';
                $html .= sprintf('<div style="font-weight:bold;margin-bottom:6px;">%s: <span style="%s">%s</span>%s</div>',
                    $index === 0 ? 'Exception' : 'Caused by', $mono . 'font-size:13px;', self::escape($exception->getName()),
                    ($exception->getCode() !== null && $exception->getCode() !== 0) ? sprintf(' <span style="color:#71717a;font-weight:normal;">(code %d)</span>', $exception->getCode()) : ''
                );

                if(($thrownAt = self::getThrownAt($exception)) !== null)
                {
                    $html .= sprintf('<div style="color:#71717a;margin-bottom:6px;">Thrown at <span style="%s">%s</span></div>', $mono, self::escape($thrownAt));
                }

                if($exception->getMessage() !== '')
                {
                    $html .= sprintf('<div style="padding:8px 12px;margin-bottom:8px;border-left:3px solid %s;background:#fafafa;white-space:pre-wrap;word-break:break-word;">%s</div>', $color, self::escape($exception->getMessage()));
                }

                $frames = self::getFrames($exception, $configuration->getTraceFormat());
                if(count($frames) > 0)
                {
                    $html .= sprintf('<pre style="%s">%s</pre>', $pre, self::escape(implode("\n", $frames)));
                }

                $html .= '</td></tr>';
            }

            $html .= '<tr><td style="padding:16px 24px 20px;color:#a1a1aa;font-size:12px;">Sent by LogLib2</td></tr>';
            $html .= '</table></body></html>';

            return $html;
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
         * Returns the accent color used for the given log level.
         *
         * @param LogLevel $level The log level.
         * @return string The color as a hex string.
         */
        private static function getLevelColor(LogLevel $level): string
        {
            return match($level)
            {
                LogLevel::DEBUG, LogLevel::VERBOSE => '#71717a',
                LogLevel::INFO => '#2563eb',
                LogLevel::WARNING => '#d97706',
                LogLevel::ERROR => '#dc2626',
                LogLevel::CRITICAL => '#991b1b',
            };
        }

        /**
         * Encodes a header value using RFC 2047 encoded-words if it contains non-ASCII characters, line breaks are
         * removed to prevent header injection.
         *
         * @param string $value The header value.
         * @return string The encoded header value.
         */
        private static function encodeHeader(string $value): string
        {
            $value = trim((string)preg_replace('/[\r\n]+/', ' ', $value));
            if(!preg_match('/[^\x20-\x7E]/', $value) && strlen($value) <= 900)
            {
                return $value;
            }

            return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
        }

        /**
         * Escapes text for use in HTML.
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
         * @return string The truncated text.
         */
        private static function truncate(string $text): string
        {
            if(mb_strlen($text) <= self::MAX_SUBJECT_MESSAGE_LENGTH)
            {
                return $text;
            }

            return mb_substr($text, 0, max(0, self::MAX_SUBJECT_MESSAGE_LENGTH - 3)) . '...';
        }

        /**
         * Returns the configured recipients that are valid email addresses.
         *
         * @param EmailConfiguration $configuration The email configuration.
         * @return string[] The valid recipient addresses.
         */
        private static function getValidRecipients(EmailConfiguration $configuration): array
        {
            return array_values(array_filter($configuration->getRecipients(), fn($recipient) => filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false));
        }

        /**
         * Returns a key that uniquely identifies the SMTP server and account emails are sent with.
         *
         * @param EmailConfiguration $configuration The email configuration.
         * @return string The target key.
         */
        private static function getTargetKey(EmailConfiguration $configuration): string
        {
            return sprintf('%s:%d|%s|%s', $configuration->getHost(), $configuration->getPort(), $configuration->getUsername() ?? '', $configuration->getFromAddress() ?? '');
        }
    }
