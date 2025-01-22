<?php

    namespace LogLib2\Enums;

    use DateTime;

    enum TimestampFormat
    {
        case NONE;
        case TIME_ONLY;
        case TIME_ONLY_MILLIS;
        case DATE_ONLY;
        case DATE_TIME;
        case DATE_TIME_MILLIS;
        case UNIX_TIMESTAMP;

        /**
         * Retrieves the format string for the timestamp type.
         *
         * @param int|null $time The time to format, or null to use the current time.
         * @return string Returns the format string for the timestamp type.
         */
        public function format(?int $time=null): string
        {
            $format = match($this)
            {
                self::NONE => '',
                self::TIME_ONLY => 'H:i:s',
                self::TIME_ONLY_MILLIS => 'H:i:s.u',
                self::DATE_ONLY => 'Y-m-d',
                self::DATE_TIME => 'Y-m-d H:i:s',
                self::DATE_TIME_MILLIS => 'Y-m-d H:i:s.u',
                self::UNIX_TIMESTAMP => 'U',
            };

            if($time === null)
            {
                $time = time();
            }

            return (new DateTime())->setTimestamp($time)->format($format);
        }

        /**
         * Parses the input string into a TimestampFormat enum.
         *
         * @param string $input The input string to parse.
         * @return TimestampFormat The parsed TimestampFormat enum.
         */
        public static function parseFrom(string $input): TimestampFormat
        {
            return match(strtolower($input))
            {
                'none', '0' => TimestampFormat::NONE,
                'time_only_millis', '2' => TimestampFormat::TIME_ONLY_MILLIS,
                'date_only', '3' => TimestampFormat::DATE_ONLY,
                'date_time', '4' => TimestampFormat::DATE_TIME,
                'date_time_millis', '5' => TimestampFormat::DATE_TIME_MILLIS,
                'unix_timestamp', '6' => TimestampFormat::UNIX_TIMESTAMP,
                default => TimestampFormat::TIME_ONLY,
            };
        }
    }
