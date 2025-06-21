<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use ArrayObject;
use Dotclear\App;
use Throwable;

/**
 * @brief       ApiServer core.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServer
{
    /**
     * Endpoint API current version.
     *
     * @var     string  VERSION
     */
    public const VERSION = 'v1';

    /**
     * The requested URI arguments.
     *
     * @var     array<int, string>  $get
     */
    private array $get = [];

    /**
     * The sanitized requested POST parameters.
     *
     * @var    array<string, string>    $post
     */
    private array $post = [];

    /**
     * The registred endpoints.
     *
     * @var    array<string, string>    $endpoints
     */
    private array $endpoints = [];

    /**
     * The requested endpoint.
     */
    private string $endpoint = '';

    /**
     * The requested API version.
     */
    private string $version = '';

    /**
     * Create a new API server instance.
     *
     * @param   string  $args   The URI arguments
     */
    public function __construct(string $args)
    {
        try {
            // Cleanup URI arguments
            if (str_starts_with($args, '/')) {
                $args = substr($args, 1);
            }
            if (str_ends_with($args, '/')) {
                $args = substr($args, 0, -1);
            }
            $args = explode('/', $args);

            // Set properties
            $this->endpoint = array_shift($args) ?: '';
            $this->get      = $args;
            $this->version  = $this->getVersionFromHeaders();

            // Check API activation
            if (!My::settings()->get('active')) {
                throw new ApiServerException(101);
            }

            // Check API version
            if (!in_array($this->version, ApiServerEndpoint::VERSIONS)) {
                throw new ApiServerException(103);
            }

            // Register all endpoints (endpoints list is sent on authentication)
            $this->addDefaultEndpoints();
            $this->addEndpoints();

            // Check if called endpoint exists
            if (!isset($this->endpoints[$this->endpoint])) {
                throw new ApiServerException(102);
            }
            $class = $this->endpoints[$this->endpoint];

            // Check POST params. If an argument is present it MUST not be empty !!!
            foreach ($class::FIELDS as $field => $required) {
                if (($required || isset($_POST[$field])) && empty($_POST[$field])) {
                    throw new ApiServerException(104);
                }
                if (!empty($_POST[$field]) && (is_int($_POST[$field]) || is_string($_POST[$field]))) {
                    $this->post[(string) $field] = (string) $_POST[$field];
                }
            }

            // Load API endpoint
            new $class($this);

            // Should not be here
            throw new ApiServerException(105);
        } catch (Throwable $e) {
            throw new ApiServerException((int) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Add default endpoints.
     */
    private function addDefaultEndpoints(): void
    {
        $this->endpoints = [
            Endpoint\AuthEndpoint::ID      => Endpoint\AuthEndpoint::class,
            Endpoint\UserEndpoint::ID      => Endpoint\UserEndpoint::class,
            Endpoint\EndpointsEndpoint::ID => Endpoint\EndpointsEndpoint::class,
            Endpoint\CodesEndpoint::ID     => Endpoint\CodesEndpoint::class,
            Endpoint\BlogEndpoint::ID      => Endpoint\BlogEndpoint::class,
        ];
    }

    /**
     * Register endpoints.
     *
     * First come, first serv, an endpoint is not registered if its ID is already taken.
     */
    private function addEndpoints(): void
    {
        /**
         * @var        ArrayObject<int|string, string>
         */
        $endpoints = new ArrayObject();

        # --BEHAVIOR-- ApiServerAddEndpoint -- ArrayObject
        App::behavior()->callBehavior(My::id() . 'AddEndpoint', $endpoints);

        foreach ($endpoints as $endpoint) {
            if (is_subclass_of($endpoint, ApiServerEndpoint::class)
                && in_array($this->version, $endpoint::VERSIONS)
                && !isset($this->endpoints[(string) $endpoint::ID])
            ) {
                $this->endpoints[(string) $endpoint::ID] = $endpoint;
            }
        }
    }

    /**
     * Get called endpoint ID.
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Get registered endpoints.
     *
     * @return  array<string, string>   The endpoint ID/class name
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * Get a API called URI arg.
     *
     * @param   int     $arg    The arg number
     */
    public function getArg(int $arg): string
    {
        return $this->get[$arg] ?? '';
    }

    /**
     * Get API called URI args.
     *
     * @return  array<int, string>
     */
    public function getArgs(): array
    {
        return $this->get;
    }

    /**
     * Get a API called POST parameter.
     *
     * Returns an empty string if key is not found.
     *
     * @param   string  $key    The search key
     */
    public function getParam(string $key): string
    {
        return $this->post[$key] ?? '';
    }

    /**
     * Get API called POST parameters.
     *
     * @return  array<string, string>
     */
    public function getParams(): array
    {
        return $this->post;
    }

    /**
     * Get used API version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Send API related HTTP headers.
     */
    public static function sendHeaders(): void
    {
        header('User-Agent: ' . My::id() . '/' . self::VERSION);
        header('Content-type: application/json');
    }

    /**
     * Get called API version from HTTP headers.
     *
     * If no version are requested, use current one.
     */
    private function getVersionFromHeaders(): string
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                $name = (string) $name;
                if (str_starts_with($name, 'HTTP_') && is_string($value)) {
                    $headers[str_replace(' ', '-', str_replace('_', ' ', substr($name, 5)))] = $value;
                }
            }
        }
        $headers = array_change_key_case($headers, CASE_LOWER);

        return $headers['x-api-version'] ?? self::VERSION;
    }
}
