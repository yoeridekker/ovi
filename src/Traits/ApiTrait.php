<?php

namespace Ovi\Traits;

use Ovi\Helpers\Helper;
use Ovi\Helpers\Log;

trait ApiTrait
{
    /**
     * HTTP response status code of the last request.
     * @var int
     */
    private $status_code;

    /**
     * Decoded response body from the last request.
     * @var array
     */
    public $response = [];

    private $guzzle_options = [
        'verify' => true,
        'timeout' => 30,
        'headers' => [
            'Accept' => 'application/json',
        ]
    ];

    /**
     * Get the endpoint identifier. Defaults to the short class name in lowercase when not set.
     * @return string
     */
    public function getIdentifier(): string
    {
        $name = property_exists($this, 'identifier') ? (string) $this->identifier : '';
        return $name === '' ? strtolower((new \ReflectionClass($this))->getShortName()) : $name;
    }

    /**
     * Set an option value on this instance.
     *
     * @param mixed $value
     * @param string $option Dot-notation option path.
     * @return object
     * @throws \Exception When option name is empty or does not exist.
     */
    public function setOption($value, string $option): object
    {

        if (empty($option)) {
            throw new \Exception("Option is required");
        }

        $options = explode('.', $option);
        $option_name = array_shift($options);

        if (empty($option_name) || !isset($this->{$option_name})) {
            throw new \Exception("Option '{$option}' is empty or does not exist");
        }

        if (!empty($options)) {
            Helper::set($this->{$option_name}, implode('.', $options), $value);
            return $this;
        }

        $this->{$option_name} = $value;
        return $this;
    }

    /**
     * Bulk set options using an associative array.
     *
     * @param array $params
     * @return object
     */
    public function setOptions(array $params): object
    {
        foreach ($params as $param => $value) {
            $this->setOption($value, $param);
        }
        return $this;
    }

    /**
     * Set a single query argument with optional sanitization for known params.
     *
     * @param string $param
     * @param mixed $value
     * @return object
     */
    public function setQueryArg(string $param, $value): object
    {
        if (empty($param)) {
            throw new \Exception("param is required");
        }

        $sanitizers = property_exists($this, 'sanitizers') ? $this->sanitizers : [];

        if (isset($sanitizers[$param])) {
            $value = $this->sanitizeVar($sanitizers[$param], $value);
        }

        $this->query_vars[$param] = $value;
        return $this;
    }

    /**
     * Set multiple query arguments.
     *
     * @param array $params
     * @return object
     */
    public function setQueryArgs(array $params): object
    {
        foreach ($params as $param => $value) {
            $this->setQueryArg($param, $value);
        }
        return $this;
    }

    /**
     * Get all current query arguments.
     *
     * @return object
     */
    public function getQueryArgs(): object
    {
        return (object)$this->query_vars;
    }

    public function getQueryArg(string $param): string
    {
        return isset($this->query_vars[$param]) ? $this->query_vars[$param] : '';
    }

    public function sanitizeVar($sanitization, $value)
    {
        if (is_string($sanitization) && method_exists($this, $sanitization)) {
            return call_user_func([$this, $sanitization], $value);
        }

        if (is_callable($sanitization)) {
            return call_user_func($sanitization, $value);
        }

        return $value;
    }

    public function getRequestUri(): string
    {
        return $this->request_url;
    }

    public function setResponse(array $response): object
    {
        $this->response = $response;
        return $this;
    }

    public function getRequestUrl(): object
    {
        $this->request_url = sprintf('%s/%s?%s', $this->api_base, $this->api_path, http_build_query($this->query_vars));
        return $this;
    }

    public function doRequest(string $url = '', bool $silent = true): object
    {

        $client = new \GuzzleHttp\Client($this->guzzle_options);
        $request_uri = $url !== '' ? $url : $this->request_url;

        try {
            $request = $client->request('GET', $request_uri);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            Log::write(sprintf('GET %s - %s', $request_uri, $response->getBody()->getContents()), __CLASS__);

            if ($silent) {
                $this->response = (array)[];
                return $this;
            }
            return (object)[];
        }

        $this->status_code = $request->getStatusCode();

        if ($silent) {
            $this->response = (array)json_decode($request->getBody(), true);
            return $this;
        }

        return (object)json_decode($request->getBody(), true);

    }

    public function getStatusCode(): int
    {
        return $this->status_code;
    }

    /**
     * Follow all api_* links in the response concurrently. Sequential fetching
     * cost ~200-300ms per link; a single vehicle carries ~5 links and the
     * kenteken endpoints another 8, so concurrency is the difference between
     * ~3s and well under a second for a cold lookup.
     */
    public function enrichData(): object
    {
        if (empty($this->response)) return $this;

        $jobs = [];
        foreach ($this->response as $index => $record) {
            foreach ($record as $field => $value) {
                if (strpos($field, 'api_') === 0 && !empty($value)) {
                    $jobs[] = ['index' => $index, 'field' => $field, 'url' => $value, 'record' => $record];
                }
            }
        }
        if (empty($jobs)) return $this;

        $client = new \GuzzleHttp\Client($this->guzzle_options);
        $promises = [];
        $endpoints = [];
        foreach ($jobs as $i => $job) {
            $info = \Ovi\RDW\EndpointRegistry::resolveFromUrl($job['url']);
            if ($info) {
                $params = array_intersect_key($job['record'], array_flip($info['link']));
                $parsed = parse_url($job['url']);
                parse_str($parsed['query'] ?? '', $urlParams);
                $params = array_merge($params, $urlParams);

                $endpoint = (new $info['class']())->setQueryArgs($params)->getRequestUrl();
                $endpoints[$i] = $endpoint;
                $promises[$i] = $client->getAsync($endpoint->getRequestUri());
            } else {
                $endpoints[$i] = null;
                $promises[$i] = $client->getAsync($job['url']);
            }
        }

        $results = \GuzzleHttp\Promise\Utils::settle($promises)->wait();

        foreach ($jobs as $i => $job) {
            unset($this->response[$job['index']][$job['field']]);

            $result = $results[$i] ?? null;
            if (($result['state'] ?? '') !== 'fulfilled') continue;

            $body = (array) json_decode($result['value']->getBody(), true);
            $data = $endpoints[$i] !== null
                ? $endpoints[$i]->setResponse($body)->enrichData()->getBody()
                : $body;

            if (!empty($data)) {
                $key = preg_replace('/^api_/', '', $job['field']);
                $this->response[$job['index']][$key] = count($data) > 1 ? $data : $data[0];
            }
        }

        return $this;
    }

    public function getBody(bool $single = false): array
    {
        if ($single && count($this->response) === 1) return $this->response[0];
        return $this->response;
    }

}
