<?php

namespace Ovi;

use Ovi\Interfaces\VehiclesInterface;
use Ovi\RDW\LicensedVehicles;

class Vehicles implements VehiclesInterface
{

    public $instance;

    public function __construct()
    {
        $this->instance = new LicensedVehicles();
    }

    public function set_options(array $params = []): object
    {
        $this->instance->setOptions($params);
        return $this;
    }

    public function set_option($value, string $option): object
    {
        $this->instance->setOption($value, $option);
        return $this;
    }

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
