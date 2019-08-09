<?php 

namespace Ovi\Traits;

trait ValidationTrait
{
    /**
     * Validation functions
     */
    public function isInt( $value )
    {
        return is_int( $value );
    }

    public function isLicenseplate( $value )
    {
        return is_string( $value ) && !empty( $value ) && strlen( $value ) === 6;
    }

    public function isString( $value )
    {
        return is_string( $value );
    }

    public function isDate( $value )
    {
        if( 8 !== strlen( $value ) ) return false;

        $year   = (int) substr( $value, 0, 4);
        $month  = (int) substr( $value, 4, 2);
        $day    = (int) substr( $value, 6, 2);

        return checkdate ( $month , $day , $year );

    }
}
