<?php

    namespace LogLib2\Objects\Configurations;

    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\SmtpEncryption;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;

    class EmailConfiguration
    {
        private bool $enabled;
        private string $host;
        private int $port;
        private SmtpEncryption $encryption;
        private bool $verifyPeer;
        private ?string $username;
        private ?string $password;
        private ?string $fromAddress;
        private ?string $fromName;
        private array $recipients;
        private int $timeout;
        private LogLevel $logLevel;
        private TimestampFormat $timestampFormat;
        private TraceFormat $traceFormat;

        /**
         * EmailConfiguration constructor.
         */
        public function __construct()
        {
            $this->enabled = false;
            $this->host = 'localhost';
            $this->port = 587;
            $this->encryption = SmtpEncryption::STARTTLS;
            $this->verifyPeer = true;
            $this->username = null;
            $this->password = null;
            $this->fromAddress = null;
            $this->fromName = 'LogLib2';
            $this->recipients = [];
            $this->timeout = 10;
            $this->logLevel = LogLevel::ERROR;
            $this->timestampFormat = TimestampFormat::DATE_TIME;
            $this->traceFormat = TraceFormat::FULL;
        }

        /**
         * Retrieves the enabled status of the email configuration.
         *
         * @return bool Returns true if the email configuration is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Sets the enabled status of the email configuration.
         *
         * @param bool $enabled The enabled status to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setEnabled(bool $enabled): EmailConfiguration
        {
            $this->enabled = $enabled;
            return $this;
        }

        /**
         * Retrieves the hostname of the SMTP server.
         *
         * @return string Returns the hostname of the SMTP server.
         */
        public function getHost(): string
        {
            return $this->host;
        }

        /**
         * Sets the hostname of the SMTP server.
         *
         * @param string $host The hostname to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setHost(string $host): EmailConfiguration
        {
            $this->host = $host;
            return $this;
        }

        /**
         * Retrieves the port of the SMTP server.
         *
         * @return int Returns the port of the SMTP server.
         */
        public function getPort(): int
        {
            return $this->port;
        }

        /**
         * Sets the port of the SMTP server.
         *
         * @param int $port The port to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setPort(int $port): EmailConfiguration
        {
            $this->port = $port;
            return $this;
        }

        /**
         * Retrieves the encryption used for the SMTP connection.
         *
         * @return SmtpEncryption Returns the encryption used for the SMTP connection.
         */
        public function getEncryption(): SmtpEncryption
        {
            return $this->encryption;
        }

        /**
         * Sets the encryption used for the SMTP connection.
         *
         * @param SmtpEncryption $encryption The encryption to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setEncryption(SmtpEncryption $encryption): EmailConfiguration
        {
            $this->encryption = $encryption;
            return $this;
        }

        /**
         * Retrieves whether the SMTP server's TLS certificate is verified.
         *
         * @return bool Returns true if the certificate is verified, false otherwise.
         */
        public function isVerifyPeer(): bool
        {
            return $this->verifyPeer;
        }

        /**
         * Sets whether the SMTP server's TLS certificate is verified, this should only be disabled for testing or for
         * servers using self-signed certificates.
         *
         * @param bool $verifyPeer True to verify the certificate.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setVerifyPeer(bool $verifyPeer): EmailConfiguration
        {
            $this->verifyPeer = $verifyPeer;
            return $this;
        }

        /**
         * Retrieves the username used to authenticate with the SMTP server.
         *
         * @return string|null Returns the username, or null if authentication is not used.
         */
        public function getUsername(): ?string
        {
            return $this->username;
        }

        /**
         * Sets the username used to authenticate with the SMTP server.
         *
         * @param string|null $username The username to set, or null to disable authentication.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setUsername(?string $username): EmailConfiguration
        {
            $this->username = $username;
            return $this;
        }

        /**
         * Retrieves the password used to authenticate with the SMTP server.
         *
         * @return string|null Returns the password, or null if not set.
         */
        public function getPassword(): ?string
        {
            return $this->password;
        }

        /**
         * Sets the password used to authenticate with the SMTP server.
         *
         * @param string|null $password The password to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setPassword(?string $password): EmailConfiguration
        {
            $this->password = $password;
            return $this;
        }

        /**
         * Retrieves the address emails are sent from.
         *
         * @return string|null Returns the sender address, or null if not set.
         */
        public function getFromAddress(): ?string
        {
            return $this->fromAddress;
        }

        /**
         * Sets the address emails are sent from.
         *
         * @param string|null $fromAddress The sender address to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setFromAddress(?string $fromAddress): EmailConfiguration
        {
            $this->fromAddress = $fromAddress;
            return $this;
        }

        /**
         * Retrieves the display name emails are sent from.
         *
         * @return string|null Returns the sender display name, or null if not set.
         */
        public function getFromName(): ?string
        {
            return $this->fromName;
        }

        /**
         * Sets the display name emails are sent from.
         *
         * @param string|null $fromName The sender display name to set, or null to only use the address.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setFromName(?string $fromName): EmailConfiguration
        {
            $this->fromName = $fromName;
            return $this;
        }

        /**
         * Retrieves the addresses emails are sent to.
         *
         * @return string[] Returns the recipient addresses.
         */
        public function getRecipients(): array
        {
            return $this->recipients;
        }

        /**
         * Sets the addresses emails are sent to.
         *
         * @param string[] $recipients The recipient addresses to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setRecipients(array $recipients): EmailConfiguration
        {
            $this->recipients = array_values(array_unique(array_filter(array_map('trim', $recipients), fn($recipient) => $recipient !== '')));
            return $this;
        }

        /**
         * Adds an address emails are sent to.
         *
         * @param string $recipient The recipient address to add.
         * @return EmailConfiguration Returns the current instance.
         */
        public function addRecipient(string $recipient): EmailConfiguration
        {
            return $this->setRecipients(array_merge($this->recipients, [$recipient]));
        }

        /**
         * Retrieves the timeout in seconds for connecting to and communicating with the SMTP server.
         *
         * @return int Returns the timeout in seconds.
         */
        public function getTimeout(): int
        {
            return $this->timeout;
        }

        /**
         * Sets the timeout in seconds for connecting to and communicating with the SMTP server.
         *
         * @param int $timeout The timeout in seconds to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setTimeout(int $timeout): EmailConfiguration
        {
            $this->timeout = max(1, $timeout);
            return $this;
        }

        /**
         * Retrieves the minimum log level required for an event to be emailed.
         *
         * @return LogLevel Returns the minimum log level.
         */
        public function getLogLevel(): LogLevel
        {
            return $this->logLevel;
        }

        /**
         * Sets the minimum log level required for an event to be emailed, events are still subject to the global log
         * level filter.
         *
         * @param LogLevel $logLevel The minimum log level to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setLogLevel(LogLevel $logLevel): EmailConfiguration
        {
            $this->logLevel = $logLevel;
            return $this;
        }

        /**
         * Retrieves the timestamp format of the email configuration.
         *
         * @return TimestampFormat Returns the timestamp format.
         */
        public function getTimestampFormat(): TimestampFormat
        {
            return $this->timestampFormat;
        }

        /**
         * Sets the timestamp format of the email configuration.
         *
         * @param TimestampFormat $timestampFormat The timestamp format to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setTimestampFormat(TimestampFormat $timestampFormat): EmailConfiguration
        {
            $this->timestampFormat = $timestampFormat;
            return $this;
        }

        /**
         * Retrieves the trace format of the email configuration.
         *
         * @return TraceFormat Returns the trace format.
         */
        public function getTraceFormat(): TraceFormat
        {
            return $this->traceFormat;
        }

        /**
         * Sets the trace format of the email configuration.
         *
         * @param TraceFormat $traceFormat The trace format to set.
         * @return EmailConfiguration Returns the current instance.
         */
        public function setTraceFormat(TraceFormat $traceFormat): EmailConfiguration
        {
            $this->traceFormat = $traceFormat;
            return $this;
        }
    }
