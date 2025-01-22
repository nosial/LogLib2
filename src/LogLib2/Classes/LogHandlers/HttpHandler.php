<?php

    namespace LogLib2\Classes\LogHandlers;

    use LogLib2\Enums\LogFormat;
    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Event;

    class HttpHandler implements LogHandlerInterface
    {
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

            return true;
        }

        /**
         * @inheritDoc
         */
        public static function handleEvent(Application $application, Event $event): void
        {
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

            // Note, no exception handling is done here. If the HTTP request fails, it will fail silently.
            $ch = curl_init($application->getHttpConfiguration()->getEndpoint());
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [$header]);
            curl_exec($ch);
            curl_close($ch);
        }
    }