<?php

    namespace LogLib2\Classes\LogHandlers;

    use LogLib2\Enums\AnsiFormat;
    use LogLib2\Enums\ConsoleColor;
    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;
    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Event;
    use LogLib2\Objects\ExceptionDetails;
    use Random\RandomException;

    class ConsoleHandler implements LogHandlerInterface
    {
        private static array $applicationColors = [];

        /**
         * Checks if the current PHP environment is available for execution in CLI mode.
         *
         * @return bool True if the PHP environment is running in CLI mode, false otherwise.
         */
        public static function isAvailable(Application $application): bool
        {
            return php_sapi_name() === 'cli';
        }

        /**
         * @inheritDoc
         */
        public static function handleEvent(Application $application, Event $event): void
        {
            // Output the event to the console based on the ANSI style.
            $output = match($application->getConsoleConfiguration()->getAnsiFormat())
            {
                AnsiFormat::NONE => self::noAnsi($application, $event),
                AnsiFormat::BASIC => self::basicOutput($application, $event),
            };

            // Output the event to the appropriate console stream based on the LogLevel.
            switch($event->getLevel())
            {
                case LogLevel::DEBUG:
                case LogLevel::VERBOSE:
                case LogLevel::INFO:
                    fwrite(STDOUT, $output);
                    break;

                case LogLevel::WARNING:
                case LogLevel::ERROR:
                case LogLevel::CRITICAL:
                    fwrite(STDERR, $output);
                    break;
            }
        }

        /**
         * Outputs the event to the console.
         *
         * @param Application $application The application that generated the event.
         * @param Event $event The event to output.
         */
        private static function noAnsi(Application $application, Event $event): string
        {
            $output = (string)null;

            if($application->getConsoleConfiguration()->getTimestampFormat() !== TimestampFormat::NONE)
            {
                $output .= $application->getConsoleConfiguration()->getTimestampFormat()->format($event->getTimestamp());
            }

            if($application->getConsoleConfiguration()->isDisplayName())
            {
                if($output !== (string)null)
                {
                    $output .= ' ';
                }

                $output .= $application->getName();
            }

            if($application->getConsoleConfiguration()->isDisplayLevel())
            {
                if($output !== (string)null)
                {
                    $output .= ' ';
                }

                $output .= sprintf('[%s]', $event->getLevel()->value);
            }

            if($application->getConsoleConfiguration()->getTraceFormat() !== TraceFormat::NONE && $event->getFirstTrace() !== null)
            {
                if($output !== (string)null)
                {
                    $output .= ' ';
                }

                $output .= $application->getConsoleConfiguration()->getTraceFormat()->format($event->getFirstTrace());
            }

            if($output !== (string)null)
            {
                $output .= $event->getMessage();
            }


            if($event->getException() !== null)
            {
                $output .= self::noAnsiException($event->getException());
            }

            return $output . "\n";
        }

        /**
         * Outputs the exception details in a basic format.
         *
         * @param ExceptionDetails $exception The exception details to output.
         * @param bool $previous If this is a previous exception in the chain.
         * @return string The formatted exception details.
         */
        private static function noAnsiException(ExceptionDetails $exception, bool $previous=false): string
        {
            if($previous)
            {
                $output = sprintf("%s", $exception->getName());
            }
            else
            {
                $output = sprintf("\n%s", $exception->getName());
            }

            if($exception->getCode() !== 0 && $exception->getCode() !== null)
            {
                $output .= sprintf(" (%d)", $exception->getCode());
            }

            if($exception->getMessage() !== null)
            {
                $output .= sprintf(": %s", $exception->getMessage());
            }

            if($exception->getFile() !== null)
            {
                $output .= sprintf("    File: %s", $exception->getFile());

                if($exception->getLine() !== null && $exception->getLine() !== 0)
                {
                    $output .= sprintf(":%d", $exception->getLine());
                }
            }

            if($exception->getTrace() !== null)
            {
                $output .= "\n  Stack Trace:\n";
                foreach($exception->getTrace() as $trace)
                {
                    $output .= sprintf("    - %s\n", TraceFormat::FULL->format($trace));
                }
            }

            if($exception->getPrevious() !== null)
            {
                $output .= self::noAnsiException($exception->getPrevious(), true);
            }

            return $output;
        }

        /**
         * Outputs the exception details in a basic format.
         *
         * @param Application $application The application that generated the event.
         * @param Event $event The event to output.
         * @return string The formatted exception details.
         * @throws RandomException Thrown when the random_int function fails to generate a random integer.
         */
        private static function basicOutput(Application $application, Event $event): string
        {
            $output = (string)null;

            if($application->getConsoleConfiguration()->getTimestampFormat() !== TimestampFormat::NONE)
            {
                $output .= ConsoleColor::DEFAULT->formatBold($application->getConsoleConfiguration()->getTimestampFormat()->format($event->getTimestamp()));
            }

            if($application->getConsoleConfiguration()->isDisplayName())
            {
                if($output !== (string)null)
                {
                    $output .= ' ';
                }

                $output .= self::getApplicationColor($application)->formatBold($application->getName());
            }

            if($application->getConsoleConfiguration()->isDisplayLevel())
            {
                if($output !== (string)null)
                {
                    $output .= ' ';
                }

                $output .= sprintf('[%s]', ConsoleColor::DEFAULT->formatBold($event->getLevel()->value));
            }

            if($application->getConsoleConfiguration()->getTraceFormat() !== TraceFormat::NONE && $event->getFirstTrace() !== null)
            {
                if($output !== (string)null)
                {
                    $output .= ' ';
                }

                $output .= $application->getConsoleConfiguration()->getTraceFormat()->format($event->getFirstTrace());
            }

            if($output !== (string)null)
            {
                $output .= ' ';
            }

            $output .= $event->getMessage();

            if($event->getException() !== null)
            {
                $output .= self::basicOutputException($event->getException());
            }

            return $output . "\n";
        }

        /**
         * Outputs the exception details in a basic format.
         *
         * @param ExceptionDetails $exception The exception details to output.
         * @param bool $previous If this is a previous exception in the chain.
         * @return string The formatted exception details.
         */
        private static function basicOutputException(ExceptionDetails $exception, bool $previous=false): string
        {
            if($previous)
            {
                $output = sprintf("%s", ConsoleColor::RED->formatBold($exception->getName()));
            }
            else
            {
                $output = sprintf("\n%s", ConsoleColor::RED->formatBold($exception->getName()));
            }

            if($exception->getCode() !== 0 && $exception->getCode() !== null)
            {
                $output .= sprintf(" (%d)", ConsoleColor::DEFAULT->formatBold($exception->getCode()));
            }

            if($exception->getMessage() !== null)
            {
                $output .= sprintf(": %s", $exception->getMessage());
            }

            if($exception->getFile() !== null)
            {
                $output .= sprintf("\n  File: %s", $exception->getFile());

                if($exception->getLine() !== null && $exception->getLine() !== 0)
                {
                    $output .= sprintf(":%d", ConsoleColor::DEFAULT->formatBold($exception->getLine()));
                }
            }

            if($exception->getTrace() !== null && count($exception->getTrace()) > 0)
            {
                $output .= "\n  Stack Trace:\n";

                foreach($exception->getTrace() as $trace)
                {
                    $output .= sprintf("    - %s\n", ConsoleColor::DEFAULT->formatLight(TraceFormat::FULL->format($trace)));
                }
            }

            if($exception->getPrevious() !== null)
            {
                $output .= self::basicOutputException($exception->getPrevious(), true);
            }

            return $output;
        }

        /**
         * Retrieves the color for the given application.
         *
         * @param Application $application The application to retrieve the color for.
         * @return ConsoleColor The color for the given application.
         * @throws RandomException Thrown when the random_int function fails to generate a random integer.
         */
        private static function getApplicationColor(Application $application): ConsoleColor
        {
            if(!isset(self::$applicationColors[$application->getName()]))
            {
                self::$applicationColors[$application->getName()] = ConsoleColor::getRandomColor([
                    ConsoleColor::BLACK, ConsoleColor::DEFAULT
                ]);
            }

            return self::$applicationColors[$application->getName()];
        }
    }