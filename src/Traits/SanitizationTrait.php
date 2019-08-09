<?php 

namespace Ovi\Traits;

trait SanitizationTrait
{
    /**
     * Sanitization functions
     */
    public function sanitizeLicenseplate( $value )
    {
        return strtoupper( preg_replace("/[^a-zA-Z0-9]+/", "", $value ) );
    }

    public function sanitizeInt( $value )
    {
        return (int) $value;
    }

    public function sanitizeString( $value )
    {
        return (string) $value;
    }

    public function sanitizeDate( $value )
    {
        $year   = (int) substr( $value, 0, 4);
        $month  = (int) substr( $value, 4, 2);
        $day    = (int) substr( $value, 6, 2);
        $date   = strtotime( sprintf('%s-%s-%s', $year, $month, $day ) );
        return date('d/m/Y', $date);
    }
}