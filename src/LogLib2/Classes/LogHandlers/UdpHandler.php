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
            if(!filter_var($application->getUdpConfiguration()->getHost(), FILTER_VALIDATE_IP))
            {
                return false;
            }

            if($application->getUdpConfiguration()->getPort() < 1 || $application->getUdpConfiguration()->getPort() > 65535)
            {
                return false;
            }

            $socketKey = $application->getUdpConfiguration()->getHost() . ':' . $application->getUdpConfiguration()->getPort();
            if(!isset(self::$sockets[$socketKey]))
            {
                $errorCode = 0;
                $errorMessage = '';
                self::$sockets[$socketKey] = @pfsockopen(
                    'udp://' . $application->getUdpConfiguration()->getHost(),
                    $application->getUdpConfiguration()->getPort(),
                    $errorCode,
                    $errorMessage,
                    (float)ini_get('default_socket_timeout')
                );
                if(self::$sockets[$socketKey] === false)
                {
                    self::$sockets[$socketKey] = null;
                    return false;
                }
                return true;
            }

            if(self::$sockets[$socketKey] === null)
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
            $message = $application->getUdpConfiguration()->getLogFormat()->format(
                $application->getUdpConfiguration()->getTimestampFormat(), $application->getUdpConfiguration()->getTraceFormat(), $event
            );

            if($application->getUdpConfiguration()->isAppendNewline())
            {
                $message .= PHP_EOL;
            }

            if(strlen($message) > 65535)
            {
                return;
            }

            $host = $application->getUdpConfiguration()->getHost();
            $port = $application->getUdpConfiguration()->getPort();
            $socketKey = $host . ':' . $port;
            $timeout = (float)ini_get('default_socket_timeout');

            if(!isset(self::$sockets[$socketKey]))
            {
                $errorCode = 0;
                $errorMessage = '';
                self::$sockets[$socketKey] = @pfsockopen('udp://' . $host, $port, $errorCode, $errorMessage, $timeout);
                if(self::$sockets[$socketKey] === false)
                {
                    self::$sockets[$socketKey] = null;
                    return;
                }
            }

            if(self::$sockets[$socketKey] === null)
            {
                return;
            }

            if(@fwrite(self::$sockets[$socketKey], $message) === false)
            {
                @fclose(self::$sockets[$socketKey]);
                $errorCode = 0;
                $errorMessage = '';
                $newSocket = @pfsockopen('udp://' . $host, $port, $errorCode, $errorMessage, $timeout);
                if($newSocket !== false)
                {
                    self::$sockets[$socketKey] = $newSocket;
                    @fwrite($newSocket, $message);
                }
                else
                {
                    self::$sockets[$socketKey] = null;
                }
            }
        }
    }
