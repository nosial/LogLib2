<?php

    namespace LogLib2\Objects\Configurations;

    use LogLib2\Enums\LogFormat;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;

    class HttpConfiguration
    {
        private bool $enabled;
        private string $endpoint;
        private bool $appendNewline;
        private LogFormat $logFormat;
        private TimestampFormat $timestampFormat;
        private TraceFormat $traceFormat;

        /**
         * HttpConfiguration constructor.
         */
        public function __construct()
        {
            $this->enabled = false;
            $this->endpoint = 'http://0.0.0.0:5131';
            $this->appendNewline = false;
            $this->logFormat = LogFormat::JSONL;
            $this->timestampFormat = TimestampFormat::UNIX_TIMESTAMP;
            $this->traceFormat = TraceFormat::FULL;
        }

        /**
         * Retrieves the enabled status of the HTTP configuration.
         *
         * @return bool Returns true if the HTTP configuration is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Sets the enabled status of the HTTP configuration.
         *
         * @param bool $enabled The enabled status to set.
         * @return HttpConfiguration Returns the current instance.
         */
        public function setEnabled(bool $enabled): HttpConfiguration
        {
            $this->enabled = $enabled;
            return $this;
        }

        /**
         * Retrieves the endpoint of the HTTP configuration.
         *
         * @return string Returns the endpoint as a string.
         */
        public function getEndpoint(): string
        {
            return $this->endpoint;
        }

        /**
         * Sets the endpoint of the HTTP configuration.
         *
         * @param string $endpoint The endpoint to set.
         * @return HttpConfiguration Returns the current instance.
         */
        public function setEndpoint(string $endpoint): HttpConfiguration
        {
            $this->endpoint = $endpoint;
            return $this;
        }

        /**
         * Retrieves the append newline status of the HTTP configuration.
         *
         * @return bool Returns true if the HTTP configuration appends a newline, false otherwise.
         */
        public function isAppendNewline(): bool
        {
            return $this->appendNewline;
        }

        /**
         * Sets the append newline status of the HTTP configuration.
         *
         * @param bool $appendNewline The append newline status to set.
         * @return HttpConfiguration Returns the current instance.
         */
        public function setAppendNewline(bool $appendNewline): HttpConfiguration
        {
            $this->appendNewline = $appendNewline;
            return $this;
        }

        /**
         * Retrieves the log format of the HTTP configuration.
         *
         * @return LogFormat Returns the log format.
         */
        public function getLogFormat(): LogFormat
        {
            return $this->logFormat;
        }

        /**
         * Sets the log format of the HTTP configuration.
         *
         * @param LogFormat $logFormat The log format to set.
         * @return HttpConfiguration Returns the current instance.
         */
        public function setLogFormat(LogFormat $logFormat): HttpConfiguration
        {
            $this->logFormat = $logFormat;
            return $this;
        }

        /**
         * Retrieves the timestamp format of the HTTP configuration.
         *
         * @return TimestampFormat Returns the timestamp format.
         */
        public function getTimestampFormat(): TimestampFormat
        {
            return $this->timestampFormat;
        }

        /**
         * Sets the timestamp format of the HTTP configuration.
         *
         * @param TimestampFormat $timestampFormat The timestamp format to set.
         * @return HttpConfiguration Returns the current instance.
         */
        public function setTimestampFormat(TimestampFormat $timestampFormat): HttpConfiguration
        {
            $this->timestampFormat = $timestampFormat;
            return $this;
        }

        /**
         * Retrieves the trace format of the HTTP configuration.
         *
         * @return TraceFormat Returns the trace format.
         */
        public function getTraceFormat(): TraceFormat
        {
            return $this->traceFormat;
        }

        /**
         * Sets the trace format of the HTTP configuration.
         *
         * @param TraceFormat $traceFormat The trace format to set.
         * @return HttpConfiguration Returns the current instance.
         */
        public function setTraceFormat(TraceFormat $traceFormat): HttpConfiguration
        {
            $this->traceFormat = $traceFormat;
            return $this;
        }
    }