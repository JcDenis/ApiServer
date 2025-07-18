<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer\Endpoint;

use Dotclear\App;
use Dotclear\Plugin\ApiServer\{ ApiServerEndpoint, ApiServerLifetime };

/**
 * @brief       ApiServer blog API endpoint.
 * @ingroup     ApiServer
 *
 * For now, this endpoint experiment anonymous query.
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class BlogEndpoint extends ApiServerEndpoint
{
    public const ID   = 'blog';
    public const AUTH = false;

    protected function callEndpoint(): void
    {
        $this->setContent([
            'name'        => App::blog()->name(),
            'url'         => App::blog()->url(),
            'description' => App::blog()->desc(),
            'update'      => ApiServerLifetime::formatTime(App::blog()->upddt()),
            'nb_posts'    => App::blog()->getPosts([], true)->f(0),
        ]);
    }
}
