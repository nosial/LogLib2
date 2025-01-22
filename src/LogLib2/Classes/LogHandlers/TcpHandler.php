<?php

    namespace LogLib2\Classes\LogHandlers;

    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Event;

    class TcpHandler implements LogHandlerInterface
    {
        private static array $sockets = [];

        /**
         * @inheritDoc
         */
        public static function isAvailable(Application $application): bool
        {
            // Check if the TCP configuration is valid.
            if(!filter_var($application->getTcpConfiguration()->getHost(), FILTER_VALIDATE_IP))
            {
                return false;
            }

            if($application->getTcpConfiguration()->getPort() < 1 || $application->getTcpConfiguration()->getPort() > 65535)
            {
                return false;
            }

            $socketKey = $application->getTcpConfiguration()->getHost() . ':' . $application->getTcpConfiguration()->getPort();
            if(!isset(self::$sockets[$socketKey]))
            {
                self::$sockets[$socketKey] = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                if(self::$sockets[$socketKey] === false)
                {
                    unset(self::$sockets[$socketKey]);
                    return false;
                }

                if(!@socket_connect(self::$sockets[$socketKey], $application->getTcpConfiguration()->getHost(), $application->getTcpConfiguration()->getPort()))
                {
                    unset(self::$sockets[$socketKey]);
                    return false;
                }

                return true;
            }

            return true;
        }

        /**
         * @inheritDoc
         */
        public static function handleEvent(Application $application, Event $event): void
        {
            $message = $application->getTcpConfiguration()->getLogFormat()->format(
                $application->getTcpConfiguration()->getTimestampFormat(), $application->getTcpConfiguration()->getTraceFormat(), $event
            );

            if($application->getTcpConfiguration()->isAppendNewline())
            {
                $message .= PHP_EOL;
            }

            // If the message is too long, fail silently.
            if(strlen($message) > 65535)
            {
                return;
            }

            $socketKey = $application->getTcpConfiguration()->getHost() . ':' . $application->getTcpConfiguration()->getPort();
            if(!isset(self::$sockets[$socketKey]))
            {
                self::$sockets[$socketKey] = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                if(self::$sockets[$socketKey] === false)
                {
                    unset(self::$sockets[$socketKey]);
                    return;
                }

                if(!@socket_connect(self::$sockets[$socketKey], $application->getTcpConfiguration()->getHost(), $application->getTcpConfiguration()->getPort()))
                {
                    unset(self::$sockets[$socketKey]);
                    return;
                }
            }

            // If the request fails, try to reconnect and send the message again. if it fails again, fail silently.
            if(@socket_send(self::$sockets[$socketKey], $message, strlen($message), 0) === false)
            {
                if(!@socket_connect(self::$sockets[$socketKey], $application->getTcpConfiguration()->getHost(), $application->getTcpConfiguration()->getPort()))
                {
                    unset(self::$sockets[$socketKey]);
                    return;
                }

                @socket_send(self::$sockets[$socketKey], $message, strlen($message), 0);
            }
        }
    }