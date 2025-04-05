<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer\Endpoint;

/**
 * @brief       ApiServer authentication API endpoint.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class AuthEndpoint extends UserEndpoint
{
    public const ID     = 'auth';
    public const RATE   = 0;
    public const CACHE  = false;
    public const FIELDS = [
        'username' => true,
        'password' => true,
    ];

    protected function checkUser(): void
    {
        $this->setUser($this->api->getParam('username'), $this->api->getParam('password'));
    }
}
