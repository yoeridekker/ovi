<?php

namespace Ovi;

use Ovi\Interfaces\VehiclesInterface;
use Ovi\RDW\LicensedVehicles;

/**
 * Class Vehicles
 *
 * Facade for interacting with RDW LicensedVehicles endpoint. Provides a simple API
 * to set options and retrieve formatted or raw RDW data.
 */
class Vehicles implements VehiclesInterface
{
    /**
     * Underlying RDW LicensedVehicles instance.
     * @var LicensedVehicles
     */
    public $instance;

    /**
     * Vehicles constructor.
     */
    public function __construct()
    {
        $this->instance = new LicensedVehicles();
    }

    /**
     * Set multiple options on the underlying API instance.
     *
     * @param array $params Associative array of option paths to values (supports dot notation).
     * @return object Returns $this for chaining.
     */
    public function set_options(array $params = []): object
    {
        $this->instance->setOptions($params);
        return $this;
    }

    /**
     * Set a single option on the underlying API instance.
     *
     * @param mixed $value  Value to set.
     * @param string $option Option path (supports dot notation).
     * @return object Returns $this for chaining.
     */
    public function set_option($value, string $option): object
    {
        $this->instance->setOption($value, $option);
        return $this;
    }

    /**
     * Execute the request and return formatted response.
     *
     * @param array $params Query parameters.
     * @return array Formatted response. When a single result is returned, it returns the first item.
     */
    public function get(array $params = [])
    {
        return $this->instance->setQueryArgs($params)
            ->getRequestUrl()
            ->doRequest()
            ->enrichData()
            ->mapFields()
            ->formatData()
            ->getBody(true);
    }

    /**
     * Execute the request and return the raw (unformatted) response.
     *
     * @param array $params Query parameters.
     * @return array Raw response. When a single result is returned, it returns the first item.
     */
    public function get_raw(array $params = [])
    {
        return $this->instance->setQueryArgs($params)
            ->getRequestUrl()
            ->doRequest()
            ->enrichData()
            ->mapFields()
            ->getBody(true);
    }
}
