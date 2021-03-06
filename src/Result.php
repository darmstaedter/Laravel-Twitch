<?php

namespace romanzipp\Twitch;

use Exception;
use GuzzleHttp\Psr7\Response;
use romanzipp\Twitch\Helpers\Paginator;

class Result
{
    /**
     * Query successful.
     *
     * @var boolean
     */
    public $success = false;

    /**
     * Guzzle exception, if present.
     *
     * @var mixed|null
     */
    public $exception = null;

    /**
     * Query result data.
     *
     * @var array|\stdClass
     */
    public $data = [];

    /**
     * Total amount of result data.
     *
     * @var integer
     */
    public $total = 0;

    /**
     * Status Code.
     *
     * @var integer
     */
    public $status = 0;

    /**
     * Twitch response pagination cursor.
     *
     * @var \stdClass|null
     */
    public $pagination;

    /**
     * Internal paginator.
     *
     * @var Paginator|null
     */
    public $paginator;

    /**
     * Original Guzzle HTTP Response.
     *
     * @var Response
     */
    public $response;

    /**
     * Original Twitch instance.
     *
     * @var \romanzipp\Twitch\Twitch
     */
    public $twitch;

    /**
     * Constructor,
     *
     * @param Response $response HTTP response
     * @param Exception|mixed $exception Exception, if present
     */
    public function __construct(Response $response, Exception $exception = null)
    {
        $this->response = $response;
        $this->status = $response->getStatusCode();

        $this->success = $exception === null;
        $this->exception = $exception;

        $this->processPayload($response);

        $this->paginator = Paginator::from($this);
    }

    /**
     * Returns whether the query was successful.
     *
     * @return bool Success state
     */
    public function success(): bool
    {
        return $this->success;
    }

    /**
     * Get the response data, also available as public attribute.
     *
     * @return mixed
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Get the response status code.
     *
     * @return int
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Returns the last HTTP or API error.
     *
     * @return string Error message
     */
    public function error(): string
    {
        // TODO Switch Exception response parsing to this->data

        if ($this->exception === null || ! $this->exception->hasResponse()) {
            return 'Twitch API Unavailable';
        }

        $exception = (string) $this->exception->getResponse()->getBody();
        $exception = @json_decode($exception);

        if (property_exists($exception, 'message') && ! empty($exception->message)) {
            return $exception->message;
        }

        return $this->exception->getMessage();
    }

    /**
     * Shifts the current result (Use for single user/video etc. query).
     *
     * @return mixed Shifted data
     */
    public function shift()
    {
        if ( ! is_array($this->data)) {
            return null;
        }

        if ( ! empty($this->data)) {
            $data = $this->data;
            return array_shift($data);
        }

        return null;
    }

    /**
     * Return the current count of items in dataset.
     *
     * @return int Count
     */
    public function count(): int
    {
        if ( ! is_array($this->data)) {
            return 0;
        }

        return count((array) $this->data);
    }

    /**
     * Set the Paginator to fetch the first set of results.
     *
     * @return null|Paginator
     */
    public function first()
    {
        return $this->paginator !== null ? $this->paginator->first() : null;
    }

    /**
     * Set the Paginator to fetch the next set of results.
     *
     * @return null|Paginator
     */
    public function next()
    {
        return $this->paginator !== null ? $this->paginator->next() : null;
    }

    /**
     * Set the Paginator to fetch the last set of results.
     *
     * @return null|Paginator
     */
    public function back()
    {
        return $this->paginator !== null ? $this->paginator->back() : null;
    }

    /**
     * Get rate limit information.
     *
     * @param string|null $key Get defined index
     * @return string|array|null
     */
    public function rateLimit(string $key = null)
    {
        if ( ! $this->response || ! $this->response->hasHeader('Ratelimit-Remaining')) {
            return null;
        }

        $rateLimit = [
            'limit'     => (int) $this->response->getHeaderLine('Ratelimit-Limit'),
            'remaining' => (int) $this->response->getHeaderLine('Ratelimit-Remaining'),
            'reset'     => (int) $this->response->getHeaderLine('Ratelimit-Reset'),
        ];

        if ($key === null) {
            return $rateLimit;
        }

        return $rateLimit[$key];
    }

    /**
     * Insert users in data response.
     *
     * @param string $identifierAttribute Attribute to identify the users
     * @param string $insertTo Data index to insert user data
     * @return self
     */
    public function insertUsers(string $identifierAttribute = 'user_id', string $insertTo = 'user'): self
    {
        $data = $this->data;

        $userIds = collect($data)->map(function ($item) use ($identifierAttribute) {
            return $item->{$identifierAttribute};
        })->toArray();

        if (count($userIds) == 0) {
            return $this;
        }

        $users = collect($this->twitch->getUsersByIds($userIds)->data);

        $dataWithUsers = collect($data)->map(function ($item) use ($users, $identifierAttribute, $insertTo) {
            $item->$insertTo = $users->where('id', $item->{$identifierAttribute})->first();

            return $item;
        });

        $this->data = $dataWithUsers->toArray();

        return $this;
    }

    /**
     * Map response payload to instance attributes.
     *
     * @param \GuzzleHttp\Psr7\Response $response
     */
    protected function processPayload(Response $response): void
    {
        $payload = @json_decode($response->getBody());

        if ($payload === null) {
            return;
        }

        if ( ! property_exists($payload, 'data')) {
            $this->data = $payload;
            return;
        }

        $this->data = $payload->data ?? [];
        $this->total = $payload->total ?? 0;
        $this->pagination = $payload->pagination ?? null;
    }
}
