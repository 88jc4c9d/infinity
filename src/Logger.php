<?php

namespace Infinity;

class Logger
{
    /**
     * Log a message to both stdout and syslog.
     *
     * @param string $message
     */
    public function log(string $message) {
        echo $message, PHP_EOL;
        syslog(LOG_INFO, $message);
    }
}