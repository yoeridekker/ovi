<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

use Ovi\RDW\FaultCodes;

/**
 * Class Faults
 *
 * RDW endpoint for reporting technical inspection faults per license plate.
 */
class Faults implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/2u8a-sfar.json';

    private $allowed_params = array(
        'kenteken' => [
            'required' => true,
            'validation' => 'isLicenseplate',
            'sanitization' => 'sanitizeLicenseplate',
            'error_message' => 'Licenseplate can\'t be empty and need to be exactly 6 alpha-numeric characters',
        ],
    );

    protected $fields = [
        'meld_datum_door_keuringsinstantie',
        'soort_erkenning_omschrijving',
        'aantal_gebreken_geconstateerd',
        'gebrek_details'
    ];
    public $request_url = '';
    public $query_vars = [];

    /**
     * Enrich each fault record with detailed information by resolving the fault code.
     * @return object Returns $this for chaining.
     */
    public function enrichData() : object
    {
        foreach( $this->response as $index => $vehicle )
        {
            foreach( $vehicle as $field => $value )
            {
                if( $field === 'gebrek_identificatie' ){
                    $this->response[$index] = array_merge( $this->response[$index], $this->getFaultCode( $value ) );
                }
            }
        }
        return $this;
    }

    /**
     * Fetch detailed information for a given fault code.
     *
     * @param string $faultcode Fault code identifier.
     * @return array The resolved fault details.
     */
    public function getFaultCode( string $faultcode ) : array
    {
        $faults = new FaultCodes();
        return $faults->setQueryArg( 'gebrek_identificatie', $faultcode )->getRequestUrl()->doRequest()->getBody( true );
    }

}
