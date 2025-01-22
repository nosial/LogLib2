<?php

    namespace LogLib2\Objects;

    use LogLib2\Enums\LogLevel;
    use LogLib2\Enums\TimestampFormat;
    use LogLib2\Enums\TraceFormat;
    use LogLib2\Interfaces\SerializableInterface;
    use Throwable;

    class Event implements SerializableInterface
    {
        private string $applicationName;
        private int $timestamp;
        private LogLevel $level;
        private string $message;
        private ?ExceptionDetails $exception;
        /**
         * @var StackTrace[]
         */
        private array $traces;

        /**
         * Constructs a new instance with the provided parameters.
         *
         * @param string $applicationName The name of the application that generated the event.
         * @param LogLevel $level The log level of the event.
         * @param string $message The message of the event.
         * @param array $backtrace The array of StackTrace instances.
         * @param int|null $timestamp The timestamp of the event, or null if not specified (defaults to the current time).
         * @param ExceptionDetails|Throwable|null $exceptionDetails The exception details, or null if not specified.
         */
        public function __construct(string $applicationName, LogLevel $level, string $message, array $backtrace, ?int $timestamp=null, ExceptionDetails|Throwable|null $exceptionDetails=null)
        {
            if($exceptionDetails instanceof Throwable)
            {
                $exceptionDetails = ExceptionDetails::fromThrowable($exceptionDetails);
            }

            $this->applicationName = $applicationName;
            if($timestamp === null)
            {
                $timestamp = time();
            }
            $this->timestamp = $timestamp;
            $this->level = $level;
            $this->message = $message;
            $this->exception = $exceptionDetails;
            $this->traces = $backtrace;
        }

        /**
         * Retrieves the application name.
         *
         * @return string Returns the application name as a string.
         */
        public function getApplicationName(): string
        {
            return $this->applicationName;
        }

        /**
         * Retrieves the timestamp.
         *
         * @return int Returns the timestamp as an integer.
         */
        public function getTimestamp(): int
        {
            return $this->timestamp;
        }

        /**
         * Retrieves the log level.
         *
         * @return LogLevel Returns the log level as a LogLevel instance.
         */
        public function getLevel(): LogLevel
        {
            return $this->level;
        }

        /**
         * Retrieves the message.
         *
         * @return string Returns the message as a string.
         */
        public function getMessage(): string
        {
            return $this->message;
        }

        /**
         * Retrieves the exception details.
         *
         * @return ExceptionDetails|null Returns the exception details, or null if not set.
         */
        public function getException(): ?ExceptionDetails
        {
            return $this->exception;
        }

        /**
         * Retrieves the backtrace.
         *
         * @return StackTrace[] Returns the backtrace as an array of StackTrace instances.
         */
        public function getTraces(): array
        {
            return $this->traces;
        }

        /**
         * Retrieves the first trace.
         *
         * @return StackTrace|null Returns the first trace or null if no traces are set.
         */
        public function getFirstTrace(): ?StackTrace
        {
            if(count($this->traces) > 0)
            {
                return $this->traces[0];
            }

            return null;
        }

        /**
         * Returns a standard array representation of the event.
         *
         * @return array The standard array representation of the event.
         */
        public function toStandardArray(TimestampFormat $timestampFormat, TraceFormat $traceFormat): array
        {
            $result = $this->toArray();

            /// Rename the traces to stack_trace
            $result['stack_trace'] = $result['traces'];
            unset($result['traces']);

            // Format the timestamp
            if($timestampFormat === TimestampFormat::NONE)
            {
                $result['timestamp'] = TimestampFormat::UNIX_TIMESTAMP->format($result['timestamp']);
            }
            else
            {
                $result['timestamp'] = $timestampFormat->format($result['timestamp']);
            }

            // Format the trace
            if(count($result['stack_trace']) > 0)
            {
                $result['trace'] = $traceFormat->format($this->getFirstTrace());
            }

            return $result;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            $result = [
                'application_name' => $this->applicationName,
                'timestamp' => $this->timestamp,
                'level' => $this->level->value,
                'message' => $this->message,
                'traces' => [],
                'exception' => null,
            ];

            foreach($this->traces as $trace)
            {
                $result['traces'][] = $trace->toArray();
            }

            if($this->exception !== null)
            {
                $result['exception'] = $this->exception->toArray();
            }

            return $result;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(?array $data=null): Event
        {
            $traces = [];
            if(isset($data['traces']))
            {
                foreach($data['traces'] as $traceData)
                {
                    $traces[] = StackTrace::fromArray($traceData);
                }
            }

            $exceptionDetails = null;
            if(isset($data['exception']))
            {
                $exceptionDetails = ExceptionDetails::fromArray($data['exception']);
            }

            return new Event(
                $data['application_name'],
                LogLevel::from($data['level']),
                $data['message'],
                $traces,
                $data['timestamp'],
                $exceptionDetails
            );
        }
    }