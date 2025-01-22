<?php

    namespace LogLib2\Enums;

    enum CallType : string
    {
        /**
         * Represents a method call.
         *
         * @var string METHOD_CALL
         */
        case METHOD_CALL = '->';

        /**
         * Represents a static method call.
         *
         * @var string STATIC_CALL
         */
        case STATIC_CALL = '::';

        /**
         * Represents a function call.
         *
         * @var string FUNCTION_CALL
         */
        case FUNCTION_CALL = '()';

        /**
         * Represents a lambda function call.
         *
         * @var string LAMBDA_CALL
         */
        case LAMBDA_CALL = 'λ';
    }
