<?php

    namespace LogLib2\Objects\Configurations;

    use LogLib2\Enums\LogFormat;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;

    class UdpConfiguration
    {
        private bool $enabled;
        private string $host;
        private int $port;
        private bool $appendNewline;
        private LogFormat $logFormat;
        private TimestampFormat $timestampFormat;
        private TraceFormat $traceFormat;

        /**
         * TcpConfiguration constructor.
         */
        public function __construct()
        {
            $this->enabled = false;
            $this->host = '0.0.0.0';
            $this->port = 5131;
            $this->appendNewline = false;
            $this->logFormat = LogFormat::JSONL;
            $this->timestampFormat = TimestampFormat::UNIX_TIMESTAMP;
            $this->traceFormat = TraceFormat::FULL;
        }

        /**
         * Retrieves the enabled status of the TCP configuration.
         *
         * @return bool Returns true if the TCP configuration is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Sets the enabled status of the TCP configuration.
         *
         * @param bool $enabled The enabled status to set.
         * @return UdpConfiguration Returns the current instance.
         */
        public function setEnabled(bool $enabled): UdpConfiguration
        {
            $this->enabled = $enabled;
            return $this;
        }

        /**
         * Retrieves the host of the TCP configuration.
         *
         * @return string Returns the host as a string.
         */
        public function getHost(): string
        {
            return $this->host;
        }

        /**
         * Sets the host of the TCP configuration.
         *
         * @param string $host The host to set.
         * @return UdpConfiguration Returns the current instance.
         */
        public function setHost(string $host): UdpConfiguration
        {
            $this->host = $host;
            return $this;
        }

        /**
         * Retrieves the port of the TCP configuration.
         *
         * @return int Returns the port as an integer.
         */
        public function getPort(): int
        {
            return $this->port;
        }

        /**
         * Sets the port of the TCP configuration.
         *
         * @param int $port The port to set.
         * @return UdpConfiguration Returns the current instance.
         */
        public function setPort(int $port): UdpConfiguration
        {
            $this->port = $port;
            return $this;
        }

        /**
         * Retrieves the append newline status of the TCP configuration.
         *
         * @return bool Returns true if the TCP configuration appends a newline, false otherwise.
         */
        public function isAppendNewline(): bool
        {
            return $this->appendNewline;
        }

        /**
         * Sets the append newline status of the TCP configuration.
         *
         * @param bool $appendNewline The append newline status to set.
         * @return UdpConfiguration Returns the current instance.
         */
        public function setAppendNewline(bool $appendNewline): UdpConfiguration
        {
            $this->appendNewline = $appendNewline;
            return $this;
        }

        /**
         * Retrieves the log format of the TCP configuration.
         *
         * @return LogFormat Returns the log format.
         */
        public function getLogFormat(): LogFormat
        {
            return $this->logFormat;
        }

        /**
         * Sets the log format of the TCP configuration.
         *
         * @param LogFormat $logFormat The log format to set.
         * @return UdpConfiguration Returns the current instance.
         */
        public function setLogFormat(LogFormat $logFormat): UdpConfiguration
        {
            $this->logFormat = $logFormat;
            return $this;
        }

        /**
         * Retrieves the timestamp format of the TCP configuration.
         *
         * @return TimestampFormat Returns the timestamp format.
         */
        public function getTimestampFormat(): TimestampFormat
        {
            return $this->timestampFormat;
        }

        /**
         * Sets the timestamp format of the TCP configuration.
         *
         * @param TimestampFormat $timestampFormat The timestamp format to set.
         * @return UdpConfiguration Returns the current instance.
         */
        public function setTimestampFormat(TimestampFormat $timestampFormat): UdpConfiguration
        {
            $this->timestampFormat = $timestampFormat;
            return $this;
        }

        /**
         * Retrieves the trace format of the TCP configuration.
         *
         * @return TraceFormat Returns the trace format.
         */
        public function getTraceFormat(): TraceFormat
        {
            return $this->traceFormat;
        }

        /**
         * Sets the trace format of the TCP configuration.
         *
         * @param TraceFormat $traceFormat The trace format to set.
         * @return UdpConfiguration Returns the current instance.
         */
        public function setTraceFormat(TraceFormat $traceFormat): UdpConfiguration
        {
            $this->traceFormat = $traceFormat;
            return $this;
        }
    }