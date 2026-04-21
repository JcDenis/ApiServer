<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\App;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/**
 * @brief       ApiServer core token.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerToken extends ApiServerLifetime
{
    /**
     * Token version.
     *
     * @var     string  VERSION
     */
    public const VERSION = ApiServer::VERSION;

    /**
     * JWT algorithm.
     *
     * @var     string  ALGORITHM
     */
    public const ALGORITHM = 'HS256';

    /**
     * Create user token instance.
     *
     * @param   string  $user   The token user ID
     * @param   int     $time   The token timestamp
     * @param   int     $reset  The token reset timestamp
     */
    public function __construct(
        public readonly string $user,
        public readonly int $time,
        public readonly int $reset,
    ) {
    }

    /**
     * Encode user token.
     *
     * @return  string  The authorization bearer header value.
     */
    public function encode(): string
    {
        return JWT::encode(
            [
                'iat'  => $this->time,
                'exp'  => $this->reset,
                'user' => $this->user,
            ],
            App::config()->masterKey(),
            self::ALGORITHM,
            My::id() . self::VERSION
        );
    }

    /**
     * Decode token.
     *
     * @param   string  $bearer     The authorization bearer header value.
     */
    public static function decode(string $bearer): self
    {
        try {
            $decode = JWT::decode(
                $bearer,
                [My::id() . self::VERSION => new Key(App::config()->masterKey(), self::ALGORITHM)]
            );

            $user = is_string($user = $decode->user) ? $user : '';
            $iat  = is_numeric($iat = $decode->iat) ? (int) $iat : 0;
            $exp  = is_numeric($exp = $decode->exp) ? (int) $exp : 0;

            return new self($user, $iat, $exp);
        } catch (Throwable) {
            return self::newFromUser('');
        }
    }

    /**
     * Build token from user ID.
     *
     * @param   string  $user   The user ID
     */
    public static function newFromUser(string $user): self
    {
        return new self($user, self::getStartTime(), self::getEndTime());
    }

    /**
     * Build token from headers.
     */
    public static function newFromHeaders(): self
    {
        $headers = getallheaders();

        return self::decode(isset($headers['Authorization']) && is_string($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '');
    }

    /**
     * Get user token lifetime in seconds.
     */
    public static function getLifeTime(): int
    {
        if (defined('APISERVER_DEFAULT_TOKEN_LIFETIME')) {
            $lifetime = is_numeric($lifetime = constant('APISERVER_DEFAULT_TOKEN_LIFETIME')) ? (int) $lifetime : 3600;
        } elseif (is_numeric(My::settings()->get('token_lifetime'))) {
            $lifetime = (int) My::settings()->get('token_lifetime');
        } else {
            $lifetime = 3600;
        }

        return $lifetime;
    }
}
