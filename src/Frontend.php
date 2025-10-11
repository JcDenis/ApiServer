<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       ApiServer module frontend process.
 * @ingroup     ApiServer
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Frontend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            // Add API user permission on new user registration from frontend
            'FrontendSessionAfterSignup' => function (Cursor $cur): void {
                if (My::settings()->get('signup_perm')) {
                    $perms           = App::users()->getUserPermissions($cur->user_id);
                    $perms           = $perms[App::blog()->id()]['p'] ?? [];
                    $perms[My::id()] = true;
                    App::auth()->sudo(App::users()->setUserBlogPermissions(...), $cur->user_id, App::blog()->id(), $perms);
                }
            },
        ]);

        return true;
    }
}
