<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer\Endpoint;

use Dotclear\Plugin\ApiServer\ApiServerEndpoint;
use Dotclear\Plugin\ApiServer\ApiServerException;
use Dotclear\Plugin\ApiServer\ApiServerResponse;

/**
 * @brief       ApiServer user API endpoint.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class CodesEndpoint extends ApiServerEndpoint
{
    public const ID     = 'codes';
    public const FIELDS = [
        'code' => false,
    ];

    protected function callEndpoint(): void
    {
        $codes = ApiServerException::codes();
        $res   = json_decode((string) json_encode($codes), true); // convert key to string
        if ($this->api->getParam('code') !== '' && isset($codes[(int) $this->api->getParam('code')])) {
            $res = [
                'message' => $codes[(int) $this->api->getParam('code')],
                'code'    => $this->api->getParam('code'),
            ];
        }

        $this->sendContent(new ApiServerResponse($res));
    }
}
