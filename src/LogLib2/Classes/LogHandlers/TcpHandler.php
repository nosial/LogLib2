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
                $errorCode = 0;
                $errorMessage = '';
                self::$sockets[$socketKey] = @pfsockopen(
                    $application->getTcpConfiguration()->getHost(),
                    $application->getTcpConfiguration()->getPort(),
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
            $message = $application->getTcpConfiguration()->getLogFormat()->format(
                $application->getTcpConfiguration()->getTimestampFormat(), $application->getTcpConfiguration()->getTraceFormat(), $event
            );

            if($application->getTcpConfiguration()->isAppendNewline())
            {
                $message .= PHP_EOL;
            }

            if(strlen($message) > 65535)
            {
                return;
            }

            $host = $application->getTcpConfiguration()->getHost();
            $port = $application->getTcpConfiguration()->getPort();
            $socketKey = $host . ':' . $port;
            $timeout = (float)ini_get('default_socket_timeout');

            if(!isset(self::$sockets[$socketKey]))
            {
                $errorCode = 0;
                $errorMessage = '';
                self::$sockets[$socketKey] = @pfsockopen($host, $port, $errorCode, $errorMessage, $timeout);
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
                $newSocket = @pfsockopen($host, $port, $errorCode, $errorMessage, $timeout);
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
