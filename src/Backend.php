<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       ApiServer module backend process.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class Backend
{
    use TraitProcess;

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
            'dcMaintenanceInit'                => BackendBehaviors::dcMaintenanceInit(...),
            'adminBeforeUserUpdate'            => BackendBehaviors::adminBeforeUserUpdate(...),
            'adminUserForm'                    => BackendBehaviors::adminUserForm(...),
            'adminBlogPreferencesFormV2'       => BackendBehaviors::adminBlogPreferencesFormV2(...),
            'adminBeforeBlogSettingsUpdate'    => BackendBehaviors::adminBeforeBlogSettingsUpdate(...),
            'adminDashboardOptionsForm'        => BackendBehaviors::adminDashboardOptionsForm(...),
            'adminAfterDashboardOptionsUpdate' => BackendBehaviors::adminAfterDashboardOptionsUpdate(...),
            'adminDashboardContentsV2'         => BackendBehaviors::adminDashboardContentsV2(...),
        ]);

        return true;
    }
}
