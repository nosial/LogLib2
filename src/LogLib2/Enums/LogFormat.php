<?php

    namespace LogLib2\Enums;

    use LogLib2\Objects\Event;
    use LogLib2\Objects\ExceptionDetails;
    use LogLib2\Objects\StackTrace;
    use SimpleXMLElement;

    enum LogFormat
    {
        case JSONL;
        case CSV;
        case TXT;
        case XML;
        case HTML;

        /**
         * Formats the log entry.
         *
         * @param TimestampFormat $timestampType The timestamp type to use.
         * @param TraceFormat $traceType The trace type to use.
         * @param Event $event The event to format.
         * @return string The formatted log entry.
         */
        public function format(TimestampFormat $timestampType, TraceFormat $traceType, Event $event): string
        {
            return match($this)
            {
                self::JSONL => self::formatJson($timestampType, $traceType, $event),
                self::CSV => self::formatCsv($timestampType, $traceType, $event),
                self::TXT => self::formatTxt($timestampType, $traceType, $event),
                self::XML => self::formatXml($timestampType, $traceType, $event),
                self::HTML => self::formatHtml($timestampType, $traceType, $event),
            };
        }

        /**
         * Parses a log format from a string.
         *
         * @param string $input The input to parse.
         * @return LogFormat The parsed log format.
         */
        public static function parseFrom(string $input): LogFormat
        {
            return match(strtolower($input))
            {
                'csv', '1' => self::CSV,
                'txt', '2' => self::TXT,
                'xml', '3' => self::XML,
                'html', '4' => self::HTML,
                default => self::JSONL
            };
        }

        /**
         * Formats the log entry as a JSON string.
         *
         * @param TimestampFormat $timestampFormat The timestamp format to use.
         * @param TraceFormat $traceFormat The trace format to use.
         * @param Event $event The event to format as a JSON string.
         * @return string The log entry as a JSON string.
         */
        private static function formatJson(TimestampFormat $timestampFormat, TraceFormat $traceFormat, Event $event): string
        {
            return json_encode($event->toStandardArray($timestampFormat, $traceFormat), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        /**
         * Formats the log entry as a CSV string.
         *
         * @param TimestampFormat $timestampFormat The timestamp format to use.
         * @param TraceFormat $traceFormat The trace format to use.
         * @param Event $event The event to format as a CSV string.
         * @return string The log entry as a CSV string.
         */
        private static function formatCsv(TimestampFormat $timestampFormat, TraceFormat $traceFormat, Event $event): string
        {
            $output = self::sanitizeCsv($timestampFormat->format($event->getTimestamp())) . ',';
            $output .= self::sanitizeCsv($event->getLevel()->value) . ',';
            $output .= self::sanitizeCsv($event->getMessage()) . ',';

            if($traceFormat === TraceFormat::NONE || $event->getFirstTrace() === null)
            {
                $output .= '-,';
            }
            else
            {
                $output .= self::sanitizeCsv($traceFormat->format($event->getFirstTrace())) . ',';
            }

            if ($event->getException() === null)
            {
                $output .= '-,';
            }
            else
            {
                $output .= self::sanitizeCsv(json_encode($event->getException()->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . ',';
            }

            return $output;
        }

        /**
         * Sanitizes a CSV value.
         *
         * @param string $value The value to sanitize.
         * @return string The sanitized value.
         */
        private static function sanitizeCsv(string $value): string
        {
            $escapedValue = str_replace('"', '""', $value);
            if (str_contains($escapedValue, ',') || str_contains($escapedValue, '\n') || str_contains($escapedValue, '"'))
            {
                $escapedValue = '"' . $escapedValue . '"';
            }

            return $escapedValue;
        }

        /**
         * Formats the log entry as a plain text string.
         *
         * @param TimestampFormat $timestampType The timestamp type to use.
         * @param TraceFormat $traceType The trace type to use.
         * @param Event $event The event to format as a plain text string.
         * @return string The log entry as a plain text string.
         */
        private static function formatTxt(TimestampFormat $timestampType, TraceFormat $traceType, Event $event): string
        {
            $output = '';
            if ($timestampType !== TimestampFormat::NONE)
            {
                $output .= $timestampType->format($event->getTimestamp()) . ' ';
            }

            $output .= sprintf('[%s] ', $event->getLevel()->value);

            if ($traceType !== TraceFormat::NONE && $event->getFirstTrace() !== null)
            {
                $output .= $traceType->format($event->getFirstTrace()) . ' ';
            }

            $output .= $event->getMessage();
            if ($event->getException() !== null)
            {
                $output .= self::exceptionToString($event->getException());
            }

            return $output;
        }

        /**
         * Formats the log entry as an XML string.
         *
         * @param Event $event The event to format as an XML string.
         * @return string The log entry as an XML string.
         */
        private static function formatXml(TimestampFormat $timestampType, TraceFormat $traceType, Event $event): string
        {
            $xml = new SimpleXMLElement('<event/>');
            $xml->addChild('application_name', $event->getApplicationName());
            $xml->addChild('timestamp', $timestampType->format($event->getTimestamp()));
            $xml->addChild('level', $event->getLevel()->value);
            $xml->addChild('message', $event->getLevel()->value);

            if($traceType !== TraceFormat::NONE && $event->getFirstTrace() !== null)
            {
                $xml->addChild('trace', $traceType->format($event->getFirstTrace()));
            }

            if(count($event->getTraces()) > 0)
            {
                $tracesElement = $xml->addChild('stack_trace');
                foreach($event->getTraces() as $trace)
                {
                    $traceElement = $tracesElement->addChild('trace');
                    self::traceToXml($trace, $traceElement);
                }
            }

            if ($event->getException() !== null)
            {
                self::exceptionToXml($event->getException(), $xml->addChild('exception'));
            }

            $dom = dom_import_simplexml($xml)->ownerDocument;
            $dom->formatOutput = true;
            return $dom->saveXML($dom->documentElement);
        }

        /**
         * Converts an exception to an XML element.
         *
         * @param ExceptionDetails $exception The exception to convert.
         * @param SimpleXMLElement $xml The XML element to append the exception to.
         */
        private static function exceptionToXml(ExceptionDetails $exception, SimpleXMLElement $xml): void
        {
            $xml->addChild('name', htmlspecialchars($exception->getName(), ENT_XML1, 'UTF-8'));
            $xml->addChild('message', htmlspecialchars($exception->getMessage(), ENT_XML1, 'UTF-8'));

            if ($exception->getCode() !== null)
            {
                $xml->addChild('code', (string)$exception->getCode());
            }

            if ($exception->getFile() !== null)
            {
                $xml->addChild('file', htmlspecialchars($exception->getFile(), ENT_XML1, 'UTF-8'));
            }

            if ($exception->getLine() !== null)
            {
                $xml->addChild('line', (string)$exception->getLine());
            }

            if ($exception->getTrace() !== null)
            {
                $tracesElement = $xml->addChild('stack_trace');
                foreach ($exception->getTrace() as $trace)
                {
                    $traceElement = $tracesElement->addChild('trace');
                    self::traceToXml($trace, $traceElement);
                }
            }

            if ($exception->getPrevious() !== null)
            {
                self::exceptionToXml($exception->getPrevious(), $xml->addChild('previous'));
            }
        }

        /**
         * Converts a stack trace to an XML element.
         *
         * @param StackTrace $trace The stack trace to convert.
         * @param SimpleXMLElement $xml The XML element to append the stack trace to.
         */
        private static function traceToXml(StackTrace $trace, SimpleXMLElement $xml): void
        {
            if ($trace->getFile() !== null)
            {
                $xml->addChild('file', htmlspecialchars($trace->getFile(), ENT_XML1, 'UTF-8'));
            }

            if ($trace->getLine() !== null)
            {
                $xml->addChild('line', (string)$trace->getLine());
            }

            if ($trace->getFunction() !== null)
            {
                $xml->addChild('function', htmlspecialchars($trace->getFunction(), ENT_XML1, 'UTF-8'));
            }

            if ($trace->getClass() !== null)
            {
                $xml->addChild('class', htmlspecialchars($trace->getClass(), ENT_XML1, 'UTF-8'));
            }

            if ($trace->getCallType() !== null)
            {
                $xml->addChild('call_type', htmlspecialchars($trace->getCallType()->value, ENT_XML1, 'UTF-8'));
            }

            if ($trace->getArgs() !== null)
            {
                $argsElement = $xml->addChild('arguments');
                foreach ($trace->getArgs() as $arg)
                {
                    $argsElement->addChild('argument', htmlspecialchars(json_encode($arg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_XML1, 'UTF-8'));
                }
            }
        }

        /**
         * Formats the log entry as an HTML string.
         *
         * @param Event $event The event to format as an HTML string.
         * @return string The log entry as an HTML string.
         */
        private static function formatHtml(TimestampFormat $timestampType, TraceFormat $traceType, Event $event): string
        {
            $html = '<div class="log-entry">';
            $html .= sprintf('<p><strong>Timestamp:</strong> %s</p>', $timestampType->format($event->getTimestamp()));
            $html .= sprintf('<p><strong>Level:</strong> %s</p>', $event->getLevel()->value);
            $html .= sprintf('<p><strong>Message:</strong> %s</p>', htmlspecialchars($event->getMessage(), ENT_QUOTES, 'UTF-8'));

            if($traceType !== TraceFormat::NONE && $event->getFirstTrace() !== null)
            {
                $html .= sprintf('<p><strong>Backtrace:</strong> %s</p>', $traceType->format($event->getFirstTrace()));
            }

            if ($event->getException() !== null)
            {
                $html .= '<p><strong>Exception Details:</strong></p>';
                $html .= self::exceptionToHtml($event->getException());
            }

            $html .= '</div>';
            return $html;
        }

        /**
         * Converts an exception to an HTML string.
         *
         * @param ExceptionDetails $exception The exception to convert.
         * @return string The exception as an HTML string.
         */
        private static function exceptionToHtml(ExceptionDetails $exception): string
        {
            $html = '<div class="exception-details">';
            $html .= sprintf('<p><strong>%s:</strong> %s (Code: %s)</p>',
                htmlspecialchars($exception->getName(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'),
                $exception->getCode() !== null ? htmlspecialchars((string)$exception->getCode(), ENT_QUOTES, 'UTF-8') : 'N/A'
            );

            if ($exception->getFile() !== null)
            {
                $html .= sprintf('<p><strong>File:</strong> %s</p>', htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8'));
                if ($exception->getLine() !== null)
                {
                    $html .= sprintf('<p><strong>Line:</strong> %d</p>', $exception->getLine());
                }
            }

            if ($exception->getTrace() !== null)
            {
                $html .= '<p><strong>Stack Trace:</strong></p><ul>';
                foreach ($exception->getTrace() as $trace)
                {
                    $html .= '<li>';
                    $html .= self::traceToHtml($trace);
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }

            if ($exception->getPrevious() !== null)
            {
                $html .= '<p><strong>Caused by:</strong></p>';
                $html .= self::exceptionToHtml($exception->getPrevious());
            }

            $html .= '</div>';
            return $html;
        }

        /**
         * Converts a stack trace to an HTML string.
         *
         * @param StackTrace $trace The stack trace to convert.
         * @return string The stack trace as an HTML string.
         */
        private static function traceToHtml(StackTrace $trace): string
        {
            $output = '<div class="stack-trace">';

            if ($trace->getFile() !== null)
            {
                $output .= sprintf('<p><strong>File:</strong> %s</p>', htmlspecialchars($trace->getFile(), ENT_QUOTES, 'UTF-8'));
            }

            if ($trace->getLine() !== null)
            {
                $output .= sprintf('<p><strong>Line:</strong> %d</p>', $trace->getLine());
            }

            if ($trace->getFunction() !== null)
            {
                $output .= sprintf('<p><strong>Function:</strong> %s</p>', htmlspecialchars($trace->getFunction(), ENT_QUOTES, 'UTF-8'));
            }

            if ($trace->getClass() !== null)
            {
                $output .= sprintf('<p><strong>Class:</strong> %s</p>', htmlspecialchars($trace->getClass(), ENT_QUOTES, 'UTF-8'));
            }

            if ($trace->getCallType() !== null)
            {
                $output .= sprintf('<p><strong>Call Type:</strong> %s</p>', htmlspecialchars($trace->getCallType()->value, ENT_QUOTES, 'UTF-8'));
            }

            if ($trace->getArgs() !== null)
            {
                $output .= '<p><strong>Arguments:</strong></p><ul>';
                foreach ($trace->getArgs() as $arg)
                {
                    $output .= sprintf('<li>%s</li>', htmlspecialchars(json_encode($arg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'));
                }

                $output .= '</ul>';
            }

            $output .= '</div>';
            return $output;
        }

        /**
         * Converts an exception to a string.
         *
         * @param ExceptionDetails $exception The exception to convert.
         * @return string The exception as a string.
         */
        private static function exceptionToString(ExceptionDetails $exception): string
        {
            $output = sprintf("\n%s: %s (%s)", $exception->getName(), $exception->getMessage(), $exception->getCode() ?? 0);

            if ($exception->getFile() !== null)
            {
                $output .= sprintf("\nFile: %s", $exception->getFile());

                if ($exception->getLine() !== null)
                {
                    $output .= sprintf(":%d", $exception->getLine());
                }
            }

            if ($exception->getTrace() !== null)
            {
                $output .= "\n";
                foreach ($exception->getTrace() as $trace)
                {
                    $output .= sprintf("  %s\n", TraceFormat::FULL->format($trace));
                }
            }

            if ($exception->getPrevious() !== null)
            {
                $output .= self::exceptionToString($exception->getPrevious());
            }

            return $output;
        }
    }
