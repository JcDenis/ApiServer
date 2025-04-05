<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer\Endpoint;

use ArrayObject;
use Dotclear\App;
use Dotclear\Plugin\ApiServer\ApiServerEndpoint;
use Dotclear\Plugin\ApiServer\ApiServerRate;
use Dotclear\Plugin\ApiServer\ApiServerResponse;
use Dotclear\Plugin\ApiServer\My;

/**
 * @brief       ApiServer user API endpoint.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class UserEndpoint extends ApiServerEndpoint
{
    public const ID    = 'user';
    public const CACHE = false;

    protected function callEndpoint(): void
    {
        $content = [
            'name'        => App::auth()->getInfo('user_cn'),
            'token'       => $this->token->encode(),
            'token_reset' => ApiServerRate::formatRateTime($this->token->reset),
            'rate_limit'  => $this->rate->getLimit(),
            'rate_remain' => $this->rate->getRemain(),
            'rate_reset'  => $this->rate->getResetDate(),
        ];

        // Additonal user info
        $more = new ArrayObject();

        # --BEHAVIOR-- ApiServerUserEndpointContent -- ApiServerEndpoint, ArrayObject
        App::behavior()->callBehavior(My::id() . 'UserEndpointContent', $this, $more);

        foreach ($more as $key => $value) {
            if (!array_key_exists($key, $content) && (is_string($value) || is_int($value))) { // @phpstan-ignore-line
                $content[$key] = $value;
            }
        }

        $this->sendContent(new ApiServerResponse($content));
    }
}
