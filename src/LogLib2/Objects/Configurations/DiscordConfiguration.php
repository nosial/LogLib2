<?php

    namespace LogLib2\Objects\Configurations;

    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\TraceFormat;

    class DiscordConfiguration
    {
        private bool $enabled;
        private ?string $webhookUrl;
        private ?string $threadId;
        private ?string $username;
        private ?string $avatarUrl;
        private LogLevel $logLevel;
        private TraceFormat $traceFormat;

        /**
         * DiscordConfiguration constructor.
         */
        public function __construct()
        {
            $this->enabled = false;
            $this->webhookUrl = null;
            $this->threadId = null;
            $this->username = null;
            $this->avatarUrl = null;
            $this->logLevel = LogLevel::ERROR;
            $this->traceFormat = TraceFormat::FULL;
        }

        /**
         * Retrieves the enabled status of the Discord configuration.
         *
         * @return bool Returns true if the Discord configuration is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Sets the enabled status of the Discord configuration.
         *
         * @param bool $enabled The enabled status to set.
         * @return DiscordConfiguration Returns the current instance.
         */
        public function setEnabled(bool $enabled): DiscordConfiguration
        {
            $this->enabled = $enabled;
            return $this;
        }

        /**
         * Retrieves the webhook URL that notifications are sent to.
         *
         * @return string|null Returns the webhook URL, or null if not set.
         */
        public function getWebhookUrl(): ?string
        {
            return $this->webhookUrl;
        }

        /**
         * Sets the webhook URL that notifications are sent to, created under the channel's Integrations settings
         * (e.g. https://discord.com/api/webhooks/{id}/{token}).
         *
         * @param string|null $webhookUrl The webhook URL to set.
         * @return DiscordConfiguration Returns the current instance.
         */
        public function setWebhookUrl(?string $webhookUrl): DiscordConfiguration
        {
            $this->webhookUrl = $webhookUrl;
            return $this;
        }

        /**
         * Retrieves the ID of the thread that notifications are sent to.
         *
         * @return string|null Returns the thread ID, or null if notifications are sent to the webhook's channel.
         */
        public function getThreadId(): ?string
        {
            return $this->threadId;
        }

        /**
         * Sets the ID of the thread (or forum post) within the webhook's channel that notifications are sent to.
         *
         * @param string|int|null $threadId The thread ID to set, or null to send to the webhook's channel.
         * @return DiscordConfiguration Returns the current instance.
         */
        public function setThreadId(string|int|null $threadId): DiscordConfiguration
        {
            $this->threadId = $threadId === null ? null : (string)$threadId;
            return $this;
        }

        /**
         * Retrieves the username that overrides the webhook's default username.
         *
         * @return string|null Returns the username, or null to use the webhook's default.
         */
        public function getUsername(): ?string
        {
            return $this->username;
        }

        /**
         * Sets the username that overrides the webhook's default username.
         *
         * @param string|null $username The username to set, or null to use the webhook's default.
         * @return DiscordConfiguration Returns the current instance.
         */
        public function setUsername(?string $username): DiscordConfiguration
        {
            $this->username = $username;
            return $this;
        }

        /**
         * Retrieves the avatar URL that overrides the webhook's default avatar.
         *
         * @return string|null Returns the avatar URL, or null to use the webhook's default.
         */
        public function getAvatarUrl(): ?string
        {
            return $this->avatarUrl;
        }

        /**
         * Sets the avatar URL that overrides the webhook's default avatar.
         *
         * @param string|null $avatarUrl The avatar URL to set, or null to use the webhook's default.
         * @return DiscordConfiguration Returns the current instance.
         */
        public function setAvatarUrl(?string $avatarUrl): DiscordConfiguration
        {
            $this->avatarUrl = $avatarUrl;
            return $this;
        }

        /**
         * Retrieves the minimum log level required for an event to be sent to Discord.
         *
         * @return LogLevel Returns the minimum log level.
         */
        public function getLogLevel(): LogLevel
        {
            return $this->logLevel;
        }

        /**
         * Sets the minimum log level required for an event to be sent to Discord, events are still subject to the
         * global log level filter.
         *
         * @param LogLevel $logLevel The minimum log level to set.
         * @return DiscordConfiguration Returns the current instance.
         */
        public function setLogLevel(LogLevel $logLevel): DiscordConfiguration
        {
            $this->logLevel = $logLevel;
            return $this;
        }

        /**
         * Retrieves the trace format of the Discord configuration.
         *
         * @return TraceFormat Returns the trace format.
         */
        public function getTraceFormat(): TraceFormat
        {
            return $this->traceFormat;
        }

        /**
         * Sets the trace format of the Discord configuration.
         *
         * @param TraceFormat $traceFormat The trace format to set.
         * @return DiscordConfiguration Returns the current instance.
         */
        public function setTraceFormat(TraceFormat $traceFormat): DiscordConfiguration
        {
            $this->traceFormat = $traceFormat;
            return $this;
        }
    }
