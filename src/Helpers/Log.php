<?php

namespace Ovi\Helpers;

/**
 * Class Log
 *
 * Simple file logger for debugging purposes.
 */
class Log
{
    /**
     * Append a log message to the debug log if it exists and is writable.
     *
     * @param string $message   Message to write.
     * @param string $initiator Class or component name initiating the log.
     * @return void
     */
    public static function write( string $message, string $initiator = 'Unknown' )
    {
        $log_file = dirname(__DIR__) . '/logs/debug.log';
        if( file_exists( $log_file ) && is_writable( $log_file ) )
        {
            $time = date('d-M-Y H:i:s');
            file_put_contents( $log_file, "[$time] : $message ($initiator)" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

    }
}
