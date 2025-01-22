<?php

    namespace LogLib2;

    require __DIR__ . DIRECTORY_SEPARATOR . 'autoload_patch.php';

    use LogLib2\Classes\LogHandlers\ConsoleHandler;
    use LogLib2\Classes\LogHandlers\DescriptorHandler;
    use LogLib2\Classes\LogHandlers\FileHandler;
    use LogLib2\Classes\LogHandlers\HttpHandler;
    use LogLib2\Classes\LogHandlers\TcpHandler;
    use LogLib2\Classes\LogHandlers\UdpHandler;
    use LogLib2\Classes\Utilities;
    use LogLib2\Enums\AnsiFormat;
    use LogLib2\Enums\LogFormat;
    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Configurations\ConsoleConfiguration;
    use LogLib2\Objects\Configurations\DescriptorConfiguration;
    use LogLib2\Objects\Configurations\FileConfiguration;
    use LogLib2\Objects\Configurations\HttpConfiguration;
    use LogLib2\Objects\Configurations\TcpConfiguration;
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
        private static ?UdpConfiguration $defaultUdpConfiguration=null;
        private static bool $handlersRegistered=false;
        private static ?Logger $runtimeLogger=null;
        private static int $backtraceLevel=3;

        private Application $application;

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
         * @return Application The Application instance used by the Logger.
         */
        public function debug(string $message): void
        {
            $this->handleEvent($this->createEvent(LogLevel::DEBUG, $message));
        }

        /**
         * Logs a verbose message with the provided message.
         *
         * @param string $message The message to log.
         */
        public function verbose(string $message): void
        {
            $this->handleEvent($this->createEvent(LogLevel::VERBOSE, $message));
        }

        /**
         * Logs an informational message with the provided message.
         *
         * @param string $message The message to log.
         */
        public function info(string $message): void
        {
            $this->handleEvent($this->createEvent(LogLevel::INFO, $message));
        }

        /**
         * Logs a warning message with the provided message.
         *
         * @param string $message The message to log.
         */
        public function warning(string $message, null|ExceptionDetails|Throwable $e=null): void
        {
            $this->handleEvent($this->createEvent(LogLevel::WARNING, $message, $e));
        }

        /**
         * Logs an error message with the provided message.
         *
         * @param string $message The message to log.
         */
        public function error(string $message, null|ExceptionDetails|Throwable $e=null): void
        {
            $this->handleEvent($this->createEvent(LogLevel::ERROR, $message, $e));
        }

        /**
         * Logs a critical message with the provided message.
         *
         * @param string $message The message to log.
         */
        public function critical(string $message, null|ExceptionDetails|Throwable $e=null): void
        {
            $this->handleEvent($this->createEvent(LogLevel::CRITICAL, $message, $e));
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
            // Return early if the given LogLevel not allowed to be processed with the application's given log level.
            if(!Utilities::getEnvironmentLogLevel()->levelAllowed($event->getLevel()))
            {
                return;
            }

            // Handle the event with the appropriate log handlers.
            if($this->application->getConsoleConfiguration()->isEnabled() && ConsoleHandler::isAvailable($this->application))
            {
                ConsoleHandler::handleEvent($this->application, $event);
            }

            if($this->application->getDescriptorConfiguration()->isEnabled() && DescriptorHandler::isAvailable($this->application))
            {
                DescriptorHandler::handleEvent($this->application, $event);
            }

            if($this->application->getFileConfiguration()->isEnabled() && FileHandler::isAvailable($this->application))
            {
                FileHandler::handleEvent($this->application, $event);
            }

            if($this->application->getHttpConfiguration()->isEnabled() && HttpHandler::isAvailable($this->application))
            {
                HttpHandler::handleEvent($this->application, $event);
            }

            if($this->application->getTcpConfiguration()->isEnabled() && TcpHandler::isAvailable($this->application))
            {
                TcpHandler::handleEvent($this->application, $event);
            }

            if($this->application->getUdpConfiguration()->isEnabled() && UdpHandler::isAvailable($this->application))
            {
                UdpHandler::handleEvent($this->application, $event);
            }
        }

        /**
         * Retrieves the availability of the log handlers.
         *
         * @return array The availability of the log handlers.
         */
        public function getAvailability(): array
        {
            return [
                ConsoleHandler::class => ConsoleHandler::isAvailable($this->application),
                DescriptorConfiguration::class => DescriptorHandler::isAvailable($this->application),
                FileHandler::class => FileHandler::isAvailable($this->application),
                HttpHandler::class => HttpHandler::isAvailable($this->application),
                TcpHandler::class => TcpHandler::isAvailable($this->application),
                UdpHandler::class => UdpHandler::isAvailable($this->application)
            ];
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

            // Register to catch fatal errors.
            register_shutdown_function(function() use ($logger)
            {
                $error = error_get_last();

                if($error !== null)
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