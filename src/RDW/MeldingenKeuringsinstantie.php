<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ApiTrait;

class MeldingenKeuringsinstantie implements ApiInterface
{
    use SanitizationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/sgfe-77wx.json';

    private $sanitizers = [
        'kenteken' => 'sanitizeLicenseplate',
    ];

    public $request_url = '';
    public $query_vars = [];
}
