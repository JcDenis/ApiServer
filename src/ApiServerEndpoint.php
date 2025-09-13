<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\App;
use Dotclear\Helper\Network\Http;

/**
 * @brief       ApiServer core endpoint.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerEndpoint
{
    /**
     * Endpoint ID.
     *
     * @var 	string 	ID
     */
    public const ID = '';

    /**
     * Endpoint API supported versions.
     *
     * @var 	array<int, string> 	VERSIONS
     */
    public const VERSIONS = [
        'v1',
    ];

    /**
     * Endpoint required registerd user.
     *
     * @var     bool    AUTH
     */
    public const AUTH = true;

    /**
     * Endpoint used arguments.
     *
     * This is the list of POST parameters that endpoint could use,
     * Set FIELDS[parmeter] to true if it is required.
     *
     * @var 	array<string, bool> 	FIELDS
     */
    public const FIELDS = [];

    /**
     * API Endpoint call cost for rate limit.
     *
     * @var     int    RATE
     */
    public const RATE = 1;

    /**
     * API response detail level.
     *
     * @var     int    LEVEL
     */
    public const LEVEL = 1;

    /**
     * API endpoint use cache.
     *
     * @var     bool    CACHE
     */
    public const CACHE = true;

    /**
     * Authenticated user.
     */
    protected ApiServerToken $token;

    /**
     * Rate limit handler.
     */
    protected ApiServerRate $rate;

    /**
     * Cache handler.
     */
    protected ApiServerCache $cache;

    /**
     * Create API endpoint instance.
     *
     * @param   ApiServer   $api    The api server instance
     */
    public function __construct(
        protected ApiServer $api
    ) {
        $this->checkEndpoint();
        $this->checkUser();
        $this->checkRate();
        $this->checkCache();
        $this->callEndpoint();
    }

    /**
     * Check endpoint additional requirements.
     *
     * API endpoint SHOULD overload this method.
     */
    protected function checkEndpoint(): void
    {
        //
    }

    /**
     * Check user authorization.
     *
     * Auth endpoint overloads this method to authenticate user.
     */
    protected function checkUser(): void
    {
        $this->token = ApiServerToken::newFromHeaders();
        $this->setUser($this->token->user);
    }

    /**
     * Get current user ID.
     */
    public function getUser(): string
    {
        return $this->token->user ?? '';
    }

    /**
     * Check user permissions.
     *
     * Used to authenticate user or check user authorization.
     * * User permissions are per blog
     * * User MUST have ApiServer
     * * User status MUST be valid
     */
    protected function setUser(string $username, ?string $password = null): void
    {
        // User is allready checked
        if (is_null($password) && $this->token->user !== '' && $this->token->user === $username) {
            // Only load user info
            if (App::auth()->checkUser($username, null, null, false) === true) {
                return;
            }
            // Check user perms
        } elseif (App::auth()->checkUser($username, $password, null, false) === true
         && App::auth()->check(My::id(), App::blog()->id())                 === true
         && !App::status()->user()->isRestricted((int) App::auth()->getInfo('user_status'))
         && !App::auth()->mustChangePassword()
        ) {
            $this->token = ApiServerToken::newFromUser((string) App::auth()->userID());

            return;
        }

        if (static::AUTH) {
            // User is not authorized
            throw new ApiServerException(109);
        }
    }

    /**
     * Check user rate limit.
     */
    protected function checkRate(): void
    {
        $this->rate = new ApiServerRate($this->token->user, static::RATE);
    }

    /**
     * Check endpoint response cache.
     */
    protected function checkCache(): void
    {
        $this->cache = new ApiServerCache($this->api, static::CACHE);
        $cache       = $this->cache->readCache();
        if ($cache->code !== 110) {
            $this->sendContent($cache);
        }
    }

    /**
     * Quick throw exception.
     *
     * @see     ApiServerException
     *
     * @param   int     $code       The exception code
     * @param   string  $message    The custom status message
     */
    protected static function setException(int $code, string $message = ''): void
    {
        throw new ApiServerException($code, $message);
    }

    /**
     * Quick send content.
     *
     * @see     sendContent
     *
     * @param   array<string, mixed>    $content    The response content array
     */
    protected function setContent(array $content): void
    {
        $this->sendContent(new ApiServerResponse($content));
    }

    /**
     * Send response.
     *
     * @param   ApiServerResponse   $content    The reponse content instance
     */
    protected function sendContent(ApiServerResponse $content): void
    {
        // Write cache
        $this->cache->writeCache($content);

        // Send API headers
        $this->api->sendHeaders();

        // Send rate limit headers
        $this->rate->sendHeaders();

        // Send cache headers
        $this->cache->sendHeaders();

        // Send status header
        Http::head($content->code);

        // Send content
        echo $content->encode();

        exit;
    }

    /**
     * Call API endpoint.
     *
     * API endpoint MUST overload this method.
     */
    protected function callEndpoint(): void
    {
        throw new ApiServerException(200, 'Nothing to return');
    }
}
