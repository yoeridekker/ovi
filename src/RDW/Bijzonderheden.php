<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ApiTrait;

class Bijzonderheden implements ApiInterface
{
    use SanitizationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/7ug8-2dtt.json';

    private $sanitizers = [
        'kenteken' => 'sanitizeLicenseplate',
    ];

    public $request_url = '';
    public $query_vars = [];
}
