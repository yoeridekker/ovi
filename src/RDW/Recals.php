<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

use Ovi\RDW\RecalCodes;
use Ovi\RDW\RecalStatus;

/**
 * Class Recals
 *
 * RDW endpoint for recall actions associated with a vehicle by license plate.
 */
class Recals implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/jct3-vb8s.json';

    private $allowed_params = array(
        'kenteken' => [
            'required' => true,
            'validation' => 'isLicenseplate',
            'sanitization' => 'sanitizeLicenseplate',
            'error_message' => 'Licenseplate can\'t be empty and need to be exactly 6 alpha-numeric characters',
        ],
    );

    public $request_url = '';
    public $query_vars = [];

    public function enrichData() : object
    {
        foreach( $this->response as $index => $recal )
        {
            foreach( $recal as $field => $value )
            {
                if( $field === 'referentiecode_rdw' && $value != '' )
                {
                    $this->response[$index] = array_merge(
                        $this->response[$index],
                        $this->getRecalCode( $value ),
                        $this->getRecalStatus( $this->query_vars['kenteken'], $value )
                    );
                }
            }
        }

        return $this;
    }

    public function getRecalCode( string $code ) : array
    {
        $codes = new RecalCodes();
        return $codes->setQueryArg( 'referentiecode_rdw', $code )->getRequestUrl()->doRequest()->getBody( true );
    }

    public function getRecalStatus( string $kenteken, string $code ) : array
    {
        $status = new RecalStatus();
        return $status->setQueryArgs( ['kenteken' => $kenteken, 'referentiecode_rdw' => $code ] )->getRequestUrl()->doRequest()->getBody( true );
    }


}
