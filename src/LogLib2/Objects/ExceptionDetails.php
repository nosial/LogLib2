<?php

    namespace LogLib2\Objects;

    use LogLib2\Interfaces\SerializableInterface;
    use Throwable;

    class ExceptionDetails implements SerializableInterface
    {
        private string $name;
        private string $message;
        private ?int $code;
        private ?string $file;
        private ?int $line;
        /**
         * @var StackTrace[]|null
         */
        private ?array $trace;
        private ?ExceptionDetails $previous;

        /**
         * Constructs a new instance with the provided parameters.
         *
         * @param string $name The name of the exception.
         * @param string $message The exception message.
         * @param string|int|null $code The exception code, or null if not specified. If a string is provided, it will be converted to an integer.
         * @param string|null $file The file name, or null if not specified.
         * @param string|int|null $line The line number, or null if not specified. If a string is provided, it will be converted to an integer.
         * @param StackTrace[]|null $trace The array of StackTrace instances, or null if not provided.
         * @param ExceptionDetails|null $previous The previous exception, or null if not specified.
         */
        public function __construct(string $name, string $message, null|string|int $code=null, ?string $file=null, null|string|int $line=null, ?array $trace=null, ?ExceptionDetails $previous=null)
        {
            if(is_string($line))
            {
                $line = (int)$line;
            }

            if(is_string($code))
            {
                $code = (int)$code;
            }

            $this->name = $name;
            $this->message = $message;
            $this->code = $code;
            $this->file = $file;
            $this->line = $line;
            $this->trace = $trace;
            $this->previous = $previous;
        }

        public function getName(): string
        {
            return $this->name;
        }

        public function getMessage(): string
        {
            return $this->message;
        }

        public function getCode(): ?int
        {
            return $this->code;
        }

        public function getFile(): ?string
        {
            return $this->file;
        }

        public function getLine(): ?int
        {
            return $this->line;
        }

        public function getTrace(): ?array
        {
            return $this->trace;
        }

        public function getPrevious(): ?ExceptionDetails
        {
            return $this->previous;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            $result = [
                'name' => $this->name,
                'message' => $this->message,
                'code' => $this->code,
                'file' => $this->file,
                'line' => $this->line,
                'trace' => [],
                'previous' => null,
            ];

            if($this->trace !== null)
            {
                foreach($this->trace as $trace)
                {
                    $result['trace'][] = $trace->toArray();
                }
            }

            if($this->previous !== null)
            {
                $result['previous'] = $this->previous->toArray();
            }

            return $result;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(?array $data=null): SerializableInterface
        {

            $trace = [];
            if(isset($data['trace']))
            {
                foreach($data['trace'] as $traceData)
                {
                    $trace[] = StackTrace::fromArray($traceData);
                }
            }

            $previous = null;
            if(isset($data['previous']))
            {
                $previous = self::fromArray($data['previous']);
            }

            return new ExceptionDetails(
                $data['name'] ?? '',
                $data['message'] ?? '',
                $data['code'] ?? null,
                $data['file'] ?? null,
                $data['line'] ?? null,
                $trace,
                $previous
            );
        }

        /**
         * Creates a new instance from the provided Throwable instance.
         *
         * @param Throwable $e The Throwable instance to create the ExceptionDetails instance from.
         * @return ExceptionDetails The created ExceptionDetails instance.
         */
        public static function fromThrowable(Throwable $e): ExceptionDetails
        {
            $trace = [];
            foreach($e->getTrace() as $traceData)
            {
                $trace[] = StackTrace::fromTrace($traceData);
            }

            $previous = null;
            if($e->getPrevious() !== null)
            {
                $previous = self::fromThrowable($e->getPrevious());
            }

            return new ExceptionDetails(
                get_class($e),
                $e->getMessage(),
                $e->getCode(),
                $e->getFile(),
                $e->getLine(),
                $trace,
                $previous
            );
        }
    }