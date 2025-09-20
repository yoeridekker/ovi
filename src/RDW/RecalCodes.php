<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

/**
 * Class RecalCodes
 *
 * RDW endpoint to look up details for a given recall reference code.
 */
class RecalCodes implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/j9yg-7rg9.json';

    private $allowed_params = array(
        'referentiecode_rdw' => [
            'required' => true,
            'validation' => 'isString',
            'error_message' => 'Recal reference can not be empty',
        ],
    );

    public $request_url = '';
    public $query_vars = [];

    /**
     * No-op enrichment for RecalCodes endpoint.
     * @return object
     */
    public function enrichData() : object
    {
        return $this;
    }


}
