<?php

    namespace LogLib2\Objects\Configurations;

    use LogLib2\Enums\LogFormat;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;

    class TcpConfiguration
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
         * @return TcpConfiguration Returns the current instance.
         */
        public function setEnabled(bool $enabled): TcpConfiguration
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
         * @return TcpConfiguration Returns the current instance.
         */
        public function setHost(string $host): TcpConfiguration
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
         * @return TcpConfiguration Returns the current instance.
         */
        public function setPort(int $port): TcpConfiguration
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
         * @return TcpConfiguration Returns the current instance.
         */
        public function setAppendNewline(bool $appendNewline): TcpConfiguration
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
         * @return TcpConfiguration Returns the current instance.
         */
        public function setLogFormat(LogFormat $logFormat): TcpConfiguration
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
         * @return TcpConfiguration Returns the current instance.
         */
        public function setTimestampFormat(TimestampFormat $timestampFormat): TcpConfiguration
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
    }