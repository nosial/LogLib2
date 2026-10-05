<?php

    namespace LogLib2\Objects\Configurations;

    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;

    class TelegramConfiguration
    {
        private bool $enabled;
        private ?string $botToken;
        private ?string $chatId;
        private ?int $topicId;
        private string $apiEndpoint;
        private LogLevel $logLevel;
        private bool $disableNotification;
        private TimestampFormat $timestampFormat;
        private TraceFormat $traceFormat;

        /**
         * TelegramConfiguration constructor.
         */
        public function __construct()
        {
            $this->enabled = false;
            $this->botToken = null;
            $this->chatId = null;
            $this->topicId = null;
            $this->apiEndpoint = 'https://api.telegram.org';
            $this->logLevel = LogLevel::ERROR;
            $this->disableNotification = false;
            $this->timestampFormat = TimestampFormat::DATE_TIME;
            $this->traceFormat = TraceFormat::FULL;
        }

        /**
         * Retrieves the enabled status of the Telegram configuration.
         *
         * @return bool Returns true if the Telegram configuration is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Sets the enabled status of the Telegram configuration.
         *
         * @param bool $enabled The enabled status to set.
         * @return TelegramConfiguration Returns the current instance.
         */
        public function setEnabled(bool $enabled): TelegramConfiguration
        {
            $this->enabled = $enabled;
            return $this;
        }

        /**
         * Retrieves the bot token used to authenticate with the Telegram Bot API.
         *
         * @return string|null Returns the bot token, or null if not set.
         */
        public function getBotToken(): ?string
        {
            return $this->botToken;
        }

        /**
         * Sets the bot token used to authenticate with the Telegram Bot API.
         *
         * @param string|null $botToken The bot token to set, as issued by @BotFather.
         * @return TelegramConfiguration Returns the current instance.
         */
        public function setBotToken(?string $botToken): TelegramConfiguration
        {
            $this->botToken = $botToken;
            return $this;
        }

        /**
         * Retrieves the chat ID that notifications are sent to.
         *
         * @return string|null Returns the chat ID, or null if not set.
         */
        public function getChatId(): ?string
        {
            return $this->chatId;
        }

        /**
         * Sets the chat ID that notifications are sent to, this can be a numeric chat ID (e.g. -1001234567890) or the
         * username of a public channel/group (e.g. @mychannel).
         *
         * @param string|int|null $chatId The chat ID to set.
         * @return TelegramConfiguration Returns the current instance.
         */
        public function setChatId(string|int|null $chatId): TelegramConfiguration
        {
            $this->chatId = $chatId === null ? null : (string)$chatId;
            return $this;
        }

        /**
         * Retrieves the topic (message thread) ID that notifications are sent to.
         *
         * @return int|null Returns the topic ID, or null if notifications are sent to the main chat.
         */
        public function getTopicId(): ?int
        {
            return $this->topicId;
        }

        /**
         * Sets the topic (message thread) ID that notifications are sent to, only applicable to forum supergroups.
         *
         * @param int|null $topicId The topic ID to set, or null to send to the main chat.
         * @return TelegramConfiguration Returns the current instance.
         */
        public function setTopicId(?int $topicId): TelegramConfiguration
        {
            $this->topicId = $topicId;
            return $this;
        }

        /**
         * Retrieves the base URL of the Telegram Bot API.
         *
         * @return string Returns the base URL of the Telegram Bot API.
         */
        public function getApiEndpoint(): string
        {
            return $this->apiEndpoint;
        }

        /**
         * Sets the base URL of the Telegram Bot API, useful when using a self-hosted Bot API server.
         *
         * @param string $apiEndpoint The base URL to set.
         * @return TelegramConfiguration Returns the current instance.
         */
        public function setApiEndpoint(string $apiEndpoint): TelegramConfiguration
        {
            $this->apiEndpoint = rtrim($apiEndpoint, '/');
            return $this;
        }

        /**
         * Retrieves the minimum log level required for an event to be sent to Telegram.
         *
         * @return LogLevel Returns the minimum log level.
         */
        public function getLogLevel(): LogLevel
        {
            return $this->logLevel;
        }

        /**
         * Sets the minimum log level required for an event to be sent to Telegram, events are still subject to the
         * global log level filter.
         *
         * @param LogLevel $logLevel The minimum log level to set.
         * @return TelegramConfiguration Returns the current instance.
         */
        public function setLogLevel(LogLevel $logLevel): TelegramConfiguration
        {
            $this->logLevel = $logLevel;
            return $this;
        }

        /**
         * Retrieves whether notifications are sent silently.
         *
         * @return bool Returns true if notifications are sent silently, false otherwise.
         */
        public function isDisableNotification(): bool
        {
            return $this->disableNotification;
        }

        /**
         * Sets whether notifications are sent silently (users receive a notification with no sound).
         *
         * @param bool $disableNotification True to send notifications silently.
         * @return TelegramConfiguration Returns the current instance.
         */
        public function setDisableNotification(bool $disableNotification): TelegramConfiguration
        {
            $this->disableNotification = $disableNotification;
            return $this;
        }

        /**
         * Retrieves the timestamp format of the Telegram configuration.
         *
         * @return TimestampFormat Returns the timestamp format.
         */
        public function getTimestampFormat(): TimestampFormat
        {
            return $this->timestampFormat;
        }

        /**
         * Sets the timestamp format of the Telegram configuration.
         *
         * @param TimestampFormat $timestampFormat The timestamp format to set.
         * @return TelegramConfiguration Returns the current instance.
         */
        public function setTimestampFormat(TimestampFormat $timestampFormat): TelegramConfiguration
        {
            $this->timestampFormat = $timestampFormat;
            return $this;
        }

        /**
         * Retrieves the trace format of the Telegram configuration.
         *
         * @return TraceFormat Returns the trace format.
         */
        public function getTraceFormat(): TraceFormat
        {
            return $this->traceFormat;
        }

        /**
         * Sets the trace format of the Telegram configuration.
         *
         * @param TraceFormat $traceFormat The trace format to set.
         * @return TelegramConfiguration Returns the current instance.
         */
        public function setTraceFormat(TraceFormat $traceFormat): TelegramConfiguration
        {
            $this->traceFormat = $traceFormat;
            return $this;
        }
    }
