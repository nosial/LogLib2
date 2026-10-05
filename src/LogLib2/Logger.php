<?php

    namespace LogLib2;

    require __DIR__ . DIRECTORY_SEPARATOR . 'autoload_patch.php';

    use LogLib2\Classes\LogHandlers\ConsoleHandler;
    use LogLib2\Classes\LogHandlers\DescriptorHandler;
    use LogLib2\Classes\LogHandlers\DiscordHandler;
    use LogLib2\Classes\LogHandlers\EmailHandler;
    use LogLib2\Classes\LogHandlers\FileHandler;
    use LogLib2\Classes\LogHandlers\HttpHandler;
    use LogLib2\Classes\LogHandlers\TcpHandler;
    use LogLib2\Classes\LogHandlers\TelegramHandler;
    use LogLib2\Classes\LogHandlers\UdpHandler;
    use LogLib2\Classes\Utilities;
    use LogLib2\Enums\AnsiFormat;
    use LogLib2\Enums\LogFormat;
    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\SmtpEncryption;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;
    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Configurations\ConsoleConfiguration;
    use LogLib2\Objects\Configurations\DescriptorConfiguration;
    use LogLib2\Objects\Configurations\DiscordConfiguration;
    use LogLib2\Objects\Configurations\EmailConfiguration;
    use LogLib2\Objects\Configurations\FileConfiguration;
    use LogLib2\Objects\Configurations\HttpConfiguration;
    use LogLib2\Objects\Configurations\TcpConfiguration;
    use LogLib2\Objects\Configurations\TelegramConfiguration;
    use LogLib2\Objects\Configurations\UdpConfiguration;
    use LogLib2\Objects\Event;
    use LogLib2\Objects\ExceptionDetails;
    use Throwable;

    class Logger
    {
        private static ?ConsoleConfiguration $defaultConsoleConfiguration=null;
        private static ?DescriptorConfiguration $defaultDescriptorConfiguration=null;
        private static ?FileConfiguration $defaultFileConfiguration=null;
        private static ?HttpConfiguration $defaultHttpConfiguration=null;
        private static ?TcpConfiguration $defaultTcpConfiguration=null;
        private static ?TelegramConfiguration $defaultTelegramConfiguration=null;
        private static ?EmailConfiguration $defaultEmailConfiguration=null;
        private static ?DiscordConfiguration $defaultDiscordConfiguration=null;
        private static ?UdpConfiguration $defaultUdpConfiguration=null;
        private static bool $handlersRegistered=false;
        private static ?Logger $runtimeLogger=null;
        private static int $backtraceLevel=3;
        private static ?LogLevel $environmentLogLevel=null;
        private static bool $dispatching=false;
        private static array $reportedFailures=[];

        private Application $application;
        private array $handlerAvailability = [];
        private array $failedHandlers = [];

        /**
         * Constructs a new instance with the provided application name.
         *
         * @param string $application The application name
         */
        public function __construct(string $application)
        {
            $this->application = new Application($application);
            $this->application->setConsoleConfiguration(self::getDefaultConsoleConfiguration());
            $this->application->setDescriptorConfiguration(self::getDefaultDescriptorConfiguration());
            $this->application->setFileConfiguration(self::getDefaultFileConfiguration());
            $this->application->setHttpConfiguration(self::getDefaultHttpConfiguration());
            $this->application->setTcpConfiguration(self::getDefaultTcpConfiguration());
            $this->application->setTelegramConfiguration(self::getDefaultTelegramConfiguration());
            $this->application->setEmailConfiguration(self::getDefaultEmailConfiguration());
            $this->application->setDiscordConfiguration(self::getDefaultDiscordConfiguration());
            $this->application->setUdpConfiguration(self::getDefaultUdpConfiguration());
        }

        /**
         * Retrieves the Application instance used by the Logger.
         *
         * @return Application The Application instance used by the Logger.
         */
        public function getApplication(): Application
        {
            return $this->application;
        }

        /**
         * Retrieves the Application instance used by the Logger.
         *
         * @param string $message
         * @return void The Application instance used by the Logger.
         */
        public function debug(string $message): void
        {
            try
            {
                $this->handleEvent($this->createEvent(LogLevel::DEBUG, $message));
            }
            catch(Throwable $t)
            {
                self::reportFailure(self::class, $t);
            }
        }

        /**
         * Logs a verbose message with the provided message.
         *
         * @param string $message The message to log.
         */
        public function verbose(string $message): void
        {
            try
            {
                $this->handleEvent($this->createEvent(LogLevel::VERBOSE, $message));
            }
            catch(Throwable $t)
            {
                self::reportFailure(self::class, $t);
            }
        }

        /**
         * Logs an informational message with the provided message.
         *
         * @param string $message The message to log.
         */
        public function info(string $message): void
        {
            try
            {
                $this->handleEvent($this->createEvent(LogLevel::INFO, $message));
            }
            catch(Throwable $t)
            {
                self::reportFailure(self::class, $t);
            }
        }

        /**
         * Logs a warning message with the provided message.
         *
         * @param string $message The message to log.
         */
        public function warning(string $message, null|ExceptionDetails|Throwable $e=null): void
        {
            try
            {
                $this->handleEvent($this->createEvent(LogLevel::WARNING, $message, $e));
            }
            catch(Throwable $t)
            {
                self::reportFailure(self::class, $t);
            }
        }

        /**
         * Logs an error message with the provided message.
         *
         * @param string $message The message to log.
         */
        public function error(string $message, null|ExceptionDetails|Throwable $e=null): void
        {
            try
            {
                $this->handleEvent($this->createEvent(LogLevel::ERROR, $message, $e));
            }
            catch(Throwable $t)
            {
                self::reportFailure(self::class, $t);
            }
        }

        /**
         * Logs a critical message with the provided message.
         *
         * @param string $message The message to log.
         */
        public function critical(string $message, null|ExceptionDetails|Throwable $e=null): void
        {
            try
            {
                $this->handleEvent($this->createEvent(LogLevel::CRITICAL, $message, $e));
            }
            catch(Throwable $t)
            {
                self::reportFailure(self::class, $t);
            }
        }

        /**
         * Logs an alert message with the provided message.
         *
         * @param string $message The message to log.
         */
        private function createEvent(LogLevel $level, string $message, null|ExceptionDetails|Throwable $e=null): Event
        {
            return new Event($this->application->getName(), $level, $message, Utilities::getBackTrace(Logger::getBacktraceLevel()), time(), $e);
        }

        /**
         * Handles the provided event by passing it to the appropriate log handlers.
         *
         * @param Event $event The event to handle.
         */
        private function handleEvent(Event $event): void
        {
            // Events raised while another event is being handled (e.g. a PHP warning emitted by a handler and caught
            // by the runtime error handler) are dropped, otherwise a failing handler could recurse until the stack
            // is exhausted, which cannot be recovered from.
            if(self::$dispatching)
            {
                return;
            }

            self::$dispatching = true;

            try
            {
                if(self::$environmentLogLevel === null)
                {
                    self::$environmentLogLevel = Utilities::getEnvironmentLogLevel();
                }

                if(!self::$environmentLogLevel->levelAllowed($event->getLevel()))
                {
                    return;
                }

                $this->dispatch(ConsoleHandler::class, $this->application->getConsoleConfiguration()->isEnabled(), $event, true);
                $this->dispatch(DescriptorHandler::class, $this->application->getDescriptorConfiguration()->isEnabled(), $event, true);
                $this->dispatch(FileHandler::class, $this->application->getFileConfiguration()->isEnabled(), $event, true);
                $this->dispatch(HttpHandler::class, $this->application->getHttpConfiguration()->isEnabled(), $event);
                $this->dispatch(TcpHandler::class, $this->application->getTcpConfiguration()->isEnabled(), $event);
                $this->dispatch(UdpHandler::class, $this->application->getUdpConfiguration()->isEnabled(), $event);
                $this->dispatch(TelegramHandler::class, $this->application->getTelegramConfiguration()->isEnabled(), $event);
                $this->dispatch(EmailHandler::class, $this->application->getEmailConfiguration()->isEnabled(), $event);
                $this->dispatch(DiscordHandler::class, $this->application->getDiscordConfiguration()->isEnabled(), $event);
            }
            finally
            {
                self::$dispatching = false;
            }
        }

        /**
         * Passes the event to a single log handler, isolating the remaining handlers and the caller from any failure.
         * A handler that throws is disabled for this logger instance and the failure is reported once via error_log().
         *
         * @param class-string<LogHandlerInterface> $handler The log handler class.
         * @param bool $enabled True if the handler is enabled in the configuration.
         * @param Event $event The event to handle.
         * @param bool $cacheAvailability True to only check the handler's availability once, for handlers whose
         *                                availability does not change at runtime.
         */
        private function dispatch(string $handler, bool $enabled, Event $event, bool $cacheAvailability=false): void
        {
            if(!$enabled || isset($this->failedHandlers[$handler]))
            {
                return;
            }

            try
            {
                if($cacheAvailability)
                {
                    $available = $this->handlerAvailability[$handler] ??= $handler::isAvailable($this->application);
                }
                else
                {
                    $available = $handler::isAvailable($this->application);
                }

                if($available)
                {
                    $handler::handleEvent($this->application, $event);
                }
            }
            catch(Throwable $e)
            {
                $this->failedHandlers[$handler] = true;
                self::reportFailure($handler, $e, true);
            }
        }

        /**
         * Reports a failure within the logger to PHP's error log, each source is only reported once per process.
         * error_log() is used as it does not pass through the logger or the runtime error handler.
         *
         * @param string $source The class that failed.
         * @param Throwable $e The failure.
         * @param bool $disabled True if the source has been disabled as a result of the failure.
         */
        private static function reportFailure(string $source, Throwable $e, bool $disabled=false): void
        {
            if(isset(self::$reportedFailures[$source]))
            {
                return;
            }

            self::$reportedFailures[$source] = true;
            @error_log(sprintf('LogLib2: %s failed%s: %s: %s in %s:%d',
                $source, $disabled ? ' and has been disabled for this logger' : ' to handle an event', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()
            ));
        }

        /**
         * Retrieves the availability of the log handlers.
         *
         * @return array The availability of the log handlers.
         */
        public function getAvailability(): array
        {
            $availability = [];
            foreach([ConsoleHandler::class, DescriptorHandler::class, FileHandler::class, HttpHandler::class, TcpHandler::class,
                        UdpHandler::class, TelegramHandler::class, EmailHandler::class, DiscordHandler::class] as $handler)
            {
                try
                {
                    $availability[$handler] = !isset($this->failedHandlers[$handler]) && $handler::isAvailable($this->application);
                }
                catch(Throwable)
                {
                    $availability[$handler] = false;
                }
            }

            return $availability;
        }

        /**
         * Retrieves the default ConsoleConfiguration instance.
         *
         * @return ConsoleConfiguration The default ConsoleConfiguration instance.
         */
        public static function getDefaultConsoleConfiguration(): ConsoleConfiguration
        {
            if(self::$defaultConsoleConfiguration === null)
            {
                self::$defaultConsoleConfiguration = new ConsoleConfiguration();

                // Apply environment variables to the default ConsoleConfiguration instance.
                if(getenv('LOGLIB_CONSOLE_ENABLED') !== false)
                {
                    self::$defaultConsoleConfiguration->setEnabled(filter_var(getenv('LOG_CONSOLE_ENABLED'), FILTER_VALIDATE_BOOLEAN));
                }
                if(getenv('LOGLIB_CONSOLE_DISPLAY_NAME') !== false)
                {
                    self::$defaultConsoleConfiguration->setDisplayName(filter_var(getenv('LOG_CONSOLE_DISPLAY_NAME'), FILTER_VALIDATE_BOOLEAN));
                }
                if(getenv('LOGLIB_CONSOLE_DISPLAY_LEVEL') !== false)
                {
                    self::$defaultConsoleConfiguration->setDisplayLevel(filter_var(getenv('LOG_CONSOLE_DISPLAY_LEVEL'), FILTER_VALIDATE_BOOLEAN));
                }
                if(getenv('LOGLIB_CONSOLE_ANSI_FORMAT') !== false)
                {
                    self::$defaultConsoleConfiguration->setAnsiFormat(AnsiFormat::parseFrom(getenv('LOGLIB_CONSOLE_ANSI_FORMAT')));
                }
                if(getenv('LOGLIB_CONSOLE_TRACE_FORMAT') !== false)
                {
                    self::$defaultConsoleConfiguration->setTraceFormat(TraceFormat::parseFrom(getenv('LOGLIB_CONSOLE_TRACE_FORMAT')));
                }
                if(getenv('LOGLIB_CONSOLE_TIMESTAMP_FORMAT') !== false)
                {
                    self::$defaultConsoleConfiguration->setTimestampFormat(TimestampFormat::parseFrom(getenv('LOGLIB_CONSOLE_TIMESTAMP_FORMAT')));
                }
            }

            return self::$defaultConsoleConfiguration;
        }

        /**
         * Retrieves the default DescriptorConfiguration instance.
         *
         * @return DescriptorConfiguration The default DescriptorConfiguration instance.
         */
        public static function getDefaultDescriptorConfiguration(): DescriptorConfiguration
        {
            if(self::$defaultDescriptorConfiguration === null)
            {
                self::$defaultDescriptorConfiguration = new DescriptorConfiguration();

                // Apply environment variables to the default DescriptorConfiguration instance.
                if(getenv('LOGLIB_DESCRIPTOR_ENABLED') !== false)
                {
                    self::$defaultDescriptorConfiguration->setEnabled(filter_var(getenv('LOGLIB_DESCRIPTOR_ENABLED'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_DESCRIPTOR_PATH') !== false)
                {
                    self::$defaultDescriptorConfiguration->setDescriptor(getenv('LOGLIB_DESCRIPTOR_PATH'));
                }

                if(getenv('LOGLIB_DESCRIPTOR_APPEND_NEWLINE') !== false)
                {
                    self::$defaultDescriptorConfiguration->setAppendNewline(filter_var(getenv('LOGLIB_DESCRIPTOR_APPEND_NEWLINE'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_DESCRIPTOR_LOG_FORMAT') !== false)
                {
                    self::$defaultDescriptorConfiguration->setLogFormat(LogFormat::parseFrom(getenv('LOGLIB_DESCRIPTOR_LOG_FORMAT')));
                }

                if(getenv('LOGLIB_DESCRIPTOR_TIMESTAMP_FORMAT') !== false)
                {
                    self::$defaultDescriptorConfiguration->setTimestampFormat(TimestampFormat::parseFrom(getenv('LOGLIB_DESCRIPTOR_TIMESTAMP_FORMAT')));
                }

                if(getenv('LOGLIB_DESCRIPTOR_TRACE_FORMAT') !== false)
                {
                    self::$defaultDescriptorConfiguration->setTraceFormat(TraceFormat::parseFrom(getenv('LOGLIB_DESCRIPTOR_TRACE_FORMAT')));
                }
            }

            return self::$defaultDescriptorConfiguration;
        }

        /**
         * Retrieves the default FileConfiguration instance.
         *
         * @return FileConfiguration The default FileConfiguration instance.
         */
        public static function getDefaultFileConfiguration(): FileConfiguration
        {
            if(self::$defaultFileConfiguration === null)
            {
                self::$defaultFileConfiguration = new FileConfiguration();

                // Apply environment variables to the default FileConfiguration instance.
                if(getenv('LOGLIB_FILE_ENABLED') !== false)
                {
                    self::$defaultFileConfiguration->setEnabled(filter_var(getenv('LOGLIB_FILE_ENABLED'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_FILE_DEFAULT_PERMISSIONS') !== false)
                {
                    $permissions = octdec(filter_var(getenv('LOGLIB_FILE_DEFAULT_PERMISSIONS'), FILTER_VALIDATE_INT));
                    if($permissions !== false)
                    {
                        self::$defaultFileConfiguration->setDefaultPermissions($permissions);
                    }
                }

                if(getenv('LOGLIB_FILE_PATH') !== false)
                {
                    // Parse magic constants in the file path.
                    $path = getenv('LOGLIB_FILE_PATH');
                    $path = str_ireplace('%CWD%', getcwd(), $path);
                    $path = str_ireplace('%HOME%', getenv('HOME') ?? sys_get_temp_dir(), $path);
                    $path = str_ireplace('%TMP%', sys_get_temp_dir(), $path);
                    $path = str_ireplace('%TEMP%', sys_get_temp_dir(), $path);

                    if(!is_dir($path))
                    {
                        @mkdir($path, self::$defaultFileConfiguration->getDefaultPermissions(), true);
                    }

                    self::$defaultFileConfiguration->setLogPath($path);
                }

                if(getenv('LOGLIB_FILE_APPEND_NEWLINE') !== false)
                {
                    self::$defaultFileConfiguration->setAppendNewline(filter_var(getenv('LOGLIB_FILE_APPEND_NEWLINE'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_FILE_LOG_FORMAT') !== false)
                {
                    self::$defaultFileConfiguration->setLogFormat(LogFormat::parseFrom(getenv('LOGLIB_FILE_LOG_FORMAT')));
                }

                if(getenv('LOGLIB_FILE_TIMESTAMP_FORMAT') !== false)
                {
                    self::$defaultFileConfiguration->setTimestampFormat(TimestampFormat::parseFrom(getenv('LOGLIB_FILE_TIMESTAMP_FORMAT')));
                }

                if(getenv('LOGLIB_FILE_TRACE_FORMAT') !== false)
                {
                    self::$defaultFileConfiguration->setTraceFormat(TraceFormat::parseFrom(getenv('LOGLIB_FILE_TRACE_FORMAT')));
                }
            }

            return self::$defaultFileConfiguration;
        }

        /**
         * Retrieves the default HttpConfiguration instance.
         *
         * @return HttpConfiguration The default HttpConfiguration instance.
         */
        public static function getDefaultHttpConfiguration(): HttpConfiguration
        {
            if(self::$defaultHttpConfiguration === null)
            {
                self::$defaultHttpConfiguration = new HttpConfiguration();

                // Apply environment variables to the default HttpConfiguration instance.
                if(getenv('LOGLIB_HTTP_ENABLED') !== false)
                {
                    self::$defaultHttpConfiguration->setEnabled(filter_var(getenv('LOGLIB_HTTP_ENABLED'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_HTTP_ENDPOINT') !== false)
                {
                    self::$defaultHttpConfiguration->setEndpoint(getenv('LOGLIB_HTTP_ENDPOINT'));
                }

                if(getenv('LOGLIB_HTTP_LOG_FORMAT') !== false)
                {
                    self::$defaultHttpConfiguration->setLogFormat(LogFormat::parseFrom(getenv('LOGLIB_HTTP_LOG_FORMAT')));
                }

                if(getenv('LOGLIB_HTTP_TIMESTAMP_FORMAT') !== false)
                {
                    self::$defaultHttpConfiguration->setTimestampFormat(TimestampFormat::parseFrom(getenv('LOGLIB_HTTP_TIMESTAMP_FORMAT')));
                }

                if(getenv('LOGLIB_HTTP_TRACE_FORMAT') !== false)
                {
                    self::$defaultHttpConfiguration->setTraceFormat(TraceFormat::parseFrom(getenv('LOGLIB_HTTP_TRACE_FORMAT')));
                }
            }

            return self::$defaultHttpConfiguration;
        }

        /**
         * Retrieves the default TcpConfiguration instance.
         *
         * @return TcpConfiguration The default TcpConfiguration instance.
         */
        public static function getDefaultTcpConfiguration(): TcpConfiguration
        {
            if(self::$defaultTcpConfiguration === null)
            {
                self::$defaultTcpConfiguration = new TcpConfiguration();

                // Apply environment variables to the default TcpConfiguration instance.
                if(getenv('LOGLIB_TCP_ENABLED') !== false)
                {
                    self::$defaultTcpConfiguration->setEnabled(filter_var(getenv('LOGLIB_TCP_ENABLED'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_TCP_HOST') !== false)
                {
                    self::$defaultTcpConfiguration->setHost(getenv('LOGLIB_TCP_HOST'));
                }

                if(getenv('LOGLIB_TCP_PORT') !== false)
                {
                    self::$defaultTcpConfiguration->setPort((int)getenv('LOGLIB_TCP_PORT'));
                }

                if(getenv('LOGLIB_TCP_APPEND_NEWLINE') !== false)
                {
                    self::$defaultTcpConfiguration->setAppendNewline(filter_var(getenv('LOGLIB_TCP_APPEND_NEWLINE'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_TCP_LOG_FORMAT') !== false)
                {
                    self::$defaultTcpConfiguration->setLogFormat(LogFormat::parseFrom(getenv('LOGLIB_TCP_LOG_FORMAT')));
                }

                if(getenv('LOGLIB_TCP_TIMESTAMP_FORMAT') !== false)
                {
                    self::$defaultTcpConfiguration->setTimestampFormat(TimestampFormat::parseFrom(getenv('LOGLIB_TCP_TIMESTAMP_FORMAT')));
                }
            }

            return self::$defaultTcpConfiguration;
        }

        /**
         * Retrieves the default UdpConfiguration instance.
         *
         * @return UdpConfiguration The default UdpConfiguration instance.
         */
        public static function getDefaultUdpConfiguration(): UdpConfiguration
        {
            if(self::$defaultUdpConfiguration === null)
            {
                self::$defaultUdpConfiguration = new UdpConfiguration();

                // Apply environment variables to the default UdpConfiguration instance.
                if(getenv('LOGLIB_UDP_ENABLED') !== false)
                {
                    self::$defaultUdpConfiguration->setEnabled(filter_var(getenv('LOGLIB_UDP_ENABLED'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_UDP_HOST') !== false)
                {
                    self::$defaultUdpConfiguration->setHost(getenv('LOGLIB_UDP_HOST'));
                }

                if(getenv('LOGLIB_UDP_PORT') !== false)
                {
                    self::$defaultUdpConfiguration->setPort((int)getenv('LOGLIB_UDP_PORT'));
                }

                if(getenv('LOGLIB_UDP_APPEND_NEWLINE') !== false)
                {
                    self::$defaultUdpConfiguration->setAppendNewline(filter_var(getenv('LOGLIB_UDP_APPEND_NEWLINE'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_UDP_LOG_FORMAT') !== false)
                {
                    self::$defaultUdpConfiguration->setLogFormat(LogFormat::parseFrom(getenv('LOGLIB_UDP_LOG_FORMAT')));
                }

                if(getenv('LOGLIB_UDP_TIMESTAMP_FORMAT') !== false)
                {
                    self::$defaultUdpConfiguration->setTimestampFormat(TimestampFormat::parseFrom(getenv('LOGLIB_UDP_TIMESTAMP_FORMAT')));
                }

                if(getenv('LOGLIB_UDP_TRACE_FORMAT') !== false)
                {
                    self::$defaultUdpConfiguration->setTraceFormat(TraceFormat::parseFrom(getenv('LOGLIB_UDP_TRACE_FORMAT')));
                }
            }

            return self::$defaultUdpConfiguration;
        }

        /**
         * Retrieves the default TelegramConfiguration instance.
         *
         * @return TelegramConfiguration The default TelegramConfiguration instance.
         */
        public static function getDefaultTelegramConfiguration(): TelegramConfiguration
        {
            if(self::$defaultTelegramConfiguration === null)
            {
                self::$defaultTelegramConfiguration = new TelegramConfiguration();

                // Apply environment variables to the default TelegramConfiguration instance.
                if(getenv('LOGLIB_TELEGRAM_ENABLED') !== false)
                {
                    self::$defaultTelegramConfiguration->setEnabled(filter_var(getenv('LOGLIB_TELEGRAM_ENABLED'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_TELEGRAM_BOT_TOKEN') !== false)
                {
                    self::$defaultTelegramConfiguration->setBotToken(getenv('LOGLIB_TELEGRAM_BOT_TOKEN'));
                }

                if(getenv('LOGLIB_TELEGRAM_CHAT_ID') !== false)
                {
                    self::$defaultTelegramConfiguration->setChatId(getenv('LOGLIB_TELEGRAM_CHAT_ID'));
                }

                if(getenv('LOGLIB_TELEGRAM_TOPIC_ID') !== false && getenv('LOGLIB_TELEGRAM_TOPIC_ID') !== '')
                {
                    self::$defaultTelegramConfiguration->setTopicId((int)getenv('LOGLIB_TELEGRAM_TOPIC_ID'));
                }

                if(getenv('LOGLIB_TELEGRAM_API_ENDPOINT') !== false)
                {
                    self::$defaultTelegramConfiguration->setApiEndpoint(getenv('LOGLIB_TELEGRAM_API_ENDPOINT'));
                }

                if(getenv('LOGLIB_TELEGRAM_LOG_LEVEL') !== false)
                {
                    self::$defaultTelegramConfiguration->setLogLevel(LogLevel::parseFrom(getenv('LOGLIB_TELEGRAM_LOG_LEVEL')));
                }

                if(getenv('LOGLIB_TELEGRAM_DISABLE_NOTIFICATION') !== false)
                {
                    self::$defaultTelegramConfiguration->setDisableNotification(filter_var(getenv('LOGLIB_TELEGRAM_DISABLE_NOTIFICATION'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_TELEGRAM_TIMESTAMP_FORMAT') !== false)
                {
                    self::$defaultTelegramConfiguration->setTimestampFormat(TimestampFormat::parseFrom(getenv('LOGLIB_TELEGRAM_TIMESTAMP_FORMAT')));
                }

                if(getenv('LOGLIB_TELEGRAM_TRACE_FORMAT') !== false)
                {
                    self::$defaultTelegramConfiguration->setTraceFormat(TraceFormat::parseFrom(getenv('LOGLIB_TELEGRAM_TRACE_FORMAT')));
                }
            }

            return self::$defaultTelegramConfiguration;
        }

        /**
         * Retrieves the default EmailConfiguration instance.
         *
         * @return EmailConfiguration The default EmailConfiguration instance.
         */
        public static function getDefaultEmailConfiguration(): EmailConfiguration
        {
            if(self::$defaultEmailConfiguration === null)
            {
                self::$defaultEmailConfiguration = new EmailConfiguration();

                // Apply environment variables to the default EmailConfiguration instance.
                if(getenv('LOGLIB_EMAIL_ENABLED') !== false)
                {
                    self::$defaultEmailConfiguration->setEnabled(filter_var(getenv('LOGLIB_EMAIL_ENABLED'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_EMAIL_HOST') !== false)
                {
                    self::$defaultEmailConfiguration->setHost(getenv('LOGLIB_EMAIL_HOST'));
                }

                if(getenv('LOGLIB_EMAIL_PORT') !== false)
                {
                    self::$defaultEmailConfiguration->setPort((int)getenv('LOGLIB_EMAIL_PORT'));
                }

                if(getenv('LOGLIB_EMAIL_ENCRYPTION') !== false)
                {
                    self::$defaultEmailConfiguration->setEncryption(SmtpEncryption::parseFrom(getenv('LOGLIB_EMAIL_ENCRYPTION')));
                }

                if(getenv('LOGLIB_EMAIL_VERIFY_PEER') !== false)
                {
                    self::$defaultEmailConfiguration->setVerifyPeer(filter_var(getenv('LOGLIB_EMAIL_VERIFY_PEER'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_EMAIL_USERNAME') !== false)
                {
                    self::$defaultEmailConfiguration->setUsername(getenv('LOGLIB_EMAIL_USERNAME'));
                }

                if(getenv('LOGLIB_EMAIL_PASSWORD') !== false)
                {
                    self::$defaultEmailConfiguration->setPassword(getenv('LOGLIB_EMAIL_PASSWORD'));
                }

                if(getenv('LOGLIB_EMAIL_FROM_ADDRESS') !== false)
                {
                    self::$defaultEmailConfiguration->setFromAddress(getenv('LOGLIB_EMAIL_FROM_ADDRESS'));
                }

                if(getenv('LOGLIB_EMAIL_FROM_NAME') !== false)
                {
                    self::$defaultEmailConfiguration->setFromName(getenv('LOGLIB_EMAIL_FROM_NAME'));
                }

                if(getenv('LOGLIB_EMAIL_RECIPIENTS') !== false)
                {
                    self::$defaultEmailConfiguration->setRecipients(explode(',', getenv('LOGLIB_EMAIL_RECIPIENTS')));
                }

                if(getenv('LOGLIB_EMAIL_TIMEOUT') !== false)
                {
                    self::$defaultEmailConfiguration->setTimeout((int)getenv('LOGLIB_EMAIL_TIMEOUT'));
                }

                if(getenv('LOGLIB_EMAIL_LOG_LEVEL') !== false)
                {
                    self::$defaultEmailConfiguration->setLogLevel(LogLevel::parseFrom(getenv('LOGLIB_EMAIL_LOG_LEVEL')));
                }

                if(getenv('LOGLIB_EMAIL_TIMESTAMP_FORMAT') !== false)
                {
                    self::$defaultEmailConfiguration->setTimestampFormat(TimestampFormat::parseFrom(getenv('LOGLIB_EMAIL_TIMESTAMP_FORMAT')));
                }

                if(getenv('LOGLIB_EMAIL_TRACE_FORMAT') !== false)
                {
                    self::$defaultEmailConfiguration->setTraceFormat(TraceFormat::parseFrom(getenv('LOGLIB_EMAIL_TRACE_FORMAT')));
                }
            }

            return self::$defaultEmailConfiguration;
        }

        /**
         * Retrieves the default DiscordConfiguration instance.
         *
         * @return DiscordConfiguration The default DiscordConfiguration instance.
         */
        public static function getDefaultDiscordConfiguration(): DiscordConfiguration
        {
            if(self::$defaultDiscordConfiguration === null)
            {
                self::$defaultDiscordConfiguration = new DiscordConfiguration();

                // Apply environment variables to the default DiscordConfiguration instance.
                if(getenv('LOGLIB_DISCORD_ENABLED') !== false)
                {
                    self::$defaultDiscordConfiguration->setEnabled(filter_var(getenv('LOGLIB_DISCORD_ENABLED'), FILTER_VALIDATE_BOOLEAN));
                }

                if(getenv('LOGLIB_DISCORD_WEBHOOK_URL') !== false)
                {
                    self::$defaultDiscordConfiguration->setWebhookUrl(getenv('LOGLIB_DISCORD_WEBHOOK_URL'));
                }

                if(getenv('LOGLIB_DISCORD_THREAD_ID') !== false)
                {
                    self::$defaultDiscordConfiguration->setThreadId(getenv('LOGLIB_DISCORD_THREAD_ID'));
                }

                if(getenv('LOGLIB_DISCORD_USERNAME') !== false)
                {
                    self::$defaultDiscordConfiguration->setUsername(getenv('LOGLIB_DISCORD_USERNAME'));
                }

                if(getenv('LOGLIB_DISCORD_AVATAR_URL') !== false)
                {
                    self::$defaultDiscordConfiguration->setAvatarUrl(getenv('LOGLIB_DISCORD_AVATAR_URL'));
                }

                if(getenv('LOGLIB_DISCORD_LOG_LEVEL') !== false)
                {
                    self::$defaultDiscordConfiguration->setLogLevel(LogLevel::parseFrom(getenv('LOGLIB_DISCORD_LOG_LEVEL')));
                }

                if(getenv('LOGLIB_DISCORD_TRACE_FORMAT') !== false)
                {
                    self::$defaultDiscordConfiguration->setTraceFormat(TraceFormat::parseFrom(getenv('LOGLIB_DISCORD_TRACE_FORMAT')));
                }
            }

            return self::$defaultDiscordConfiguration;
        }

        /**
         * Retrieves the backtrace level.
         *
         * @return int The backtrace level.
         */
        public static function getBacktraceLevel(): int
        {
            return self::$backtraceLevel;
        }

        /**
         * Sets the backtrace level.
         *
         * @param int $backtraceLevel The backtrace level.
         */
        public static function setBacktraceLevel(int $backtraceLevel): void
        {
            self::$backtraceLevel = $backtraceLevel;
        }

        /**
         * Retrieves the runtime logger instance.
         *
         * @return Logger The runtime logger instance.
         */
        public static function getRuntimeLogger(): Logger
        {
            if(self::$runtimeLogger === null)
            {
                self::$runtimeLogger = new Logger('Runtime');
            }

            return self::$runtimeLogger;
        }

        /**
         * Registers the log handlers.
         */
        public static function registerHandlers(): void
        {
            if(self::$handlersRegistered)
            {
                return;
            }

            $logger = self::getRuntimeLogger();

            // Register to catch all PHP errors & warnings.
            set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logger)
            {
                switch($errno)
                {
                    case E_ERROR:
                    case E_CORE_ERROR:
                    case E_COMPILE_ERROR:
                    case E_USER_ERROR:
                    case E_RECOVERABLE_ERROR:
                    case E_CORE_WARNING:
                    case E_COMPILE_WARNING:
                    case E_PARSE:
                        $logger->critical($errstr, Utilities::detailsFromError($errno, $errstr, $errfile, $errline));
                        break;

                    case E_WARNING:
                    case E_USER_WARNING:
                    case E_DEPRECATED:
                    case E_USER_DEPRECATED:
                    case E_NOTICE:
                    case E_USER_NOTICE:
                    case E_STRICT:
                        $logger->warning($errstr, Utilities::detailsFromError($errno, $errstr, $errfile, $errline));
                        break;

                    default:
                        $logger->error($errstr, Utilities::detailsFromError($errno, $errstr, $errfile, $errline));
                        break;
                }
            });

            // Register to catch all uncaught exceptions.
            set_exception_handler(function(Throwable $e) use ($logger)
            {
                $logger->error($e->getMessage(), $e);
            });

            // Register to catch fatal errors, these bypass the error handler above. error_get_last() also returns
            // non-fatal errors that were already handled (e.g. warnings), which must not be reported again as critical.
            register_shutdown_function(function() use ($logger)
            {
                $error = error_get_last();

                if($error !== null && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)) !== 0)
                {
                    $logger->critical($error['message'], Utilities::detailsFromError($error['type'], $error['message'], $error['file'], $error['line']));
                }
            });

            self::$handlersRegistered = true;
        }

        /**
         * Unregisters the log handlers.
         */
        public static function unregisterHandlers(): void
        {
            if(!self::$handlersRegistered)
            {
                return;
            }

            restore_error_handler();
            restore_exception_handler();
            self::$handlersRegistered = false;
        }

        /**
         * Retrieves the registration status of the log handlers.
         *
         * @return bool Returns true if the log handlers are registered, false otherwise.
         */
        public static function isHandlersRegistered(): bool
        {
            return self::$handlersRegistered;
        }
    }