<?php

    namespace LogLib2\Interfaces;

    interface SerializableInterface
    {
        /**
         * Returns an array representation of the object
         *
         * @return array The array representation of the object
         */
        public function toArray(): array;

        /**
         * Constructs the object from an array representation
         *
         * @param array|null $data The array representation of the object
         * @throws \InvalidArgumentException If one or more data entries are invalid
         * @return SerializableInterface The constructed class that implements SerializableInterface
         */
        public static function fromArray(?array $data=null): SerializableInterface;
    }