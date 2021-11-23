<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

class Emissions implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/5w6t-p66a.json';

    private $allowed_params = array(
        'eu_type_goedkeuringssleutel' => [
            'required' => false,
        ],
        'eeg_variantcode' => [
            'required' => true,
        ],
        'eeg_uitvoeringscode' => [
            'required' => true,
        ]
    );

    protected $fields = [];
    public $request_url = '';
    public $query_vars = [];

    public function enrichData(): object
    {
        return $this;
    }
}
