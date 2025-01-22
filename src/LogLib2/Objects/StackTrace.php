<?php

    namespace LogLib2\Objects;

    use LogLib2\Classes\Utilities;
    use LogLib2\Enums\CallType;
    use LogLib2\Interfaces\SerializableInterface;

    class StackTrace implements SerializableInterface
    {
        private ?string $file;
        private ?int $line;
        private ?string $function;
        private ?array $args;
        private ?string $class;
        private ?CallType $callType;

        /**
         * Constructs a new instance with the provided parameters.
         *
         * @param string|null $file The file name, or null if not specified.
         * @param int|null $line The line number, or null if not specified.
         * @param string|null $function The function name, or null if not specified.
         * @param array|null $args The array of arguments, or null if not provided.
         * @param string|null $class The class name, or null if not specified.
         * @param CallType|null $callType The type of the call, or null if not specified.
         *
         * @return void
         */
        public function __construct(?string $file=null, ?int $line=null, ?string $function=null, ?array $args=null, ?string $class=null, ?CallType $callType=null)
        {
            $this->file = $file;
            $this->line = $line;
            $this->function = $function;

            if($args !== null && !empty($args))
            {
                $this->args = $args;
            }
            else
            {
                $this->args = null;
            }

            $this->class = $class;
            $this->callType = $callType;
        }

        /**
         * Retrieves the file name.
         *
         * @return string|null Returns the file as a string, or null if no file is set.
         */
        public function getFile(): ?string
        {
            return $this->file;
        }

        /**
         * Retrieves the line number.
         *
         * @return int|null Returns the line number or null if not set.
         */
        public function getLine(): ?int
        {
            return $this->line;
        }

        /**
         * Retrieves the function name.
         *
         * @return string|null The function name or null if not set.
         */
        public function getFunction(): ?string
        {
            return $this->function;
        }

        /**
         * Retrieves the arguments.
         *
         * @return array|null Returns an array of arguments or null if no arguments are set.
         */
        public function getArgs(): ?array
        {
            return $this->args;
        }

        /**
         *
         * @return string|null The class name or null if not set.
         */
        public function getClass(): ?string
        {
            return $this->class;
        }

        /**
         * Retrieves the call type.
         *
         * @return CallType|null The call type or null if not set.
         */
        public function getCallType(): ?CallType
        {
            return $this->callType;
        }

        /**
         * Determines whether the current object contains no data.
         *
         * @return bool True if all properties are null, false otherwise.
         */
        public function isEmpty(): bool
        {
            return
                $this->file === null &&
                $this->line === null &&
                $this->function === null &&
                $this->args === null &&
                $this->class === null &&
                $this->callType === null;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'file' => $this->file,
                'line' => $this->line,
                'function' => $this->function,
                'args' => $this->args,
                'class' => $this->class,
                'call_type' => $this->callType?->value ?? CallType::STATIC_CALL->value,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(?array $data=null): StackTrace
        {
            $callType = null;
            if(isset($data['call_type']))
            {
                $callType = CallType::tryFrom($data['call_type']);
            }

            return new StackTrace(
                $data['file'] ?? null,
                $data['line'] ?? null,
                $data['function'] ?? null,
                $data['args'] ?? null,
                $data['class'] ?? null,
                $callType
            );
        }

        /**
         * Creates a new instance from the provided trace data.
         *
         * @param array $trace The trace data to be used.
         * @return StackTrace The new instance created from the trace data.
         */
        public static function fromTrace(array $trace): StackTrace
        {
            $parsedTrace = [
                'file' => $trace['file'] ?? null,
                'function' => $trace['function'] ?? null,
                'class' => $trace['class'] ?? null,
                'call' => $trace['call'] ?? null,
            ];

            if(isset($trace['line']))
            {
                $parsedTrace['line'] = (int) $trace['line'];
            }
            else
            {
                $parsedTrace['line'] = null;
            }

            if(isset($trace['args']))
            {
                $result = [];
                if(array_is_list($trace['args']))
                {
                    foreach($trace['args'] as $arg)
                    {
                        $result[] = Utilities::getSafeValue($arg);
                    }
                }
                else
                {
                    foreach($trace['args'] as $key => $arg)
                    {
                        $result[$key] = Utilities::getSafeValue($arg);
                    }
                }

                $parsedTrace['args'] = $result;
            }

            return StackTrace::fromArray($parsedTrace);
        }
    }