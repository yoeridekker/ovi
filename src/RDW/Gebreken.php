<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ApiTrait;

class Gebreken implements ApiInterface
{
    use SanitizationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/hx2c-gt7k.json';

    public $request_url = '';
    public $query_vars = [];
}
