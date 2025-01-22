<?php

    namespace LogLib2\Exceptions;

    use Throwable;

    class IOException extends \Exception
    {
        /**
         * @inheritDoc
         */
        public function __construct(string $message = "", int $code=0, ?Throwable $previous=null)
        {
            parent::__construct($message, $code, $previous);
        }
    }