<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

use Ovi\RDW\Emissions;
use Ovi\RDW\Engines;
use Ovi\RDW\Transmission;
use Ovi\RDW\VehicleSpecifications;
use Ovi\RDW\ModelInformation;
use Ovi\RDW\Faults;
use Ovi\RDW\Recals;

class LicensedVehicles implements ApiInterface
{

    use SanitizationTrait;
    use ValidationTrait;
    use ApiTrait;

    public $api_base = 'https://opendata.rdw.nl';
    public $api_path = 'resource/m9d7-ebf2.json';

    public $allowed_params = array(
        'kenteken' => [
            'required' => false,
            'validation' => 'isLicenseplate',
            'sanitization' => 'sanitizeLicenseplate',
            'error_message' => 'Licenseplate can\'t be empty and need to be exactly 6 alpha-numeric characters',
        ],
        'voertuigsoort' => [
            'validation' => 'isString',
        ],
        'merk' => [
            'validation' => 'isString',
        ],
        'handelsbenaming' => [
            'validation' => 'isString',
        ],
        'vervaldatum_apk' => [
            'validation' => 'isDate',
        ],
        'datum_tenaamstelling' => [
            'validation' => 'isDate',
        ],
        'bruto_bpm' => [
            'validation' => 'isInt',
        ],

        // General
        '$limit' => [
            'validation' => 'isInt',
            'sanitization' => 'sanitizeInt',
        ],
        '$offset' => [
            'validation' => 'isInt',
            'sanitization' => 'sanitizeInt',
        ],
    );

    public $request_url = '';
    public $query_vars  = [];
    public $fields_json = '';
    public $fields      = [];

    public function mapFields(): object
    {
        $this->fields_json  = __DIR__ . '/data/fields.json';
        $this->fields       = json_decode(file_get_contents($this->fields_json), true);
        $this->mapFieldsRecursively($this->response);
        file_put_contents($this->fields_json, json_encode($this->fields, JSON_PRETTY_PRINT));
        return $this;
    }

    public function mapFieldsRecursively($array)
    {
        foreach ($array as $index => $element) {

            if (!is_int($index) && !isset($this->fields[$index])) {
                $this->fields[$index] = ['label' => '', 'format' => ''];
            }

            if (is_array($element) || is_object($element)) {
                $this->mapFieldsRecursively($element);
            }
        }
    }

    public function enrichData(): object
    {
        // Multiple results
        if (count($this->response) !== 1) return $this;

        // Single result
        foreach ($this->response as $index => $vehicle) {

            if (!empty($vehicle['typegoedkeuringsnummer']) && !empty($vehicle['variant']) && !empty($vehicle['uitvoering'])) {
                $params = [
                    // 'eu_type_goedkeuringssleutel'   => (string) $vehicle['typegoedkeuringsnummer'], // Formatting is wrong.
                    'eeg_variantcode'               => (string) $vehicle['variant'],
                    'eeg_uitvoeringscode'           => (string) $vehicle['uitvoering'],
                ];

                $emissions = $this->get(new Emissions(), $params);
                if (!empty($emissions)) {
                    $this->response[$index]['uitstoot'] = $emissions;
                }

                $engines = $this->get(new Engines(), $params);
                if (!empty($engines)) {
                    $this->response[$index]['motoren'] = $engines;
                }

                $transmission = $this->get(new Transmission(), $params);
                if (!empty($transmission)) {
                    $this->response[$index]['transmissie'] = $transmission;
                }

                $uitvoering = $this->get(new VehicleSpecifications(), $params);
                if (!empty($uitvoering)) {
                    $this->response[$index]['uitvoering'] = $uitvoering;
                }

                $tradename = $this->get(new ModelInformation(), $params);
                if (!empty($tradename)) {
                    $this->response[$index]['handelsnaam'] = $tradename;
                }
            }

            foreach ($vehicle as $field => $value) {

                if (strpos($field, 'api_gekentekende_voertuigen_') !== false && isset($vehicle['kenteken'])) {
                    $key    = str_replace('api_gekentekende_voertuigen_', '', $field);
                    $data   = (array) $this->doRequest($value . '?kenteken=' . $vehicle['kenteken'], false);

                    if (!empty($data)) {
                        $this->response[$index][$key] = count($data) > 1 ? $data : $data[0];
                    }

                    unset($this->response[$index][$field]);
                }

                if ($field === 'kenteken' && !empty($value)) {

                    $faults = $this->get(new Faults(), [$field => $value]);
                    if (!empty($faults)) {
                        $this->response[$index]['gebreken'] = $faults;
                    }

                    $recals = $this->get(new Recals(), [$field => $value]);
                    if (!empty($recals)) {
                        $this->response[$index]['terugroep_acties'] = $recals;
                    }
                }
            }
        }

        foreach ($this->response[$index] as $vehicle_index => $vehicle_data) {
            $this->response[$index]['dataset'][$vehicle_index] = is_array($vehicle_data) && 1 === count($vehicle_data) ? $vehicle_data[0] : $vehicle_data;
        }

        $params = $emissions = $engines = $engines = $transmission = $uitvoering = $tradename = $vehicle = $field = $value = $faults = $recals = null;

        return $this;
    }

    public function get($class, array $params): array
    {
        return $class->setQueryArgs($params)->getRequestUrl()->doRequest()->enrichData()->getBody();
    }

    public function formatData()
    {
        array_walk_recursive($this->response, [$this, 'formatDataRecursive']);
        return $this;
    }

    public function formatDataRecursive(&$item, $field)
    {
        $sanitize = isset($this->fields[$field]['format']) && method_exists($this, $this->fields[$field]['format']) ? call_user_func([$this, $this->fields[$field]['format']], $item) : $item;
        $item = [
            'value' => $sanitize,
            'name'  => $field,
            'label' => (isset($this->fields[$field]['label']) && !empty($this->fields[$field]['label']) ? $this->fields[$field]['label'] : $field)
        ];
    }
}
