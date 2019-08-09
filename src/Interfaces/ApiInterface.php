<?php

namespace Ovi\Interfaces;

interface ApiInterface
{
    public function setOption( $value, string $option ) : object ;
    public function setOptions( array $params ) : object ;
    public function setQueryArg( string $param, string $value ) : object ;
    public function setQueryArgs( array $params ) : object ;
    public function getQueryArg( string $param ) : string ;
    public function getQueryArgs() : object ;
    public function validateVar( $param, $validation, $value ) ;
    public function sanitizeVar( $sanitization, $value ) ;
    public function validateRequest() : object ;
    public function getRequestUrl() : object;
    public function doRequest( string $url = '', bool $silent = true ) : object ;
    public function enrichData() : object ;
    public function getBody( bool $single = false ) : array ;
}
