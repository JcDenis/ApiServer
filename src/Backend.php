<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       ApiServer module backend process.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Behaviors
        App::behavior()->addBehaviors([
            'dcMaintenanceInit'             => BackendBehaviors::dcMaintenanceInit(...),
            'adminBeforeUserUpdate'         => BackendBehaviors::adminBeforeUserUpdate(...),
            'adminUserForm'                 => BackendBehaviors::adminUserForm(...),
            'adminBlogPreferencesFormV2'    => BackendBehaviors::adminBlogPreferencesFormV2(...),
            'adminBeforeBlogSettingsUpdate' => BackendBehaviors::adminBeforeBlogSettingsUpdate(...),
        ]);

        return true;
    }
}
