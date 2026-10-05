<?php

    namespace LogLib2\Enums;

    enum SmtpEncryption
    {
        /**
         * No encryption, the connection is plain text (typically port 25).
         */
        case NONE;

        /**
         * The connection is upgraded to TLS using the STARTTLS command (typically port 587).
         */
        case STARTTLS;

        /**
         * The connection uses implicit TLS from the start (typically port 465).
         */
        case TLS;

        /**
         * Parses the input string into a SmtpEncryption enum.
         *
         * @param string $input The input string to parse.
         * @return SmtpEncryption The parsed SmtpEncryption enum.
         */
        public static function parseFrom(string $input): SmtpEncryption
        {
            return match(strtolower($input))
            {
                'none', 'plain', '0' => SmtpEncryption::NONE,
                'tls', 'ssl', 'smtps', '2' => SmtpEncryption::TLS,
                default => SmtpEncryption::STARTTLS,
            };
        }
    }
