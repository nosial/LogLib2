<?php

    namespace LogLib2\Classes;

    use ErrorException;
    use LogLib2\Enums\LogLevel;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\ExceptionDetails;
    use LogLib2\Objects\StackTrace;
    use OptsLib\Parse;
    use Throwable;

    class Utilities
    {
        private static ?array $cachedOptions = null;

        /**
         * Retrieves the logging level from various sources, including environment variables,
         * CLI arguments, or from the Application object. Defaults to INFO if no specific
         * logging level is found.
         *
         * @return LogLevel Returns the determined logging level based on the available sources.
         */
        public static function getEnvironmentLogLevel(): LogLevel
        {
            // Parse from environment if a variable is set.
            if(getenv('LOG_LEVEL'))
            {
                return LogLevel::parseFrom(getenv('LOG_LEVEL'));
            }

            // Parse from CLI arguments if the script is running in CLI mode.
            if(php_sapi_name() === 'cli')
            {
                if(self::$cachedOptions === null)
                {
                    self::$cachedOptions = Parse::getArguments();
                }

                if(isset(self::$cachedOptions['log-level']))
                {
                    return LogLevel::parseFrom(self::$cachedOptions['log-level']);
                }
            }

            return LogLevel::INFO;
        }

        /**
         * Determines the log path for the application from the environment, CLI arguments, or application defaults.
         *
         * The method checks if a log path is defined in environment variables, provided as a command-line argument,
         * or set in the application instance. If no path is specified, it defaults to '/tmp/logs'.
         *
         * @param Application|null $application Optional application instance to retrieve a default log path.
         * @return string The log path determined from the environment, CLI arguments, application instance, or default value.
         */
        public static function getEnvironmentLogPath(?Application $application=null): string
        {
            // Parse from CLI arguments if the script is running in CLI mode.
            if(php_sapi_name() === 'cli')
            {
                if(self::$cachedOptions === null)
                {
                    self::$cachedOptions = Parse::getArguments();
                }

                if(isset(self::$cachedOptions['log-path']))
                {
                    return rtrim(self::$cachedOptions['log-path'], DIRECTORY_SEPARATOR);
                }
            }

            if($application?->getFileConfiguration()?->getLogPath() !== null)
            {
                return rtrim($application->getFileConfiguration()->getLogPath(), DIRECTORY_SEPARATOR);
            }

            return DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'logs';
        }

        /**
         * Processes an input value and returns a safely formatted string representation.
         * Converts arrays and other unsupported data types into a structured string format.
         *
         * @param mixed $input The input value to be processed. It can be of any type including arrays, strings, integers, etc.
         *
         * @return string A safe and structured string representation of the input value.
         */
        public static function getSafeValue(mixed $input): string
        {
            if(is_array($input))
            {
                if(array_is_list($input))
                {
                    $output = [];
                    foreach($input as $value)
                    {
                        $output[] = self::getSafeValue($value);
                    }

                    return sprintf('[%s]', implode(', ', $output));
                }
                else
                {
                    $output = [];
                    foreach($input as $key => $value)
                    {
                        $output[] = sprintf('%s: %s', self::getSafeValue($key), self::getSafeValue($value));
                    }

                    return sprintf('[%s]', implode(', ', $output));
                }
            }

            return match(strtolower(gettype($input)))
            {
                'boolean', 'integer', 'double', 'float', 'string', 'null' => $input,
                default => sprintf('[%s]', strtoupper(gettype($input))),
            };
        }

        /**
         * Retrieves a backtrace from the current execution point.
         *
         * @param int $level The number of stack frames to skip before starting the trace.
         * @return StackTrace[] An array of StackTrace objects representing the backtrace.
         */
        public static function getBackTrace(int $level=3): array
        {
            if(!function_exists('debug_backtrace'))
            {
                return [];
            }

            $debugBacktrace = debug_backtrace();
            $results = [];

            foreach($debugBacktrace as $trace)
            {
                $stackTrace = StackTrace::fromTrace($trace);

                if($stackTrace->isEmpty())
                {
                    continue;
                }

                $results[] = $stackTrace;
            }

            return array_slice($results, $level);
        }

        /**
         * Sanitizes a file name by replacing invalid characters with underscores.
         *
         * @param string $name The file name to sanitize.
         * @return string Returns the sanitized file name.
         */
        public static function sanitizeFileName(string $name): string
        {
            return preg_replace('/[\/:*?"<>|.]/', '_', str_replace(' ', '-', $name));
        }

        /**
         * Converts an Error instance into an ErrorException instance.
         *
         * @param int $errno The error number.
         * @param string $errstr The error message.
         * @param string $errfile The file in which the error occurred.
         * @param int $errline The line number in which the error occurred.
         * @return ExceptionDetails Returns the converted ErrorException instance.
         */
        public static function detailsFromError(int|string $errno, string $errstr, string $errfile, int $errline): ExceptionDetails
        {
            if(is_string($errno))
            {
                $errno = 0;
                $errstr = sprintf('%s: %s', $errno, $errstr);
            }

            return new ExceptionDetails('Runtime', $errstr, $errno, $errfile, $errline);
        }
    }