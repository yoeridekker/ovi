<?php

namespace Ovi\Helpers;

class Log
{
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
