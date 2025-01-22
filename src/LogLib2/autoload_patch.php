<?php

    /**
     * This file is used to patch the autoloader, ncc will load this file first before evaluating the package's class
     * files. Without this file, ncc will run into a recursive dependency issue where classes are trying to load each
     * other before they are defined.
     */

    require __DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'Event.php';
    require __DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'ExceptionDetails.php';
    require __DIR__ . DIRECTORY_SEPARATOR . 'Objects' . DIRECTORY_SEPARATOR . 'StackTrace.php';