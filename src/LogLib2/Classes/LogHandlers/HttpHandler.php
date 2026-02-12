<?php

    namespace LogLib2\Classes\LogHandlers;

    use LogLib2\Enums\LogFormat;
    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Event;

    class HttpHandler implements LogHandlerInterface
    {
        private static array $curlHandles = [];
        private static array $failedEndpoints = [];

        /**
         * Checks if the current PHP environment is available for execution in CLI mode.
         *
         * @return bool True if the PHP environment is running in CLI mode, false otherwise.
         */
        public static function isAvailable(Application $application): bool
        {
            if(!function_exists('curl_init'))
            {
                return false;
            }

            if(!filter_var($application->getHttpConfiguration()->getEndpoint(), FILTER_VALIDATE_URL))
            {
                return false;
            }

            $endpoint = $application->getHttpConfiguration()->getEndpoint();
            if(isset(self::$failedEndpoints[$endpoint]))
            {
                return false;
            }

            return true;
        }

        /**
         * @inheritDoc
         */
        public static function handleEvent(Application $application, Event $event): void
        {
            $endpoint = $application->getHttpConfiguration()->getEndpoint();
            
            if(isset(self::$failedEndpoints[$endpoint]))
            {
                return;
            }

            $header = match($application->getHttpConfiguration()->getLogFormat())
            {
                LogFormat::JSONL => 'Content-Type: application/json',
                LogFormat::CSV => 'Content-Type: text/csv',
                LogFormat::TXT => 'Content-Type: text/plain',
                LogFormat::XML => 'Content-Type: text/xml',
                LogFormat::HTML => 'Content-Type: text/html',
            };

            $message = $application->getHttpConfiguration()->getLogFormat()->format(
                $application->getHttpConfiguration()->getTimestampFormat(), $application->getHttpConfiguration()->getTraceFormat(), $event
            );

            if($application->getHttpConfiguration()->isAppendNewline())
            {
                $message .= PHP_EOL;
            }

            if(!isset(self::$curlHandles[$endpoint]))
            {
                self::$curlHandles[$endpoint] = curl_init($endpoint);
                if(self::$curlHandles[$endpoint] === false)
                {
                    self::$failedEndpoints[$endpoint] = true;
                    self::$curlHandles[$endpoint] = null;
                    return;
                }
                curl_setopt(self::$curlHandles[$endpoint], CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt(self::$curlHandles[$endpoint], CURLOPT_RETURNTRANSFER, true);
                curl_setopt(self::$curlHandles[$endpoint], CURLOPT_TIMEOUT_MS, 5000);
            }

            if(self::$curlHandles[$endpoint] === null)
            {
                return;
            }

            curl_setopt(self::$curlHandles[$endpoint], CURLOPT_POSTFIELDS, $message);
            curl_setopt(self::$curlHandles[$endpoint], CURLOPT_HTTPHEADER, [$header]);
            
            if(@curl_exec(self::$curlHandles[$endpoint]) === false)
            {
                self::$failedEndpoints[$endpoint] = true;
                curl_close(self::$curlHandles[$endpoint]);
                self::$curlHandles[$endpoint] = null;
            }
        }
    }