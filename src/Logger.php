<?php

namespace Infinity;

class Logger
{
    /**
     * @param string $message
     */
    public function log(string $message) {
        echo $message, PHP_EOL;
        syslog(LOG_INFO, $message);
    }
}