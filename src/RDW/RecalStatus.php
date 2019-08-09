<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

class RecalStatus implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/t49b-isb7.json';
    
    private $allowed_params = array(
        'kenteken' => [
            'required' => true,
            'validation' => 'isLicenseplate',
            'sanitization' => 'sanitizeLicenseplate',
            'error_message' => 'Licenseplate can\'t be empty and need to be exactly 6 alpha-numeric characters',
        ],
        'referentiecode_rdw' => [
            'required' => true,
            'validation' => 'isString',
            'error_message' => 'Recal reference can not be empty',
        ],
    );

    public $request_url = '';
    public $query_vars = [];

    public function enrichData() : object 
    {
        return $this;
    }


}
