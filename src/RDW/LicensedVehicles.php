<?php

namespace Ovi\RDW;

use Ovi\Abstracts\ApiIdentifier;
use Ovi\Interfaces\ApiInterface;
use Ovi\RDW\Engines;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ValidationTrait;
use Ovi\Traits\ApiTrait;

use Ovi\RDW\Emissions;
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
    public $query_vars = [];
    public $fields_json = '';
    public $fields = [];

    public function __construct()
    {

    }

    public function mapFields(): object
    {
        $this->fields_json = __DIR__ . '/data/fields.json';
        $this->fields = json_decode(file_get_contents($this->fields_json), true);
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

    public function enrichDataWith($class, $keys = [])
    {
        if (count($this->response) !== 1) return $this;

        $params = [];
        foreach ($this->response as $index => $vehicle) {

            if (count(array_filter($keys, fn($key) => empty($vehicle[$key]))) > 0) {
                return $this;
            }

            $params = array_intersect_key($vehicle, array_flip($keys));

            $request = new $class();
            $data = $request->setQueryArgs($params)->getRequestUrl()->doRequest()->getBody();
            if (!empty($data)) {
                $this->response[$index][$request->getIdentifier()] = $data;
            }
        }

        return $this;
    }

    public function enrichData(): object
    {
        // Multiple results
        if (count($this->response) !== 1) return $this;

        // Single result
        foreach ($this->response as $index => $vehicle) {

            if (!empty($vehicle['variant']) && !empty($vehicle['uitvoering'])) {

                $params = [
                    'eeg_variantcode' => (string)$vehicle['variant'],
                    'eeg_uitvoeringscode' => (string)$vehicle['uitvoering'],
                ];

                $endpoints = [
                    'uitstoot' => new Emissions(),
                    'motoren' => new Engines(),
                    'transmissie' => new Transmission(),
                    'uitvoering' => new VehicleSpecifications(),
                    'handelsnaam' => new ModelInformation(),
                ];

                foreach ($endpoints as $name => $endpoint) {
                    $data = $this->get($endpoint, $params, false);
                    if (!empty($data)) {
                        $this->response[$index][$name] = $data;
                    }
                }
            }

            foreach ($vehicle as $field => $value) {

                if (strpos($field, 'api_gekentekende_voertuigen_') !== false && isset($vehicle['kenteken'])) {
                    $key = str_replace('api_gekentekende_voertuigen_', '', $field);
                    $data = (array)$this->doRequest($value . '?kenteken=' . $vehicle['kenteken'], false);

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

        $params = $emissions = $engines = $engines = $transmission = $uitvoering = $tradename = $vehicle = $field = $value = $faults = $recals = null;

        return $this;
    }

    public function get($class, array $params, bool $multiple = true): array
    {
        $data = $class->setQueryArgs($params)->getRequestUrl()->doRequest()->enrichData()->getBody();
        return false === $multiple && isset($data[0]) ? $data[0] : $data;
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
            'name' => $field,
            'label' => (isset($this->fields[$field]['label']) && !empty($this->fields[$field]['label']) ? $this->fields[$field]['label'] : $field)
        ];
    }
}
