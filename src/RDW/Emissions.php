<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

/**
 * Class Emissions
 *
 * RDW endpoint for vehicle emissions data.
 */
class Emissions implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    /** @var string */
    private $api_base = 'https://opendata.rdw.nl';
    /** @var string */
    private $api_path = 'resource/5w6t-p66a.json';

    /**
     * Allowed query parameters for this endpoint
     * @var array
     */
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

    /** @var array */
    protected $fields = [];
    /** @var string */
    public $request_url = '';
    /** @var array */
    public $query_vars = [];

    /**
     * Optionally enrich data after fetching. Not used for this endpoint.
     * @return object
     */
    public function enrichData(): object
    {
        return $this;
    }
}
