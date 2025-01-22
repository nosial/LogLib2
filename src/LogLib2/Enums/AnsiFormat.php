<?php

    namespace LogLib2\Enums;

    enum AnsiFormat
    {
        case NONE;
        case BASIC;

        /**
         * Parses the input string into an AnsiFormat enum.
         *
         * @param string $input The input string to parse.
         * @return AnsiFormat The parsed AnsiFormat enum.
         */
        public static function parseFrom(string $input): AnsiFormat
        {
            return match(strtolower($input))
            {
                'basic', '1' => AnsiFormat::BASIC,
                default => AnsiFormat::NONE,
            };
        }
    }
