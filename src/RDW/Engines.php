<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

/**
 * Class Engines
 *
 * RDW endpoint for engine specifications associated with vehicle type approvals.
 */
class Engines implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/g2s6-ehxa.json';

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

    /**
     * No-op enrichment for Engines endpoint.
     * @return object
     */
    public function enrichData(): object
    {
        return $this;
    }
}
