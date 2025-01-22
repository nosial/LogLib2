<?php

    namespace LogLib2\Interfaces;

    use LogLib2\Objects\Application;
    use LogLib2\Objects\Event;

    interface LogHandlerInterface
    {
        /**
         * Determines if the requested resource or functionality is available.
         *
         * @param Application $application The application's instance
         *
         * @return bool True if available, false otherwise
         */
        public static function isAvailable(Application $application): bool;

        /**
         * Handles the logging event for a log handler that implements this class
         *
         * @param Application $application The application's instance
         * @param Event $event The event to log
         * @return void
         */
        public static function handleEvent(Application $application, Event $event): void;
    }