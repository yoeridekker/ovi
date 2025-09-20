<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

/**
 * Class FaultCodes
 *
 * RDW endpoint providing human-readable information for fault code identifiers.
 */
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

    /**
     * No-op enrichment for FaultCodes endpoint.
     * @return object
     */
    public function enrichData() : object
    {
        return $this;
    }

}
