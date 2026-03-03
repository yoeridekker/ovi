<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ApiTrait;

class TellerstandoordeelTrendToelichting implements ApiInterface
{
    use SanitizationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/jqs4-4kvw.json';

    public $request_url = '';
    public $query_vars = [];
}
