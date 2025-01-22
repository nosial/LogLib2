<?php

    namespace LogLib2\Objects\Configurations;

    use LogLib2\Classes\Utilities;
    use LogLib2\Enums\LogFormat;
    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;

    class FileConfiguration
    {
        private bool $enabled;
        private LogLevel $logLevel;
        private string $logPath;
        private int $defaultPermissions;
        private bool $appendNewline;
        private LogFormat $logFormat;
        private TimestampFormat $timestampFormat;
        private TraceFormat $traceFormat;

        /**
         * FileConfiguration constructor.
         */
        public function __construct()
        {
            $this->enabled = true;
            $this->logPath = Utilities::getEnvironmentLogPath();
            $this->defaultPermissions = 0777;
            $this->appendNewline = true;
            $this->logFormat = LogFormat::TXT;
            $this->timestampFormat = TimestampFormat::TIME_ONLY;
            $this->traceFormat = TraceFormat::BASIC;
        }

        /**
         * Retrieves the enabled status of the file configuration.
         *
         * @return bool Returns true if the file configuration is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Sets the enabled status of the file configuration.
         *
         * @param bool $enabled The enabled status to set.
         * @return FileConfiguration Returns the current instance.
         */
        public function setEnabled(bool $enabled): FileConfiguration
        {
            $this->enabled = $enabled;
            return $this;
        }

        /**
         * Retrieves the log level of the file configuration.
         *
         * @return LogLevel Returns the log level as a LogLevel.
         */
        public function getLogLevel(): LogLevel
        {
            return $this->logLevel;
        }

        /**
         * Sets the log level of the file configuration.
         *
         * @param LogLevel $logLevel The log level to set.
         * @return FileConfiguration Returns the current instance.
         */
        public function setLogLevel(LogLevel $logLevel): FileConfiguration
        {
            $this->logLevel = $logLevel;
            return $this;
        }

        /**
         * Retrieves the log path of the file configuration.
         *
         * @return string Returns the log path as a string.
         */
        public function getLogPath(): string
        {
            return $this->logPath;
        }

        /**
         * Sets the log path of the file configuration.
         *
         * @param string $logPath The log path to set.
         * @return FileConfiguration Returns the current instance.
         */
        public function setLogPath(string $logPath): FileConfiguration
        {
            $this->logPath = $logPath;
            return $this;
        }

        /**
         * Retrieves the default permissions of the file configuration.
         *
         * @return int Returns the default permissions as an integer.
         */
        public function getDefaultPermissions(): int
        {
            return $this->defaultPermissions;
        }

        /**
         * @param int $defaultPermissions
         * @return FileConfiguration
         */
        public function setDefaultPermissions(int $defaultPermissions): FileConfiguration
        {
            $this->defaultPermissions = $defaultPermissions;
            return $this;
        }

        /**
         * Retrieves the append newline status of the file configuration.
         *
         * @return bool Returns true if the file configuration appends a newline, false otherwise.
         */
        public function isAppendNewline(): bool
        {
            return $this->appendNewline;
        }

        /**
         * Sets the append newline status of the file configuration.
         *
         * @param bool $appendNewline The append newline status to set.
         * @return FileConfiguration Returns the current instance.
         */
        public function setAppendNewline(bool $appendNewline): FileConfiguration
        {
            $this->appendNewline = $appendNewline;
            return $this;
        }

        /**
         * Retrieves the log type of the file configuration.
         *
         * @return LogFormat Returns the log type.
         */
        public function getLogFormat(): LogFormat
        {
            return $this->logFormat;
        }

        /**
         * Sets the log type of the file configuration.
         *
         * @param LogFormat $logFormat The log type to set.
         * @return FileConfiguration Returns the current instance.
         */
        public function setLogFormat(LogFormat $logFormat): FileConfiguration
        {
            $this->logFormat = $logFormat;
            return $this;

        }

        /**
         * Retrieves the timestamp format of the file configuration.
         *
         * @return TimestampFormat Returns the timestamp format as a TimestampFormat.
         */
        public function getTimestampFormat(): TimestampFormat
        {
            return $this->timestampFormat;
        }

        /**
         * Sets the timestamp format of the file configuration.
         *
         * @param TimestampFormat $timestampFormat The timestamp format to set.
         * @return FileConfiguration Returns the current instance.
         */
        public function setTimestampFormat(TimestampFormat $timestampFormat): FileConfiguration
        {
            $this->timestampFormat = $timestampFormat;
            return $this;
        }

        /**
         * Retrieves the trace format of the file configuration.
         *
         * @return TraceFormat Returns the trace format as a TraceFormat.
         */
        public function getTraceFormat(): TraceFormat
        {
            return $this->traceFormat;
        }

        /**
         * Sets the trace format of the file configuration.
         *
         * @param TraceFormat $traceFormat The trace format to set.
         * @return FileConfiguration Returns the current instance.
         */
        public function setTraceFormat(TraceFormat $traceFormat): FileConfiguration
        {
            $this->traceFormat = $traceFormat;
            return $this;
        }
    }