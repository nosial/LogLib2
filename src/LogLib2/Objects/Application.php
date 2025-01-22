<?php

    namespace LogLib2\Objects;

    use LogLib2\Objects\Configurations\ConsoleConfiguration;
    use LogLib2\Objects\Configurations\DescriptorConfiguration;
    use LogLib2\Objects\Configurations\FileConfiguration;
    use LogLib2\Objects\Configurations\HttpConfiguration;
    use LogLib2\Objects\Configurations\TcpConfiguration;
    use LogLib2\Objects\Configurations\UdpConfiguration;

    class Application
    {
        private string $name;
        private ConsoleConfiguration $consoleConfiguration;
        private DescriptorConfiguration $descriptorConfiguration;
        private FileConfiguration $fileConfiguration;
        private HttpConfiguration $httpConfiguration;
        private TcpConfiguration $tcpConfiguration;
        private UdpConfiguration $udpConfiguration;

        /**
         * Constructs a new instance with the provided application name and log level.
         *
         * @param string $name The application name.
         */
        public function __construct(string $name)
        {
            $this->name = $name;
            $this->consoleConfiguration = new ConsoleConfiguration();
            $this->descriptorConfiguration = new DescriptorConfiguration();
            $this->fileConfiguration = new FileConfiguration();
            $this->httpConfiguration = new HttpConfiguration();
            $this->tcpConfiguration = new TcpConfiguration();
            $this->udpConfiguration = new UdpConfiguration();
        }

        /**
         * Retrieves the name of the application.
         *
         * @return string The name of the application.
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Retrieves the ConsoleConfiguration instance for the application.
         *
         * @return ConsoleConfiguration The ConsoleConfiguration instance for the application.
         */
        public function getConsoleConfiguration(): ConsoleConfiguration
        {
            return $this->consoleConfiguration;
        }

        /**
         * Sets the ConsoleConfiguration instance for the application.
         *
         * @param ConsoleConfiguration $configuration The ConsoleConfiguration instance for the application.
         * @return Application Returns the current instance for method chaining.
         */
        public function setConsoleConfiguration(ConsoleConfiguration $configuration): Application
        {
            $this->consoleConfiguration = $configuration;
            return $this;
        }

        /**
         * Retrieves the DescriptorConfiguration instance for the application.
         *
         * @return DescriptorConfiguration The DescriptorConfiguration instance for the application.
         */
        public function getDescriptorConfiguration(): DescriptorConfiguration
        {
            return $this->descriptorConfiguration;
        }

        /**
         * Sets the DescriptorConfiguration instance for the application.
         *
         * @param DescriptorConfiguration $configuration The DescriptorConfiguration instance for the application.
         * @return Application Returns the current instance for method chaining.
         */
        public function setDescriptorConfiguration(DescriptorConfiguration $configuration): Application
        {
            $this->descriptorConfiguration = $configuration;
            return $this;
        }

        /**
         * Retrieves the FileConfiguration instance for the application.
         *
         * @return FileConfiguration The FileConfiguration instance for the application.
         */
        public function getFileConfiguration(): FileConfiguration
        {
            return $this->fileConfiguration;
        }

        /**
         * Sets the FileConfiguration instance for the application.
         *
         * @param FileConfiguration $configuration The FileConfiguration instance for the application.
         * @return Application Returns the current instance for method chaining.
         */
        public function setFileConfiguration(FileConfiguration $configuration): Application
        {
            $this->fileConfiguration = $configuration;
            return $this;
        }

        /**
         * Retrieves the HttpConfiguration instance for the application.
         *
         * @return HttpConfiguration The HttpConfiguration instance for the application.
         */
        public function getHttpConfiguration(): HttpConfiguration
        {
            return $this->httpConfiguration;
        }

        /**
         * Sets the HttpConfiguration instance for the application.
         *
         * @param HttpConfiguration $configuration The HttpConfiguration instance for the application.
         * @return Application Returns the current instance for method chaining.
         */
        public function setHttpConfiguration(HttpConfiguration $configuration): Application
        {
            $this->httpConfiguration = $configuration;
            return $this;
        }

        /**
         * Retrieves the TcpConfiguration instance for the application.
         *
         * @return TcpConfiguration The TcpConfiguration instance for the application.
         */
        public function getTcpConfiguration(): TcpConfiguration
        {
            return $this->tcpConfiguration;
        }

        /**
         * Sets the TcpConfiguration instance for the application.
         *
         * @param TcpConfiguration $configuration The TcpConfiguration instance for the application.
         * @return Application Returns the current instance for method chaining.
         */
        public function setTcpConfiguration(TcpConfiguration $configuration): Application
        {
            $this->tcpConfiguration = $configuration;
            return $this;
        }

        /**
         * Retrieves the UdpConfiguration instance for the application.
         *
         * @return UdpConfiguration The UdpConfiguration instance for the application.
         */
        public function getUdpConfiguration(): UdpConfiguration
        {
            return $this->udpConfiguration;
        }

        /**
         * Sets the UdpConfiguration instance for the application.
         *
         * @param UdpConfiguration $configuration The UdpConfiguration instance for the application.
         * @return Application Returns the current instance for method chaining.
         */
        public function setUdpConfiguration(UdpConfiguration $configuration): Application
        {
            $this->udpConfiguration = $configuration;
            return $this;
        }

        /**
         * Retrieves the string representation of the application.
         *
         * @return string The string representation of the application.
         */
        public function __toString(): string
        {
            return $this->name;
        }
    }