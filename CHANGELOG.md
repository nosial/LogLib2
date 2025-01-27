# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2025-01-27

Minor changes to the project

### Changed
 - ExceptionDetails now accepts a string as a type for the $line parameter, this will automatically
   convert the string to an integer.


## [1.0.1] - 2025-01-22

Minor changes to the project

### Fixed
 - Fixed issue where \LogLib2\Classes\Utilities::getSafeValue(mixed $input) would return
   a null value when the typed output is `string` by including a branch that converts
   the `gettype($input)` to a string when the input is null. 


## [1.0.0] - 2025-01-22

This is the initial release of LogLib2