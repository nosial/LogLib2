<?php

    require 'ncc';
    require 'example_class.php';

    import('net.nosial.loglib2');

    \LogLib2\Logger::setBacktraceLevel(3);
    \LogLib2\Logger::registerHandlers();
    $logger = new \LogLib2\Logger('Example');

    // Iterate 10 times
    for($i = 0; $i < 10; $i++)
    {
        // Log a message with a random log level
        $logger->info('This is an example log message.');
    }

    $a = [];
    $b = $a['foo']; // <-- This will throw a notice that will be caught by the logger

    $exception = new \Exception('This is an example exception.');
    $logger->error("test", $exception);



    $example = new ExampleClass($logger);
    $example->sleepExample(5);
    $example->throwDoubleException();