<?php 

namespace Ovi\Interfaces;

interface VehiclesInterface
{
    public function set_options( array $params = [] ) : object ;
    public function set_option( $value, string $option ) : object ;
    public function get( array $params = [] );
}
