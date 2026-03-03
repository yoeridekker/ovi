<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ApiTrait;

class GekentekendVoertuigen implements ApiInterface
{
    use SanitizationTrait;
    use ApiTrait {
        enrichData as traitEnrichData;
    }

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/m9d7-ebf2.json';

    private $sanitizers = [
        'kenteken' => 'sanitizeLicenseplate',
    ];

    public $request_url = '';
    public $query_vars = [];
    public $fields_json = '';
    public $fields = [];

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

        $vehicle = $this->response[0];

        if (count(array_filter($keys, fn($key) => empty($vehicle[$key]))) > 0) {
            return $this;
        }

        $params = array_intersect_key($vehicle, array_flip($keys));
        $request = new $class();
        $data = $request->setQueryArgs($params)->getRequestUrl()->doRequest()->enrichData()->getBody();

        if (!empty($data)) {
            $this->response[0][$request->getIdentifier()] = $data;
        }

        return $this;
    }

    /**
     * Enrich the current response with linked datasets when a single vehicle is found.
     * - Follows dynamic API links embedded in the main dataset via the trait.
     * - Fetches all remaining kenteken-linked datasets.
     *
     * @return object Returns $this for chaining.
     */
    public function enrichData(): object
    {
        if (count($this->response) !== 1) return $this;

        // 1. Generic api_* enrichment (brandstof, voertuigklasse, assen, carrosserie, carrosserie_specificatie)
        $this->traitEnrichData();

        $vehicle = $this->response[0];

        if (empty($vehicle['kenteken'])) return $this;

        $kentekenEndpoints = [
            new SubcategorieVoertuig(),
            new Bijzonderheden(),
            new Rupsbanden(),
            new Keuringen(),
            new MeldingenKeuringsinstantie(),
            new GeconstateerdeGebreken(),
            new TerugroepActieStatus(),
            new ToegevoegdeObjecten(),
        ];

        foreach ($kentekenEndpoints as $endpoint) {
            $data = $this->get($endpoint, ['kenteken' => $vehicle['kenteken']]);
            if (!empty($data)) {
                $this->response[0][$endpoint->getIdentifier()] = $data;
            }
        }

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
