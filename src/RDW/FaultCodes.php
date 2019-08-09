<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

class FaultCodes implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/tbph-ct3j.json';
    
    private $allowed_params = array(
        'gebrek_identificatie' => [
            'required' => true,
            'validation' => 'isString|hasMinLenght:1',
        ],
    );

    public $request_url = '';
    public $query_vars = [];

    public function enrichData() : object 
    {
        return $this;
    }

}
