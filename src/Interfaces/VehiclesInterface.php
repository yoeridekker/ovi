<?php

namespace Ovi\Interfaces;

/**
 * Interface VehiclesInterface
 *
 * Defines the public API for the Vehicles facade.
 */
interface VehiclesInterface
{
    /**
     * Set multiple options.
     *
     * @param array $params
     * @return object
     */
    public function set_options( array $params = [] ) : object ;

    /**
     * Set a single option.
     *
     * @param mixed $value
     * @param string $option
     * @return object
     */
    public function set_option( $value, string $option ) : object ;

    /**
     * Execute formatted request.
     *
     * @param array $params
     * @return array
     */
    public function get( array $params = [] );
}
