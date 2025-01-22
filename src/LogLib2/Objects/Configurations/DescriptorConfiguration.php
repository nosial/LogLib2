<?php

    namespace LogLib2\Objects\Configurations;

    use LogLib2\Enums\LogFormat;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;

    class DescriptorConfiguration
    {
        private bool $enabled;
        private string $descriptor;
        private bool $appendNewline;
        private LogFormat $logFormat;
        private TimestampFormat $timestampFormat;
        private TraceFormat $traceFormat;

        /**
         * FileConfiguration constructor.
         */
        public function __construct()
        {
            $this->enabled = false;
            $this->descriptor = DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'null';
            $this->appendNewline = false;
            $this->logFormat = LogFormat::JSONL;
            $this->timestampFormat = TimestampFormat::UNIX_TIMESTAMP;
            $this->traceFormat = TraceFormat::FULL;
        }

        /**
         * Retrieves the enabled status of the descriptor configuration.
         *
         * @return bool Returns true if the descriptor configuration is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Sets the enabled status of the descriptor configuration.
         *
         * @param bool $enabled The enabled status to set.
         * @return DescriptorConfiguration Returns the current instance.
         */
        public function setEnabled(bool $enabled): DescriptorConfiguration
        {
            $this->enabled = $enabled;
            return $this;
        }

        /**
         * Retrieves the descriptor of the descriptor configuration.
         *
         * @return string Returns the descriptor as a string.
         */
        public function getDescriptor(): string
        {
            return $this->descriptor;
        }

        /**
         * Sets the descriptor of the descriptor configuration.
         *
         * @param string $descriptor The descriptor to set.
         * @return DescriptorConfiguration Returns the current instance.
         */
        public function setDescriptor(string $descriptor): DescriptorConfiguration
        {
            $this->descriptor = $descriptor;
            return $this;
        }

        /**
         * Retrieves the append newline status of the descriptor configuration.
         *
         * @return bool Returns true if the descriptor configuration appends a newline, false otherwise.
         */
        public function isAppendNewline(): bool
        {
            return $this->appendNewline;
        }

        /**
         * Sets the append newline status of the descriptor configuration.
         *
         * @param bool $appendNewline The append newline status to set.
         * @return DescriptorConfiguration Returns the current instance.
         */
        public function setAppendNewline(bool $appendNewline): DescriptorConfiguration
        {
            $this->appendNewline = $appendNewline;
            return $this;
        }

        /**
         * Retrieves the log type of the descriptor configuration.
         *
         * @return LogFormat Returns the log type.
         */
        public function getLogFormat(): LogFormat
        {
            return $this->logFormat;
        }

        /**
         * Sets the log type of the descriptor configuration.
         *
         * @param LogFormat $logFormat The log type to set.
         * @return DescriptorConfiguration Returns the current instance.
         */
        public function setLogFormat(LogFormat $logFormat): DescriptorConfiguration
        {
            $this->logFormat = $logFormat;
            return $this;
        }

        /**
         * Retrieves the timestamp type of the descriptor configuration.
         *
         * @return TimestampFormat Returns the timestamp type.
         */
        public function getTimestampFormat(): TimestampFormat
        {
            return $this->timestampFormat;
        }

        /**
         * Sets the timestamp type of the descriptor configuration.
         *
         * @param TimestampFormat $timestampFormat The timestamp type to set.
         * @return DescriptorConfiguration Returns the current instance.
         */
        public function setTimestampFormat(TimestampFormat $timestampFormat): DescriptorConfiguration
        {
            $this->timestampFormat = $timestampFormat;
            return $this;
        }

        /**
         * Retrieves the trace format of the descriptor configuration.
         *
         * @return TraceFormat Returns the trace format.
         */
        public function getTraceFormat(): TraceFormat
        {
            return $this->traceFormat;
        }

        /**
         * Sets the trace format of the descriptor configuration.
         *
         * @param TraceFormat $traceFormat The trace format to set.
         * @return DescriptorConfiguration Returns the current instance.
         */
        public function setTraceFormat(TraceFormat $traceFormat): DescriptorConfiguration
        {
            $this->traceFormat = $traceFormat;
            return $this;
        }
    }