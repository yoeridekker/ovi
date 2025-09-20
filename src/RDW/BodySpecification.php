<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

/**
 * Class BodySpecification
 *
 * RDW endpoint for detailed body specifications for a given vehicle.
 */
class BodySpecification implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/jhie-znh9.json';

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
     * No-op enrichment for BodySpecification endpoint.
     * @return object
     */
    public function enrichData(): object
    {
        return $this;
    }
}
