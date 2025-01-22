<?php

    namespace LogLib2\Classes;

    use LogLib2\Exceptions\IOException;

    class FileLock
    {
        private $fileHandle;
        private int $permissions;
        private string $filePath;
        private int $retryInterval; // in microseconds
        private int $confirmationInterval; // in microseconds

        /**
         * Constructor for FileLock.
         *
         * @param string $filePath Path to the file.
         * @param int $permissions
         * @param int $retryInterval Time to wait between retries (in microseconds).
         * @param int $confirmationInterval Time to wait before double confirmation (in microseconds).
         * @throws IOException if unable to create the file or set the permissions.
         */
        public function __construct(string $filePath, int $permissions, int $retryInterval=100000, int $confirmationInterval=50000)
        {
            $this->filePath = $filePath;
            $this->permissions = $permissions;
            $this->retryInterval = $retryInterval;
            $this->confirmationInterval = $confirmationInterval;

            // Create the file if it doesn't exist
            if (!file_exists($filePath))
            {
                // Create the file
                if(!@touch($filePath))
                {
                    throw new IOException("Unable to create the file: " . $filePath);
                }

                if(!@chmod($filePath, $this->permissions))
                {
                    throw new IOException("Unable to set the file permissions: " . $filePath);
                }
            }
        }

        /**
         * Locks the file.
         *
         * @throws IOException if unable to open or lock the file.
         */
        private function lock(): bool
        {
            $this->fileHandle = @fopen($this->filePath, 'a');
            if ($this->fileHandle === false)
            {
                return false;
            }

            // Keep trying to acquire the lock until it succeeds
            while (!flock($this->fileHandle, LOCK_EX))
            {
                usleep($this->retryInterval); // Wait for the specified interval before trying again
            }

            // Double confirmation
            usleep($this->confirmationInterval); // Wait for the specified confirmation interval
            if (!flock($this->fileHandle, LOCK_EX | LOCK_NB))
            {
                // If the lock cannot be re-acquired, release the current lock and retry
                flock($this->fileHandle, LOCK_UN);
                $this->lock();
            }

            return true;
        }

        /**
         * Unlocks the file after performing write operations.
         */
        private function unlock(): void
        {
            if ($this->fileHandle !== null)
            {
                flock($this->fileHandle, LOCK_UN); // Release the lock
                fclose($this->fileHandle); // Close the file handle
                $this->fileHandle = null; // Reset the file handle

                // Check if write permissions have changed
                if (!is_writable($this->filePath))
                {
                    // Set the file permissions to the default
                    chmod($this->filePath, $this->permissions);
                }
            }
        }

        /**
         * Appends data to the file.
         *
         * @param string $data Data to append.
         * @throws IOException if unable to write to the file.
         */
        public function append(string $data): void
        {
            if(!$this->lock())
            {
                // Do not proceed if the file cannot be locked
                return;
            }

            if ($this->fileHandle !== false)
            {
                if (fwrite($this->fileHandle, $data) === false)
                {
                    throw new IOException("Unable to write to the file: " . $this->filePath);
                }
            }

            $this->unlock();
        }

        /**
         * Destructor to ensure the file handle is closed.
         */
        public function __destruct()
        {
            if ($this->fileHandle)
            {
                fclose($this->fileHandle);
            }
        }
    }