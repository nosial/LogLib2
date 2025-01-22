<?php

    namespace LogLib2\Classes\LogHandlers;

    use DateTime;
    use LogLib2\Classes\FileLock;
    use LogLib2\Classes\Utilities;
    use LogLib2\Enums\LogFormat;
    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Event;

    class FileHandler implements LogHandlerInterface
    {
        private const string CSV_HEADERS = "timestamp,level,message,trace,exception";
        private static array $fileLocks = [];

        /**
         * @inheritDoc
         */
        public static function isAvailable(Application $application): bool
        {
            $logPath = $application->getFileConfiguration()->getLogPath();
            $filePath = self::getLogFilePath($application);

            // If the log path is not writable nor a dir, return false.
            if(!is_writable($logPath) || !is_dir($logPath))
            {
                return false;
            }

            // If the log file does not exist, create it.
            if(!file_exists($filePath))
            {
                if(!@touch($filePath) || !@chmod($filePath, $application->getFileConfiguration()->getDefaultPermissions()))
                {
                    return false;
                }

                // Create the headers for the log file if required.
                if($application->getFileConfiguration()->getLogFormat() == LogFormat::CSV)
                {
                    $temporaryLock = new FileLock($filePath, $application->getFileConfiguration()->getDefaultPermissions());
                    $temporaryLock->append(self::CSV_HEADERS . PHP_EOL);
                    unset($temporaryLock);
                }
            }

            // If the file lock does not exist, create it to allow for file locking & writing.
            if(!isset(self::$fileLocks[$application->getName()]))
            {
                self::$fileLocks[$application->getName()] = new FileLock($filePath, $application->getFileConfiguration()->getDefaultPermissions());
            }

            return true;
        }

        /**
         * @inheritDoc
         */
        public static function handleEvent(Application $application, Event $event): void
        {
            $message = $application->getFileConfiguration()->getLogFormat()->format(
                $application->getFileConfiguration()->getTimestampFormat(), $application->getFileConfiguration()->getTraceFormat(), $event
            );

            if($application->getFileConfiguration()->isAppendNewline())
            {
                $message .= PHP_EOL;
            }

            self::$fileLocks[$application->getName()]->append($message);
        }

        /**
         * Retrieves the log file path for the given application.
         *
         * @param Application $application The application to retrieve the log file path for.
         *
         * @return string The log file path for the given application.
         */
        private static function getLogFilePath(Application $application): string
        {
            $extension = match($application->getFileConfiguration()->getLogFormat())
            {
                LogFormat::JSONL => 'jsonl',
                LogFormat::CSV => 'csv',
                LogFormat::TXT => 'txt',
                LogFormat::XML => 'xml',
                LogFormat::HTML => 'html',
            };

            return Utilities::getEnvironmentLogPath($application) . DIRECTORY_SEPARATOR .
                sprintf('%s-%s.%s', Utilities::sanitizeFileName($application->getName()), (new DateTime())->format('Y-m-d'), $extension);
        }
    }