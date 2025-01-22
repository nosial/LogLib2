<?php

    namespace LogLib2\Classes\LogHandlers;

    use LogLib2\Interfaces\LogHandlerInterface;
    use LogLib2\Objects\Application;
    use LogLib2\Objects\Event;

    class DescriptorHandler implements LogHandlerInterface
    {
        private static array $resources = [];

        /**
         * @inheritDoc
         */
        public static function isAvailable(Application $application): bool
        {
            // Check if the descriptor exists
            if(!file_exists($application->getDescriptorConfiguration()->getDescriptor()))
            {
                return false;
            }

            // If the file lock does not exist, create it to allow for file locking & writing.
            if(!isset(self::$resources[$application->getName()]))
            {
                self::$resources[$application->getName()] = @fopen($application->getDescriptorConfiguration()->getDescriptor(), 'a');
            }

            return true;
        }

        /**
         * @inheritDoc
         */
        public static function handleEvent(Application $application, Event $event): void
        {
            $message = $application->getDescriptorConfiguration()->getLogFormat()->format(
                $application->getDescriptorConfiguration()->getTimestampFormat(), $application->getDescriptorConfiguration()->getTraceFormat(), $event
            );

            if($application->getDescriptorConfiguration()->isAppendNewline())
            {
                $message .= PHP_EOL;
            }

            // Write the event to the descriptor.
            $result = @fwrite(self::$resources[$application->getName()], $message);

            if($result === false)
            {
                @fclose(self::$resources[$application->getName()]);
                unset(self::$resources[$application->getName()]);
                self::$resources[$application->getName()] = @fopen($application->getDescriptorConfiguration()->getDescriptor(), 'a');
                @fwrite(self::$resources[$application->getName()], $message);
            }
        }
    }