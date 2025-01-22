<?php

    namespace LogLib2\Enums;

    enum LogHandlers : string
    {
        case CONSOLE = 'console';
        case FILE = 'file';
        case TCP = 'tcp';
        case UDP = 'udp';
        case HTTP = 'http';
    }
