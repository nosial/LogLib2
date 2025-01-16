<?php

    namespace LogLib2;

    class Program
    {
        /**
         * LogLib2 main entry point
         *
         * @param string[] $args Command-line arguments
         * @return int Exit code
         */
        public static function main(array $args): int
        {
            print("Hello World from net.nosial.loglib2!" . PHP_EOL);
            return 0;
        }
    }