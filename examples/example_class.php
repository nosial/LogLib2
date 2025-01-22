<?php

    class ExampleClass {

        private \LogLib2\Logger $logger;

        public function __construct(\LogLib2\Logger $logger) {
            $this->logger = $logger;
        }

        public function getLogger(): \LogLib2\Logger {
            return $this->logger;
        }

        public function sleepExample(int $seconds): void {
            $this->logger->info("Sleeping for $seconds seconds...");
            sleep($seconds);
            $this->logger->info("Finished sleeping for $seconds seconds.");
        }

        public function  throwException(): void {
            throw new \Exception("This is an example exception.");
        }

        public function throwDoubleException(): void {
            try
            {
                $this->throwException();
            }
            catch(Exception $e)
            {
                throw new Exception("this is a new exception", 0, $e);
            }
        }

        public function warningExceptionExample(): void {
            $this->logger->warning("Throwing a warning exception...", new \Exception("This is an example warning exception."));
        }
    }