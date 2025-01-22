<?php

    namespace LogLib2\Enums;

    use LogLib2\Objects\StackTrace;

    enum TraceFormat
    {
        case NONE;
        case BASIC;
        case FULL;

        /**
         * Formats the stack trace based on the type of trace.
         *
         * @param StackTrace $stackTrace The stack trace to format.
         * @return string The formatted stack trace.
         */
        public function format(StackTrace $stackTrace): string
        {
            if($this === TraceFormat::BASIC)
            {
                return self::formatBasic($stackTrace);
            }

            if($this === TraceFormat::FULL)
            {
                return self::formatFull($stackTrace);
            }

            return (string)null;
        }

        /**
         * Parses the input string into a TraceFormat enum.
         *
         * @param string $input The input string to parse.
         * @return TraceFormat The parsed TraceFormat enum.
         */
        public static function parseFrom(string $input): TraceFormat
        {
            return match(strtolower($input))
            {
                'none', '0' => TraceFormat::NONE,
                'basic', '1' => TraceFormat::BASIC,
                'full', '2' => TraceFormat::FULL,
                default => TraceFormat::BASIC,
            };
        }

        /**
         * Formats the stack trace as a basic string.
         *
         * @param StackTrace $stackTrace The stack trace to format.
         * @return string The formatted stack trace.
         */
        private static function formatBasic(StackTrace $stackTrace): string
        {
            if($stackTrace->getFunction() === null)
            {
                if($stackTrace->getClass() !== null)
                {
                    return $stackTrace->getClass();
                }

                if($stackTrace->getFile() !== null)
                {
                    if($stackTrace->getLine() !== null)
                    {
                        return $stackTrace->getFile() . ':' . $stackTrace->getLine();
                    }
                    else
                    {
                        return $stackTrace->getFile();
                    }
                }
            }

            if($stackTrace->getClass() !== null)
            {
                return $stackTrace->getClass() . ($stackTrace->getCallType()?->value ?? CallType::STATIC_CALL->value) . $stackTrace->getFunction();
            }

            if($stackTrace->getFile() !== null)
            {
                if($stackTrace->getLine() !== null)
                {
                    return $stackTrace->getFile() . ':' . $stackTrace->getLine() . ' ' . ($stackTrace->getCallType()?->value ?? CallType::STATIC_CALL->value) . $stackTrace->getFunction();
                }
                else
                {
                    return $stackTrace->getFile() . ' ' . ($stackTrace->getCallType()?->value ?? CallType::STATIC_CALL->value) . $stackTrace->getFunction();
                }
            }

            return $stackTrace->getFunction();
        }

        /**
         * Formats the stack trace as a full string.
         *
         * @param StackTrace $stackTrace The stack trace to format.
         * @return string The formatted stack trace.
         */
        private static function formatFull(StackTrace $stackTrace): string
        {
            $output = '';

            if($stackTrace->getClass() !== null)
            {
                $output .= $stackTrace->getClass();
            }

            if($stackTrace->getCallType() !== null)
            {
                $output .= $stackTrace->getCallType()->value;
            }

            if($stackTrace->getFunction() !== null)
            {
                $output .= $stackTrace->getFunction();
            }

            if($stackTrace->getFile() !== null)
            {
                $output .= ' (' . $stackTrace->getFile();

                if($stackTrace->getLine() !== null)
                {
                    $output .= ':' . $stackTrace->getLine();
                }

                $output .= ')';
            }

            return $output;
        }
    }
