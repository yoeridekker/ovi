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

/**
 * Class LicensedVehicles
 *
 * RDW primary endpoint for licensed vehicles. Supports querying, enriching with
 * related endpoints (emissions, engines, transmission, specifications, model info,
 * faults and recalls), mapping fields, and formatting the response structure.
 */
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
	/**
	 * @var array|array[]|mixed
	 */
	public $response;

	public function __construct()
    {

    }

    /**
     * Map and persist field labels and formatters by inspecting the latest response.
     * @return object Returns $this for chaining.
     */
    public function mapFields(): object
    {
        $this->fields_json = __DIR__ . '/data/fields.json';
        $this->fields = json_decode(file_get_contents($this->fields_json), true);
        $this->mapFieldsRecursively($this->response);
        file_put_contents($this->fields_json, json_encode($this->fields, JSON_PRETTY_PRINT));
        return $this;
    }

    /**
     * Recursively scan a nested array/object to collect encountered field names
     * and initialize entries in $this->fields when missing.
     *
     * @param array|object $array Response data to scan.
     * @return void
     */
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

    /**
     * When current result is a single vehicle, call a related endpoint to enrich
     * the response with extra data using the provided keys as query args.
     *
     * @param object|string $class Endpoint class name or instance to invoke.
     * @param array $keys Keys to extract from the current vehicle to pass as query.
     * @return object Returns $this for chaining.
     */
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

    /**
     * Enrich the current response with linked datasets when a single vehicle is found.
     * - Adds emissions, engines, transmission, specifications, model info when possible.
     * - Follows dynamic API links embedded in the main dataset.
     * - Adds faults and recall actions by kenteken.
     *
     * @return object Returns $this for chaining.
     */
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

    /**
     * Helper to execute a request against a related endpoint.
     *
     * @param object $class Instance of an endpoint implementing ApiInterface.
     * @param array $params Query parameters to pass.
     * @param bool $multiple When false, return only the first item if present.
     * @return array The response data.
     */
    public function get($class, array $params, bool $multiple = true): array
    {
        $data = $class->setQueryArgs($params)->getRequestUrl()->doRequest()->enrichData()->getBody();
        return false === $multiple && isset($data[0]) ? $data[0] : $data;
    }

    /**
     * Walk the response recursively and convert each primitive into a standardized
     * structure containing value, name and label.
     *
     * @return $this
     */
    public function formatData()
    {
        array_walk_recursive($this->response, [$this, 'formatDataRecursive']);
        return $this;
    }

    /**
     * Format a single field/value into the standardized output object.
     *
     * @param mixed $item The value to be formatted (passed by reference).
     * @param string|int $field Field name/key.
     * @return void
     */
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
