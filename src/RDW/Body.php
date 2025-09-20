<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

/**
 * Class Body
 *
 * RDW endpoint for body (carrosserie) information for a vehicle.
 */
class Body implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/vezc-m2t6.json';

    private $allowed_params = array(
        'kenteken' => [
            'required' => true,
            'validation' => 'isLicenseplate',
            'sanitization' => 'sanitizeLicenseplate',
            'error_message' => 'Licenseplate can\'t be empty and need to be exactly 6 alpha-numeric characters',
        ],
    );

    protected $fields = [];
    public $request_url = '';
    public $query_vars = [];

    /**
     * No-op enrichment for Body endpoint.
     * @return object
     */
    public function enrichData(): object
    {
        return $this;
    }
}
