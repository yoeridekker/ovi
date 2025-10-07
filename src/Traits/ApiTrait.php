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

    private $guzzle_options = array(
        'verify' => true,
        'timeout' => 0,
        'headers' => [
            //'X-App-Token' => 'test',
            'Accept' => 'application/json',
        ]
    );

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
     * Set a single query argument with validation and sanitization based on allowed params.
     *
     * @param string $param
     * @param string $value
     * @return object
     * @throws \Exception When param/value invalid or param not allowed.
     */
    public function setQueryArg(string $param, string $value): object
    {
        if (empty($param)) {
            throw new \Exception("param is required");
        }

        if (empty($value)) {
            throw new \Exception("value is required");
        }

        if (isset($this->allowed_params[$param]['sanitization'])) {
            $value = $this->sanitizeVar($this->allowed_params[$param]['sanitization'], $value);
        }

        if (isset($this->allowed_params[$param]['validation'])) {
            $this->validateVar($param, $this->allowed_params[$param], $value);
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

    public function validateVar($param, $validation, $value)
    {

        $valid = true;

        if (is_string($validation['validation']) && method_exists($this, $validation['validation'])) {
            $valid = call_user_func([$this, $validation['validation']], $value);
        }

        if (is_callable($validation['validation'])) {
            $valid = call_user_func($validation['validation'], $value);
        }

        if (!$valid) {
            $exception = isset($validation['error_message']) ? $validation['error_message'] : "Validation for {$param} failed ({$validation['validation']})";
            throw new \Exception($exception);
        }

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

    public function validateRequest(): object
    {
        //var_dump(['allowed' => $this->allowed_params, 'set' => $this->query_vars]);
        foreach ($this->allowed_params as $field => $field_object) {
            if (isset($field_object['required']) && $field_object['required'] && !isset($this->query_vars[$field])) {
                var_dump($this);
                throw new \Exception("param {$field} is required");
            }
        }
        return $this;
    }

    public function getRequestUrl(): object
    {
        $this->validateRequest();
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

    public function getBody(bool $single = false): array
    {
        if ($single && count($this->response) === 1) return $this->response[0];
        return $this->response;
    }

}
