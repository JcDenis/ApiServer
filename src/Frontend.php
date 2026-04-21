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
                    $user_id = is_string($user_id = $cur->user_id) ? $user_id : '';
                    if ($user_id !== '') {
                        $perms           = App::users()->getUserPermissions($user_id);
                        $perms           = $perms[App::blog()->id()]['p'] ?? [];
                        $perms[My::id()] = true;
                        App::auth()->sudo(App::users()->setUserBlogPermissions(...), $user_id, App::blog()->id(), $perms);
                    }
                }
            },
        ]);

        return true;
    }
}
