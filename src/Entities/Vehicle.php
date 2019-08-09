<?php

namespace Ovi\Entities;

class Vehicle {

    protected static $mapping = [
        'foo' => 'bar'
    ];

    protected $data = [];
    protected $casts = [
        'foo' => 'carbon'
    ];

    public function __get($key){
        // @todo isset key?
        $value = $this->data[$key] ?? null;

        $value = $this->tryCast($key, $value);
        $getMethod = 'get' . ucfirst($key) . 'Attribute';
        if(method_exists($this, $getMethod)){
            $value = $this->$getMethod($value);
        }

        return $value;
    }

    protected function tryCast($key, $value){
        if(!isset($this->casts[$key])){
            return $value;
        }

        switch($this->casts[$key]){
            case 'carbon': return new \Carbon\Carbon($value);
            default: return $value;
        }
    }

    protected function getFooAttribute($init){
        return $init->format('Y-m-d');
    }
}


//$vehicle->foo == Y-m-d