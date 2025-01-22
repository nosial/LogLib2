<?php

    namespace LogLib2\Objects\Configurations;

    use LogLib2\Enums\AnsiFormat;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;

    class ConsoleConfiguration
    {
        private bool $enabled;
        private bool $displayName;
        private bool $displayLevel;
        private AnsiFormat $ansiFormat;
        private TraceFormat $traceFormat;
        private TimestampFormat $timestampFormat;

        /**
         * ConsoleConfiguration constructor.
         */
        public function __construct()
        {
            $this->enabled = true;
            $this->displayName = true;
            $this->displayLevel = true;
            $this->ansiFormat = AnsiFormat::BASIC;
            $this->traceFormat = TraceFormat::BASIC;
            $this->timestampFormat = TimestampFormat::TIME_ONLY;
        }

        /**
         * Retrieves the enabled status of the console configuration.
         *
         * @return bool Returns true if the console configuration is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Sets the enabled status of the console configuration.
         *
         * @param bool $enabled The enabled status to set.
         * @return ConsoleConfiguration Returns the current instance.
         */
        public function setEnabled(bool $enabled): ConsoleConfiguration
        {
            $this->enabled = $enabled;
            return $this;
        }

        /**
         * Retrieves the display name status of the console configuration.
         *
         * @return bool Returns true if the display name is enabled, false otherwise.
         */
        public function isDisplayName(): bool
        {
            return $this->displayName;
        }

        /**
         * Sets the display name status of the console configuration.
         *
         * @param bool $displayName The display name status to set.
         * @return ConsoleConfiguration Returns the current instance.
         */
        public function setDisplayName(bool $displayName): ConsoleConfiguration
        {
            $this->displayName = $displayName;
            return $this;
        }

        /**
         * Retrieves the display level status of the console configuration.
         *
         * @return bool Returns true if the display level is enabled, false otherwise.
         */
        public function isDisplayLevel(): bool
        {
            return $this->displayLevel;
        }

        /**
         * Sets the display level status of the console configuration.
         *
         * @param bool $displayLevel The display level status to set.
         * @return ConsoleConfiguration Returns the current instance.
         */
        public function setDisplayLevel(bool $displayLevel): ConsoleConfiguration
        {
            $this->displayLevel = $displayLevel;
            return $this;
        }

        /**
         * Retrieves the ANSI format of the console configuration.
         *
         * @return AnsiFormat Returns the ANSI format as an AnsiForamt.
         */
        public function getAnsiFormat(): AnsiFormat
        {
            return $this->ansiFormat;
        }

        /**
         * Sets the ANSI format of the console configuration.
         *
         * @param AnsiFormat $ansiFormat The ANSI format to set.
         * @return ConsoleConfiguration Returns the current instance.
         */
        public function setAnsiFormat(AnsiFormat $ansiFormat): ConsoleConfiguration
        {
            $this->ansiFormat = $ansiFormat;
            return $this;
        }

        /**
         * Retrieves the trace format of the console configuration.
         *
         * @return TraceFormat Returns the trace format as a TraceFormat.
         */
        public function getTraceFormat(): TraceFormat
        {
            return $this->traceFormat;
        }

        /**
         * Sets the trace format of the console configuration.
         *
         * @param TraceFormat $traceFormat The trace format to set.
         * @return ConsoleConfiguration Returns the current instance.
         */
        public function setTraceFormat(TraceFormat $traceFormat): ConsoleConfiguration
        {
            $this->traceFormat = $traceFormat;
            return $this;
        }

        /**
         * Retrieves the timestamp format of the console configuration.
         *
         * @return TimestampFormat Returns the timestamp format as a TimestampFormat.
         */
        public function getTimestampFormat(): TimestampFormat
        {
            return $this->timestampFormat;
        }

        /**
         * Sets the timestamp format of the console configuration.
         *
         * @param TimestampFormat $timestampFormat The timestamp format to set.
         * @return ConsoleConfiguration Returns the current instance.
         */
        public function setTimestampFormat(TimestampFormat $timestampFormat): ConsoleConfiguration
        {
            $this->timestampFormat = $timestampFormat;
            return $this;
        }
    }