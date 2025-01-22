<?php

    namespace LogLib2\Enums;

    use Random\RandomException;

    enum ConsoleColor
    {
        case DEFAULT;
        case BLACK;
        case RED;
        case GREEN;
        case YELLOW;
        case BLUE;
        case MAGENTA;
        case CYAN;
        case WHITE;

        /**
         * Formats the given input string with the color of the current ConsoleColor.
         *
         * @param string $input The input string to format.
         * @param bool $revertToDefault Whether to revert the color back to the default color after the input string.
         * @return string The formatted input string.
         */
        public function format(string $input, bool $revertToDefault=true): string
        {
            $colorCode = match($this)
            {
                self::DEFAULT => 39,
                self::BLACK => 30,
                self::RED => 31,
                self::GREEN => 32,
                self::YELLOW => 33,
                self::BLUE => 34,
                self::MAGENTA => 35,
                self::CYAN => 36,
                self::WHITE => 37,
            };

            return "\033[" . $colorCode . "m" . $input . ($revertToDefault ? "\033[39m" : '');
        }

        /**
         * Formats the given input string with the light color of the current ConsoleColor.
         *
         * @param string $input The input string to format.
         * @param bool $revertToDefault Whether to revert the color back to the default color after the input string.
         * @return string The formatted input string.
         */
        public function formatLight(string $input, bool $revertToDefault=true): string
        {
            $colorCode = match($this)
            {
                self::DEFAULT => 39,
                self::BLACK => 90,
                self::RED => 91,
                self::GREEN => 92,
                self::YELLOW => 93,
                self::BLUE => 94,
                self::MAGENTA => 95,
                self::CYAN => 96,
                self::WHITE => 97,
            };

            return "\033[" . $colorCode . "m" . $input . ($revertToDefault ? "\033[39m" : '');
        }

        /**
         * Formats the given input string with the bold color of the current ConsoleColor.
         *
         * @param string $input The input string to format.
         * @param bool $revertToDefault Whether to revert the color back to the default color after the input string.
         * @return string The formatted input string.
         */
        public function formatBold(string $input, bool $revertToDefault=true): string
        {
            $colorCode = match($this)
            {
                self::DEFAULT => 39,
                self::BLACK => 30,
                self::RED => 31,
                self::GREEN => 32,
                self::YELLOW => 33,
                self::BLUE => 34,
                self::MAGENTA => 35,
                self::CYAN => 36,
                self::WHITE => 37,
            };

            return "\033[1;" . $colorCode . "m" . $input . ($revertToDefault ? "\033[0m" : '');
        }

        /**
         * Formats the given input string with the background color of the current ConsoleColor.
         *
         * @param string $input The input string to format.
         * @param ConsoleColor $foreground The foreground color to use.
         * @param bool $revertToDefault Whether to revert the color back to the default color after the input string.
         * @return string The formatted input string.
         */
        public function formatBackground(string $input, ConsoleColor $foreground, bool $revertToDefault=true): string
        {
            $colorCode = match($this)
            {
                self::DEFAULT => 49,
                self::BLACK => 40,
                self::RED => 41,
                self::GREEN => 42,
                self::YELLOW => 43,
                self::BLUE => 44,
                self::MAGENTA => 45,
                self::CYAN => 46,
                self::WHITE => 47,
            };

            return "\033[" . $colorCode . "m" . $foreground->format($input, false) . ($revertToDefault ? "\033[49m" : '');
        }

        /**
         * Formats the given input string with the light background color of the current ConsoleColor.
         *
         * @return ConsoleColor The formatted input string.
         * @throws RandomException Thrown when the random_int function fails to generate a random integer.
         */
        public static function getRandomColor(array $disallow=[]): ConsoleColor
        {
            $colors = [
                self::BLACK,
                self::RED,
                self::GREEN,
                self::YELLOW,
                self::BLUE,
                self::MAGENTA,
                self::CYAN,
                self::WHITE,
            ];

            // Convert disallowed colors to strings
            $disallow = array_map(fn($color) => $color->name, $disallow);

            // Filter out disallowed colors
            $colors = array_filter($colors, fn($color) => !in_array($color->name, $disallow));

            if (empty($colors))
            {
                throw new RandomException('No colors available to choose from.');
            }

            return $colors[array_rand($colors)];
        }
    }