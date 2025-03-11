# LogLib2

LogLib2 is a lightweight logging library for php/ncc projects. This is the successor of the original LogLib library, due
to the many changes in the project structure, it is recommended to use this library instead of the original one.

With LogLib2 you can log events from your application to several different handlers built into the library such as

 - Console Logging: Logs events to `stdout` and `stderr` streams.
 - Descriptor Logging: Logs events to a file descriptor.
 - File Logging: Logs events to a file using a file locking mechanism.
 - HTTP Logging: Logs events to a remote server using HTTP POST requests.
 - TCP Logging: Logs events to a remote TCP server
 - UDP Logging: Logs events to a remote UDP server

Aside from Console logging, all other handlers supports up to 5 different log formats, which are:

 - JSON Lines
 - CSV
 - TXT
 - XML
 - HTML

LogLib2 is designed to be silent-failing, this means that if an error occurs while logging an event, the library will
silently fail and continue to log events, this is to prevent the application from crashing due to a logging error.

## Community

This project and many others from Nosial are available on multiple publicly available and free git repositories at

- [n64](https://git.n64.cc/nosial/loglib2)
- [GitHub](https://github.com/nosial/loglib2)
- [Codeberg](https://codeberg.org/nosial/loglib2)

Issues & Pull Requests are frequently checked and to be referenced accordingly in commits and changes, Nosial remains
dedicated to keep these repositories up to date when possible.

For questions & discussions see the public Telegram community at [@NosialDiscussions](https://t.me/NosialDiscussions).
We do encourage community support and discussions, please be respectful and follow the rules of the community.

## Table of Contents

<!-- TOC -->
* [LogLib2](#loglib2)
  * [Community](#community)
  * [Table of Contents](#table-of-contents)
  * [Installation](#installation)
  * [Compiling](#compiling)
* [Documentation](#documentation)
  * [Environment Variables](#environment-variables)
    * [Console Variables](#console-variables)
    * [Descriptor Variables](#descriptor-variables)
    * [File Variables](#file-variables)
    * [HTTP Variables](#http-variables)
    * [TCP Variables](#tcp-variables)
    * [UDP Variables](#udp-variables)
  * [Log Levels](#log-levels)
    * [Log Filtering](#log-filtering)
      * [Command-Line Override](#command-line-override)
      * [Environment Variable Override](#environment-variable-override)
  * [Creating a Logger](#creating-a-logger)
  * [Logging Events](#logging-events)
  * [Changing Default Configuration](#changing-default-configuration)
  * [Changing Logger Configuration](#changing-logger-configuration)
  * [TCP/UDP Logging Server (python)](#tcpudp-logging-server-python)
    * [Usage:](#usage)
  * [Formatters](#formatters)
    * [AnsiFormat](#ansiformat)
    * [LogFormat](#logformat)
    * [TimestampFormat](#timestampformat)
    * [TraceFormat](#traceformat)
  * [Object Types](#object-types)
    * [Event](#event)
    * [StackTrace](#stacktrace)
    * [ExceptionDetails](#exceptiondetails)
  * [CallTypes](#calltypes)
  * [Log Formats](#log-formats)
    * [JSONL](#jsonl)
    * [CSV](#csv)
    * [TXT](#txt)
    * [XML](#xml)
* [License](#license)
<!-- TOC -->



## Installation

To install LogLib2, you can add the project as a dependency to your project.json file, for example

From the n64 repository
```json
{
  "name": "net.nosial.loglib2",
  "version": "latest",
  "source": "nosial/libs.log2=latest@n64"
}
```

From the github repository
```json
{
  "name": "net.nosial.loglib2",
  "version": "latest",
  "source": "nosial/loglib2=latest@github"
}
```

To install the library from the command line, you can use the following command, use the appropriate source for the
repository you want to install from, you may also use the `--build-source` flag to force ncc to build the package from
source rather than downloading a pre-built package if one is available.

```bash
ncc package install --package=nosial/loglib2=latest@github
```



## Compiling

To compile the library, you can use the Makefile provided in the project, the Makefile provides several targets for
compiling the library, the default target is `all` which compiles both the `release` and `debug` versions of the library.

To compile the library, you can use the following command

```bash
make all
```

To compile the library in `release` mode, you can use the following command

```bash
make release
```

You may also compile manually using the following commands

```bash
ncc build --config=release
```

To install the library, you can use the following command

```bash
ncc package install --package=build/release/net.nosial.loglib2.ncc --skip-dependencies --build-source --reinstall -y
```

------------------------------------------------------------------------------------------------------------------------

# Documentation

The LogLib2 library provides a simple and easy-to-use logging interface for logging events from your application to
several different handlers built into the library such as Console Logging, Descriptor Logging, File Logging, HTTP Logging,
TCP Logging, and UDP Logging. This documentation will provide an overview of the library and how to use it in your
application.

## Environment Variables

Environment Variables can be used to configure the default configuration properties of the library, this is useful when
you want to configure the library without needing to declare the default properties at the start of your application.

### Console Variables

The following environment variables can be used to configure the console logging handler, these variables are used to
configure the console logging handler for all logger instances.


| Variable Name                  | Excepted Value          | Default Value | Description                                                          |
|--------------------------------|-------------------------|---------------|----------------------------------------------------------------------|
| `LOGLIB_CONSOLE_ENABLED`       | `true`, `false`         | `true`        | Enable or disable console logging                                    |
| `LOGLIB_CONSOLE_DISPLAY_NAME`  | `true`, `false`         | `true`        | Enable or disable display name of the application in console logging |
| `LOGLIB_CONSOLE_DISPLAY_LEVEL` | `true`, `false`         | `true`        | Enable or disable display level of the log event in console logging  |
| `LOGLIB_CONSOLE_ANSI_FORMAT`   | `basic`, `none`         | `basic`       | Enable or disable ANSI formatting in console logging                 | 
| `LOGLIB_CONSOLE_TRACE_FORMAT`  | `none`, `basic`, `full` | `basic`       | Enable or disable trace formatting                                   |

### Descriptor Variables

The following environment variables can be used to configure the descriptor logging handler, these variables are used to
configure the descriptor logging handler for all logger instances.

| Variable Name                        | Excepted Value                                                                                          | Default Value  | Description                                            |
|--------------------------------------|---------------------------------------------------------------------------------------------------------|----------------|--------------------------------------------------------|
| `LOGLIB_DESCRIPTOR_ENABLED`          | `true`, `false`                                                                                         | `false`        | Enable or disable descriptor logging                   |
| `LOGLIB_DESCRIPTOR_PATH`             | `string`                                                                                                | `php://stdout` | The path to the descriptor file                        |
| `LOGLIB_DESCRIPTOR_APPEND_NEWLINE`   | `true`, `false`                                                                                         | `true`         | Enable or disable appending a newline to the log entry |
| `LOGLIB_DESCRIPTOR_LOG_FORMAT`       | `jsonl`, `csv`, `txt`, `xml`, `html`                                                                    | `jsonl`        | The format of the log entry                            |
| `LOGLIB_DESCRIPTOR_TIMESTAMP_FORMAT` | `none`, `time_only`, `time_only_millis`, `date_only`, `date_time`. `date_time_millis`, `unix_timestamp` | `date_time`    | The format of the timestamp                            |
| `LOGLIB_DESCRIPTOR_TRACE_FORMAT`     | `none`, `basic`, `full`                                                                                 | `basic`        | Enable or disable trace formatting                     |

### File Variables

The following environment variables can be used to configure the file logging handler, these variables are used to
configure the file logging handler for all logger instances.

| Variable Name                     | Excepted Value                                                                                          | Default Value | Description                                            |
|-----------------------------------|---------------------------------------------------------------------------------------------------------|---------------|--------------------------------------------------------|
| `LOGLIB_FILE_ENABLED`             | `true`, `false`                                                                                         | `false`       | Enable or disable file logging                         |
| `LOGLIB_FILE_DEFAULT_PERMISSIONS` | `octal`                                                                                                 | `0777`        | The default permissions for the log file               |
| `LOGLIB_FILE_PATH`                | `string`                                                                                                | `(tmp)`       | The path to the log file                               |
| `LOGLIB_FILE_APPEND_NEWLINE`      | `true`, `false`                                                                                         | `true`        | Enable or disable appending a newline to the log entry |
| `LOGLIB_FILE_LOG_FORMAT`          | `jsonl`, `csv`, `txt`, `xml`, `html`                                                                    | `jsonl`       | The format of the log entry                            |
| `LOGLIB_FILE_TIMESTAMP_FORMAT`    | `none`, `time_only`, `time_only_millis`, `date_only`, `date_time`. `date_time_millis`, `unix_timestamp` | `date_time`   | The format of the timestamp                            |
| `LOGLIB_FILE_TRACE_FORMAT`        | `none`, `basic`, `full`                                                                                 | `basic`       | Enable or disable trace formatting                     |


### HTTP Variables

The following environment variables can be used to configure the HTTP logging handler, these variables are used to
configure the HTTP logging handler for all logger instances.

| Variable Name                  | Excepted Value                                                                                          | Default Value         | Description                                            |
|--------------------------------|---------------------------------------------------------------------------------------------------------|-----------------------|--------------------------------------------------------|
| `LOGLIB_HTTP_ENABLED`          | `true`, `false`                                                                                         | `false`               | Enable or disable HTTP logging                         |
| `LOGLIB_HTTP_ENDPOINT`         | `string`                                                                                                | `http://0.0.0.0:5131` | The URL to the HTTP endpoint                           |
| `LOGLIB_HTTP_APPEND_NEWLINE`   | `true`, `false`                                                                                         | `true`                | Enable or disable appending a newline to the log entry |
| `LOGLIB_HTTP_LOG_FORMAT`       | `jsonl`, `csv`, `txt`, `xml`, `html`                                                                    | `jsonl`               | The format of the log entry                            |
| `LOGLIB_HTTP_TIMESTAMP_FORMAT` | `none`, `time_only`, `time_only_millis`, `date_only`, `date_time`. `date_time_millis`, `unix_timestamp` | `date_time`           | The format of the timestamp                            |
| `LOGLIB_HTTP_TRACE_FORMAT`     | `none`, `basic`, `full`                                                                                 | `basic`               | Enable or disable trace formatting                     |

### TCP Variables

The following environment variables can be used to configure the TCP logging handler, these variables are used to
configure the TCP logging handler for all logger instances.

| Variable Name                 | Excepted Value                                                                                          | Default Value | Description                                            |
|-------------------------------|---------------------------------------------------------------------------------------------------------|---------------|--------------------------------------------------------|
| `LOGLIB_TCP_ENABLED`          | `true`, `false`                                                                                         | `false`       | Enable or disable TCP logging                          |
| `LOGLIB_TCP_HOST`             | `string`                                                                                                | `             |                                                        |
| `LOGLIB_TCP_PORT`             | `integer`                                                                                               | `5131`        | The port to the TCP server                             |
| `LOGLIB_TCP_APPEND_NEWLINE`   | `true`, `false`                                                                                         | `true`        | Enable or disable appending a newline to the log entry |
| `LOGLIB_TCP_LOG_FORMAT`       | `jsonl`, `csv`, `txt`, `xml`, `html`                                                                    | `jsonl`       | The format of the log entry                            |
| `LOGLIB_TCP_TIMESTAMP_FORMAT` | `none`, `time_only`, `time_only_millis`, `date_only`, `date_time`. `date_time_millis`, `unix_timestamp` | `date_time`   | The format of the timestamp                            |
| `LOGLIB_TCP_TRACE_FORMAT`     | `none`, `basic`, `full`                                                                                 | `basic`       | Enable or disable trace formatting                     |

### UDP Variables

The following environment variables can be used to configure the UDP logging handler, these variables are used to
configure the UDP logging handler for all logger instances.

| Variable Name                 | Excepted Value                                                                                          | Default Value | Description                                            |
|-------------------------------|---------------------------------------------------------------------------------------------------------|---------------|--------------------------------------------------------|
| `LOGLIB_UDP_ENABLED`          | `true`, `false`                                                                                         | `false`       | Enable or disable UDP logging                          |
| `LOGLIB_UDP_HOST`             | `string`                                                                                                | `             |                                                        |
| `LOGLIB_UDP_PORT`             | `integer`                                                                                               | `5131`        | The port to the UDP server                             |
| `LOGLIB_UDP_APPEND_NEWLINE`   | `true`, `false`                                                                                         | `true`        | Enable or disable appending a newline to the log entry |
| `LOGLIB_UDP_LOG_FORMAT`       | `jsonl`, `csv`, `txt`, `xml`, `html`                                                                    | `jsonl`       | The format of the log entry                            |
| `LOGLIB_UDP_TIMESTAMP_FORMAT` | `none`, `time_only`, `time_only_millis`, `date_only`, `date_time`. `date_time_millis`, `unix_timestamp` | `date_time`   | The format of the timestamp                            |
| `LOGLIB_UDP_TRACE_FORMAT`     | `none`, `basic`, `full`                                                                                 | `basic`       | Enable or disable trace formatting                     |


## Log Levels

The LogLevel enumeration in the LogLib2 namespace provides five distinct log levels for logging events, each log level
Depending on the log level, the log entry may be handled differently by the logging handler and or extra information may
be added to the log event, for example, the `WARNING`, `ERROR`, and `CRITICAL` log levels may include an exception object
in the log event.

| Level    | Standard Value | Possible Values                   | Description                                                                                              | Contains Exception |
|----------|----------------|-----------------------------------|----------------------------------------------------------------------------------------------------------|--------------------|
| DEBUG    | `DBG`          | `debug`, `dbg`, `d`, 0            | A debugging event, reserved for being extra verbose about states and events happening in the application | No                 |
| VERBOSE  | `VRB`          | `verbose`, `verb`, `vrb`, `v`, 1  | A verbose event, reserved for being verbose about states and events happening in the application         | No                 |
| INFO     | `INF`          | `info`, `inf`, `i`, 2             | An informational event, reserved for logging general information about the application                   | No                 |
| WARNING  | `WRN`          | `warning`, `warn`, `wrn`, `w`, 3  | A warning event, reserved for logging events that may indicate a potential problem in the application    | Yes                |
| ERROR    | `ERR`          | `error`, `err`, `e`, 4            | An error event, reserved for logging events that indicate an error in the application                    | Yes                |
| CRITICAL | `CRT`          | `critical`, `crit`, `crt`, `c`, 5 | A critical event, reserved for logging events that indicate a critical error in the application          | Yes                |

The values are used to allow the user to specify the log level in a more human-readable format, for example, if you were
to use command-line arguments or environment variables to specify the log level to `debug`, the values `debug`, `dbg`, `d`,
and `0` would all be valid values to specify the log level.

### Log Filtering

By default application's log level filter is set to `INFO`, this means that only log events with a log level of `INFO` or
higher will be processed but everything below `INFO` will be ignored. The filter can easily be overridden by the
command-line arguments or environment variables to enforce all loggers to use a specific log level.

#### Command-Line Override

The log level filter can be overridden by the command-line arguments, for example, if LogLib2 can access the command-line
arguments, it will check for the `--log-level` argument, if the argument is present, it will override the default log
level filter with the value provided in the argument.

```bash
./myapp --log-level=debug
```

#### Environment Variable Override

The log level filter can also be overridden by the environment variable `LOG_LEVEL`, if the environment variable is set,
it will override the default log level filter with the value provided in the environment variable.

```bash
export LOG_LEVEL=debug
./myapp
```

## Creating a Logger

To create a logger, you can use the `Logger` class in the LogLib2 namespace, all loggers require an application name to
be specified, this is used to identify the application that generated the log event.

```php
$logger = new \LogLib2\Logger('com.example.myapp');

// Or specify the log level filter
$logger = new \LogLib2\Logger('com.example.myapp', \LogLib2\LogLevel::DEBUG);
```

The logger instance is designed to be statically defined, this means that you can define the logger instance as a static
variable in your application and use it throughout your application, this is to prevent the need to create a new logger
instance every time you want to log an event.

```php
class MyApp {
    private static ?\LogLib2\Logger $logger=null;

    public static function getLogger() {
        if (self::$logger === null) {
            self::$logger = new \LogLib2\Logger('com.example.myapp');
        } 
   
        return self::$logger;
    }
}
```

## Logging Events

To log an event, you can use the `log` method on the logger instance, you can specify the method to use by using the
appropriate method on the logger instance, for example, to log a debug event, you can use the `debug` method, to log a
verbose event, you can use the `verbose` method, and so on.

`warning`, `error`, and `critical` log levels may include an exception object in the log event, this is to provide more
information about the error that occurred, for example, the exception object may contain the exception name, message,
and stack trace.

```php
$logger = new \LogLib2\Logger('com.example.myapp');
$exception = new \Exception('An error occurred');

$logger->debug('A debugging event occurred');
$logger->verbose('A verbose event occurred');
$logger->info('An informational event occurred');
$logger->warning('A warning event occurred', $exception);
$logger->error('An error event occurred', $exception);
$logger->critical('A critical event occurred', $exception);
```

## Changing Default Configuration

The default configuration can be altered before the rest of the code-base begins to utilize the library, for example
while by default UDP logging is disabled, it can be enabled by altering the default configuration before any logging is
initiated, when libraries/applications begin to initialize their own logging instances, they would inherit the default
configuration that was altered unless they are explicitly configured to be overridden by their own configurations.

```php
\LogLib2\Logger::getDefaultUdpConfiguration()->setEnabled(true);
\LogLib2\Logger::getDefaultUdpConfiguration()->setHost('0.0.0.0');
\LogLib2\Logger::getDefaultUdpConfiguration()->setPort(5131);

\LogLib2\Logger::getDefaultTcpConfiguration()->setEnabled(true);
\LogLib2\Logger::getDefaultTcpConfiguration()->setHost('0.0.0.0');  
\LogLib2\Logger::getDefaultTcpConfiguration()->setPort(5131);

\LogLib2\Logger::getDefaultHttpConfiguration()->setEnabled(true);
\LogLib2\Logger::getDefaultHttpConfiguration()->setUrl('http://0.0.0.0/log');

// This will use the default configuration that was altered above
$logger = new \LogLib2\Logger('com.example.myapp');
$logger->info('An informational event occurred');
```

 > Note: The recommended way to alter the default configuration is to do so before any logging is initiated, or
 > alternatively you can use environment variables to skip the need to alter the default configuration manually.

## Changing Logger Configuration

The logger configuration can be altered by the logger instance, this is useful when you want to override the default
configuration for a specific logger instance, for example, you may want to enable UDP logging for a specific logger
instance but not for the rest of the application.

```php
$logger = new \LogLib2\Logger('com.example.myapp');
$logger->getUdpConfiguration()->setEnabled(true);
$logger->getUdpConfiguration()->setHost('0.0.0.0');
$logger->getUdpConfiguration()->setPort(5131);

$logger->info('An informational event occurred');
```

 > Note: This would override the default configuration for the logger instance only, other logger instances would still
 > use the default configuration unless they are explicitly configured to be overridden by their own configurations.

## TCP/UDP Logging Server (python)

While no fully-fledged logging server is provided with the library, a simple TCP/UDP logging server is provided
with this project which is written in Python without requiring pip dependencies, the server is listens on both
TCP & UDP connections and is only designed to receive JSON formatted log entries with the Unix Timestamp being the
format timestamp. Contributions to improve this server are welcomed.

 > See [server.py](server.py) for the server implementation.

### Usage:

The server can be started by using python to run [server.py](server.py), by default the server listens on port 5131 and
writes log entries to the current working directory under the 'logs' directory. This can be configured using command-line
arguments

```bash
python server.py --port=5131 --working-directory=/path/to/logs
```

Once running, you can configure the UDP configuration of your loggers to use this server, or alternatively use
environment variables to configure the server for all loggers.

```bash
export LOGLIB_UDP_ENABLED=true
export LOGLIB_UDP_HOST=0.0.0.0
export LOGLIB_UDP_PORT=5131
```

And once any logging events have been fired, you should see the server receiving the log entries in real-time. If for
any reason that LogLib fails to send these entries to the server, it will fail silently and re-try next time.

## Formatters

Formatters are used in LogLib to specify the type of format for a log entry property or log format entirely. Some
formats are only applicable to some handlers for example AnsiFormat is only applicable to ConsoleHandler

### AnsiFormat

The AnsiFormat enumeration in the LogLib2 namespace provides two distinct formats for ANSI formatting in console logging,
these formats are used to specify the type of ANSI formatting to use in console logging.

| Format Name | Value   | Description                                                                     |
|-------------|---------|---------------------------------------------------------------------------------|
| `NONE`      | `none`  | (Default) Displays regular console output without any ANSI formatting or colors |
| `BASIC`     | `basic` | Displays regular console output with ANSI formatting and colors                 |


### LogFormat

The LogFormat enumeration allows you to dictate the log output format, some formats has different behaviors but
they all try to follow a compatible format.

| Format Name | Value           |                                                                                                                                                                       |
|-------------|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `JSONL`     | `json`, `jsonl` | JSON Lines format, each log entry is JSON encoded intended to be used for JSONL files or storage mediums                                                              |
| `CSV`       | `csv`           | CSV Format, when using a File handler; if the file doesn't exist a new one will be created with CSV headers included, in other cases CSV rows are simply used instead |
| `TXT`       | `txt`           | TXT Format, a human-readable format that is intended to be used for TXT files or storage mediums                                                                      |
| `XML`       | `xml`           | XML Format, intended to be used for XML files or storage mediums                                                                                                      |
| `HTML`      | `html`          | HTML Format, intended to be used for HTML files or storage mediums                                                                                                    |


### TimestampFormat

The TimestampFormat enumeration allows you to dictate the timestamp format for the log entry, this is used to specify the
type of format to use for the timestamp in the log entry.

| Format Name        | Value                   | Description                                                                         |
|--------------------|-------------------------|-------------------------------------------------------------------------------------|
| `NONE`             | `none`, `0`             | (Default) No timestamp is included in the log entry                                 |
| `TIME_ONLY`        | `time_only`, `1`        | Only the time is included in the log entry (`H:i:s`)                                |
| `TIME_ONLY_MILLIS` | `time_only_millis`, `2` | Only the time is included in the log entry with milliseconds (`H:i:s.u`)            |
| `DATE_ONLY`        | `date_only`, `3`        | Only the date is included in the log entry (`Y-m-d`)                                |
| `DATE_TIME`        | `date_time`, `4`        | The date and time are included in the log entry (`Y-m-d H:i:s`)                     |
| `DATE_TIME_MILLIS` | `date_time_millis`, `5` | The date and time are included in the log entry with milliseconds (`Y-m-d H:i:s.u`) |
| `UNIX_TIMESTAMP`   | `unix_timestamp`, `6`   | The Unix timestamp is included in the log entry (`U`)                               |

### TraceFormat

The TraceFormat enumeration allows you to dictate the trace format for the log entry, this is used to specify the type of
format to use for the trace in the log

| Format Name | Value   | Description                                                                 |
|-------------|---------|-----------------------------------------------------------------------------|
| `NONE`      | `none`  | (Default) No trace is included in the log entry                             |
| `BASIC`     | `basic` | Only the class and method name are included in the log entry trace           |
| `FULL`      | `full`  | The full trace is included in the log entry, including the class and method |


## Object Types

The LogLib2 library provides simplified object types for logging events, exceptions and stack traces. These object types
are designed to be used for serialization for log formats, for example JSON would show an object representation of the
log entry, while TXT would show a human-readable representation of the log entry.

### Event

The Event object type is used to represent a log event, it contains the following properties:

| Property Name    | Value Type                                     | Optional | Description                                                                         |
|------------------|------------------------------------------------|----------|-------------------------------------------------------------------------------------|
| application_name | `string`                                       | No       | The name of the application that created the event                                  |
| timestamp        | `string`                                       | No       | The timestamp of the event, formatted using the [TimestampFormat](#timestampformat) |
| level            | [`LogLevel`](#log-levels) (See standard value) | No       | The level of the logging event, eg; `INF`                                           |
| message          | `string`                                       | No       | The message of the event                                                            |
| trace            | `string`                                       | Yes      | The trace of the executed caller that created the event                             |
| stack_trace      | [`StackTrace[]`](#stacktrace)                  | Yes      | The stack traces of the executed caller that created the event                      |
| exception        | [`ExceptionDetails`](#exceptiondetails)        | Yes      | The exception object of the event                                                   |

### StackTrace

The StackTrace object type is used to represent a stack trace, it contains the following properties:

| Property Name | Value Type              | Optional | Description                             |
|---------------|-------------------------|----------|-----------------------------------------|
| file          | `string`                | Yes      | The executing file of the caller        |
| line          | `integer`               | Yes      | The executing line of the caller        |
| function      | `string`                | Yes      | The executing function of the caller    |
| args          | `mixed[]`               | Yes      | The arguments of the executing function |
| class         | `string`                | Yes      | The executing class of the caller       |
| call_type     | [`CallType`](#calltype) | Yes      | The call type of the caller             |


### ExceptionDetails

The ExceptionDetails object type is used to represent an exception object, it contains the following properties:

| Property Name | Value Type                              | Optional | Description                             |
|---------------|-----------------------------------------|----------|-----------------------------------------|
| name          | `string`                                | No       | The name of the exception               |
| message       | `string`                                | No       | The message of the exception            |
| code          | `integer`                               | Yes      | The code of the exception               |
| file          | `string`                                | Yes      | The file of the exception               |
| line          | `integer`                               | Yes      | The line of the exception               |
| trace         | [`StackTrace[]`](#stacktrace)           | Yes      | The stack trace of the exception        |
| previous      | [`ExceptionDetails`](#exceptiondetails) | Yes      | The previous exception of the exception |

## CallTypes

CallTypes are used to specify the type of call that was made when the log event was created, this is used to provide more
information about the call that was made when the log event was created.

| Call Type       | Value    | Description                                                  |
|-----------------|----------|--------------------------------------------------------------|
| `METHOD_CALL`   | `->`     | A method call was made when the log event was created        |
| `STATIC_CALL`   | `::`     | A static method call was made when the log event was created |
| `FUNCTION_CALL` | `()`     | A function >call was made when the log event was created     |
| `LAMBDA_CALL`   | `λ`      | A lambda call was made when the log event was created        |
| `EVAL_CALL`     | `eval()` | An eval call was made when the log event was created         |

## Log Formats

The LogFormat enumeration in the LogLib2 namespace provides five distinct formats for serializing log entries.
Each format is designed to suit different logging needs and use cases, such as machine-readable formats (JSONL, XML),
human-readable formats (TXT, HTML), or structured formats (CSV).

These log formats are designed to be used for the following handlers:

 - Descriptor Logging
 - File Logging
 - HTTP Logging
 - TCP Logging
 - UDP Logging

But note the common behavior of each handler when serializing log entries:

 - File Logging: Appends a newline (\n) to the serialized log entry automatically.
 - HTTP, UDP, TCP, Descriptor Logging: Does not append a newline (\n) to the serialized log entry.

### JSONL

Produces a single-line JSON representation of the log entry. This format is highly efficient for processing large
volumes of logs as each log entry occupies a single line.

```json
{"timestamp":"16:00:23","level":"ERROR","message":"An error occurred","trace":"Some trace details","exception":{"name":"ExceptionName","message":"Error details"}}
```

JSON Objects follows the same object structure format as the [Event](#event) object type.

### CSV

Produces a CSV representation of the log entry. This format is useful for exporting log entries to a spreadsheet or
database for further analysis.

 > Note: Only the File handler will use CSV headers, this only happens when trying to create a .csv file that doesn't
 > exist, LogLib will automatically create the file with the headers included.
 
The CSV format is as follows:

```csv
timestamp,level,message,trace,exception
16:00:23,ERROR,An error occurred,<trace>,<json encoded exception details>
```

 - `timestamp`: The timestamp of the log entry, formatted using the [TimestampFormat](#timestampformat).
 - `level`: The level of the log entry, eg; `INF`.
 - `message`: The message of the log entry.
 - `trace`: The trace of the event, formatted using the [TraceFormat](#traceformat).
 - `exception`: JSON encoded exception details, formatted using the [ExceptionDetails](#exceptiondetails) object type.


### TXT

Produces a human-readable text representation of the log entry. This format is useful for viewing log entries in a
text editor or terminal.

The TXT format is as follows:

```
05:45:38 [INFO] This is an example log message.
05:45:38 [INFO] This is an example log message.
05:45:38 [INFO] This is an example log message.
05:45:38 [INFO] This is an example log message.
05:45:38 [INFO] This is an example log message.
05:45:38 [INFO] This is an example log message.
05:45:38 [INFO] This is an example log message.
05:45:38 [INFO] This is an example log message.
05:45:39 [INFO] This is an example log message.
05:45:39 [INFO] This is an example log message.
05:45:39 [ERR] test
Exception: This is an example exception. (0)
File: LogLib2/examples/example1.php:22
05:45:39 [INFO] ExampleClass::sleepExample Sleeping for 5 seconds...
05:45:44 [INFO] ExampleClass::sleepExample Finished sleeping for 5 seconds.
05:45:39 [WRN] LogLib2\Logger::LogLib2\{closure} Undefined array key "foo"
Runtime: Undefined array key "foo" (2)
File: LogLib2/examples/example1.php:20
05:45:44 [ERR] LogLib2\Logger::LogLib2\{closure} this is a new exception
Exception: this is a new exception (0)
File: LogLib2/examples/example_class.php:32
  ExampleClassthrowDoubleException (LogLib2/examples/example1.php:29)
Exception: This is an example exception. (0)
File: LogLib2/examples/example_class.php:22
  ExampleClassthrowException (LogLib2/examples/example_class.php:28)
  ExampleClassthrowDoubleException (LogLib2/examples/example1.php:29)
```

### XML

Produces an XML representation of the log entry. This format is useful for exporting log entries to an XML file or
The XML structure follows the same object structure as [Event](#event)

```xml
<event>
  <application_name>Runtime</application_name>
  <timestamp>05:52:58</timestamp>
  <level>WRN</level>
  <message>WRN</message>
  <trace>LogLib2\Logger::LogLib2\{closure}</trace>
  <stack_trace>
    <trace>
      <file>LogLib2/examples/example1.php</file>
      <line>20</line>
      <function>LogLib2\{closure}</function>
      <class>LogLib2\Logger</class>
      <arguments>
        <argument>"2"</argument>
        <argument>"Undefined array key \"foo\""</argument>
        <argument>"LogLib2/examples/example1.php"</argument>
        <argument>"20"</argument>
      </arguments>
    </trace>
  </stack_trace>
  <exception>
    <name>Runtime</name>
    <message>Undefined array key "foo"</message>
    <code>2</code>
    <file>LogLib2/examples/example1.php</file>
    <line>20</line>
  </exception>
</event>
```

```xml

<event>
<application_name>Runtime</application_name>
<timestamp>05:53:03</timestamp>
<level>ERR</level>
<message>ERR</message>
<trace>LogLib2\Logger::LogLib2\{closure}</trace>
<stack_trace>
  <trace>
    <function>LogLib2\{closure}</function>
    <class>LogLib2\Logger</class>
    <arguments>
      <argument>"[OBJECT]"</argument>
    </arguments>
  </trace>
</stack_trace>
<exception>
  <name>Exception</name>
  <message>this is a new exception</message>
  <code>0</code>
  <file>LogLib2/examples/example_class.php</file>
  <line>32</line>
  <stack_trace>
    <trace>
      <file>LogLib2/examples/example1.php</file>
      <line>29</line>
      <function>throwDoubleException</function>
      <class>ExampleClass</class>
    </trace>
  </stack_trace>
  <previous>
    <name>Exception</name>
    <message>This is an example exception.</message>
    <code>0</code>
    <file>LogLib2/examples/example_class.php</file>
    <line>22</line>
    <stack_trace>
      <trace>
        <file>LogLib2/examples/example_class.php</file>
        <line>28</line>
        <function>throwException</function>
        <class>ExampleClass</class>
      </trace>
      <trace>
        <file>LogLib2/examples/example1.php</file>
        <line>29</line>
        <function>throwDoubleException</function>
        <class>ExampleClass</class>
      </trace>
    </stack_trace>
  </previous>
</exception>
</event>
```



------------------------------------------------------------------------------------------------------------------------

# License

The LogLib2 library is licensed under the MIT License, see the [LICENSE](LICENSE) file for more information.