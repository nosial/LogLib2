<?php

    namespace LogLib2\Enums;

    enum LogLevel : string
    {
        /**
         * Represents a debug level constant.
         */
        case DEBUG = 'DBG';

        /**
         * Represents a verbose level constant.
         */
        case VERBOSE = 'VRB';

        /**
         * Represents an information level constant.
         */
        case INFO = 'INFO';

        /**
         * Represents a warning level constant.
         */
        case WARNING = 'WRN';

        /**
         * Represents an error level constant.
         */
        case ERROR = 'ERR';

        /**
         * Represents a critical level constant.
         */
        case CRITICAL = 'CRT';

        /**
         * Retrieves the levels of severity corresponding to the current level.
         *
         * @return array An array of levels applicable to the current instance.
         */
        public function getLevels(): array
        {
            return match ($this)
            {
                self::DEBUG => [self::DEBUG, self::VERBOSE, self::INFO, self::WARNING, self::ERROR, self::CRITICAL],
                self::VERBOSE => [self::VERBOSE, self::INFO, self::WARNING, self::ERROR, self::CRITICAL],
                self::INFO => [self::INFO, self::WARNING, self::ERROR, self::CRITICAL],
                self::WARNING => [self::WARNING, self::ERROR, self::CRITICAL],
                self::ERROR => [self::ERROR, self::CRITICAL],
                self::CRITICAL => [self::CRITICAL],
            };
        }

        /**
         * Checks if the provided log level is allowed based on the current instance's levels.
         *
         * @param LogLevel $level The log level to check against the allowed levels.
         * @return bool True if the log level is allowed, false otherwise.
         */
        public function levelAllowed(LogLevel $level): bool
        {
            return in_array($level, $this->getLevels());
        }

        /**
         * Parses the given value and returns the corresponding log level.
         *
         * @param int|string $value The value to parse, which can be an integer or a string representation of the log level.
         * @return LogLevel The log level matching the provided value, or the default log level if no match is found.
         */
        public static function parseFrom(int|string $value): LogLevel
        {
            if(is_string($value))
            {
                $value = strtolower($value);
            }

            return match($value)
            {
                'debug', 'dbg', 'd', 0 => self::DEBUG,
                'verbose', 'verb', 'vrb', 'v', 1 => self::VERBOSE,
                'information', 'info', 'inf', 'i', 2 => self::INFO,
                'warning', 'warn', 'wrn', 'w', 3 => self::WARNING,
                'error', 'err', 'e', 4 => self::ERROR,
                'critical', 'crit', 'crt', 'c', 5 => self::CRITICAL,
                default => self::INFO
            };
        }
    }
