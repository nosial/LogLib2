<?php

    namespace LogLib2\Classes\LogHandlers;

    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Event;

    class UdpHandler implements LogHandlerInterface
    {
        private static array $sockets = [];

        /**
         * @inheritDoc
         */
        public static function isAvailable(Application $application): bool
        {
            // Check if the UDP configuration is valid.
            if(!filter_var($application->getUdpConfiguration()->getHost(), FILTER_VALIDATE_IP))
            {
                return false;
            }

            if($application->getUdpConfiguration()->getPort() < 1 || $application->getUdpConfiguration()->getPort() > 65535)
            {
                return false;
            }

            // If the socket does not exist, create it.
            $socketKey = $application->getUdpConfiguration()->getHost() . ':' . $application->getUdpConfiguration()->getPort();
            if(!isset(self::$sockets[$socketKey]))
            {
                self::$sockets[$application->getName()] = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                if(self::$sockets[$application->getName()] === false)
                {
                    unset(self::$sockets[$socketKey]);
                    return false;
                }
            }

            return true;
        }

        /**
         * @inheritDoc
         */
        public static function handleEvent(Application $application, Event $event): void
        {
            $message = $application->getUdpConfiguration()->getLogFormat()->format(
                $application->getUdpConfiguration()->getTimestampFormat(), $application->getUdpConfiguration()->getTraceFormat(), $event
            );

            if($application->getUdpConfiguration()->isAppendNewline())
            {
                $message .= PHP_EOL;
            }

            // If the message is too long, fail silently.
            if(strlen($message) > 65535)
            {
                return;
            }

            $socketKey = $application->getUdpConfiguration()->getHost() . ':' . $application->getUdpConfiguration()->getPort();
            if(!isset(self::$sockets[$socketKey]))
            {
                self::$sockets[$socketKey] = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                if(self::$sockets[$socketKey] === false)
                {
                    unset(self::$sockets[$socketKey]);
                    return;
                }
            }

            // If the request fails, try to reconnect and send the message again. if it fails again, fail silently.
            if(@socket_sendto(self::$sockets[$socketKey], $message, strlen($message), 0, $application->getUdpConfiguration()->getHost(), $application->getUdpConfiguration()->getPort()) === false)
            {
                if(!@socket_connect(self::$sockets[$socketKey], $application->getUdpConfiguration()->getHost(), $application->getUdpConfiguration()->getPort()))
                {
                    unset(self::$sockets[$socketKey]);
                }

                @socket_sendto(self::$sockets[$socketKey], $message, strlen($message), 0, $application->getUdpConfiguration()->getHost(), $application->getUdpConfiguration()->getPort());
            }
        }
    }