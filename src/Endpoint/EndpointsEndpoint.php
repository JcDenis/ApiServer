<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer\Endpoint;

use Dotclear\Plugin\ApiServer\ApiServerEndpoint;
use Dotclear\Plugin\ApiServer\ApiServerResponse;

/**
 * @brief       ApiServer user API endpoint.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class EndpointsEndpoint extends ApiServerEndpoint
{
    public const ID   = 'endpoints';
    public const RATE = 0;

    protected function callEndpoint(): void
    {
        $res = [];
        foreach ($this->api->getEndpoints() as $id => $class) {
            $res[$id] = $class::FIELDS;
        }

        $this->sendContent(new ApiServerResponse($res));
    }
}
