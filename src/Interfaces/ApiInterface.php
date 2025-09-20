<?php

namespace Ovi\Interfaces;

/**
 * Interface ApiInterface
 *
 * Contract for RDW API endpoint classes.
 */
interface ApiInterface
{
    /**
     * Unique identifier for the endpoint/class.
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Set an option on the request object or on one of its nested option arrays.
     * @param mixed $value
     * @param string $option
     * @return object
     */
    public function setOption($value, string $option): object;

    /**
     * Bulk set options.
     * @param array $params
     * @return object
     */
    public function setOptions(array $params): object;

    /**
     * Set a single query parameter with validation and sanitization.
     * @param string $param
     * @param string $value
     * @return object
     */
    public function setQueryArg(string $param, string $value): object;

    /**
     * Set multiple query parameters.
     * @param array $params
     * @return object
     */
    public function setQueryArgs(array $params): object;

    /**
     * Get a single query argument value.
     * @param string $param
     * @return string
     */
    public function getQueryArg(string $param): string;

    /**
     * Get all query arguments.
     * @return object
     */
    public function getQueryArgs(): object;

    /**
     * Validate a value using configured rules.
     * @param string $param
     * @param array|mixed $validation
     * @param mixed $value
     * @return void
     */
    public function validateVar($param, $validation, $value);

    /**
     * Sanitize a value using configured rules.
     * @param string|callable $sanitization
     * @param mixed $value
     * @return mixed
     */
    public function sanitizeVar($sanitization, $value);

    /**
     * Validate the overall request before sending.
     * @return object
     */
    public function validateRequest(): object;

    /**
     * Build request URL for the current query.
     * @return object
     */
    public function getRequestUrl(): object;

    /**
     * Execute HTTP GET request.
     * @param string $url Optional override URL.
     * @param bool $silent When true, swallow client exceptions and return empty response.
     * @return object|array When silent: $this; otherwise decoded response body.
     */
    public function doRequest(string $url = '', bool $silent = true): object;

    /**
     * Enrich the fetched data with related endpoints when applicable.
     * @return object
     */
    public function enrichData(): object;

    /**
     * Get the response body.
     * @param bool $single If true and exactly one item exists, return that item.
     * @return array
     */
    public function getBody(bool $single = false): array;
}
