<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Interface\Core\BlogSettingsInterface;
use Dotclear\Plugin\maintenance\Maintenance;

/**
 * @brief       ApiServer backend behaviors.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class BackendBehaviors
{
    /**
     * Maintenance task.
     */
    public static function dcMaintenanceInit(Maintenance $maintenance): void
    {
        $maintenance->addTask(MaintenanceTask\CleanCache::class);
    }

    /**
     * Update user API rate limit.
     */
    public static function adminBeforeUserUpdate(Cursor $cur, string $user_id): void
    {
        $limit = abs((int) $_POST[My::id() . 'rate_limit'] ?: ApiServerRate::getDefaultCallsLimit());

        $cur->user_options[My::id()] = [
            'reset'  => ApiServerRate::getTime(true),
            'limit'  => $limit,
            'remain' => $limit,
        ];
    }

    /**
     * User API rate limit form.
     */
    public static function adminUserForm(?MetaRecord $rs): void
    {
        if (!$rs instanceof MetaRecord) {
            return;
        }
        $res = $rs->option(My::id());

        echo (new Div())
            ->items([
                (new Para())
                    ->items([
                        (new Number(My::id() . 'rate_limit', 10, 9999))
                            ->value((string) (is_array($res) && isset($res['limit']) ? $res['limit'] : ApiServerRate::getDefaultCallsLimit()))
                            ->label((new Label(sprintf(__('API call limit per %d seconds:'), ApiServerRate::getDefaultTimeFrame()), Label::OL_TF))),
                    ]),
            ])
            ->render();
    }

    /**
     * Add blog preferences form.
     *
     * @param   BlogSettingsInterface   $blog_settings  The blog settings
     */
    public static function adminBlogPreferencesFormV2(BlogSettingsInterface $blog_settings): void
    {
        $rs = App::log()->getLogs(['log_table' => My::id() . 'rate', 'limit' => 1]);

        echo (new Fieldset(My::id() . '_params'))
            ->legend(new Legend(My::name()))
            ->fields([
                (new Para())
                    ->items([
                        (new Checkbox(My::id() . 'active', $blog_settings->get(My::id())->get('active')))
                            ->value(1)
                            ->label((new Label(__('Enable public API for this blog'), Label::IL_FT))),
                    ]),
                $rs->isEmpty() ? new None() : (new Para())
                    ->items([
                        (new Checkbox(My::id() . 'reset', false))
                            ->value(1)
                            ->label((new Label(sprintf(__('Reset anonymous API calls limit. (%d calls remaining for next %d seconds)'), (int) $rs->f('log_msg'), ApiServerRate::getDefaultTimeFrame()), Label::IL_FT))),
                    ]),
            ])
            ->render();
    }

    /**
     * Save blog preference.
     *
     * @param   BlogSettingsInterface   $blog_settings  The blog settings
     */
    public static function adminBeforeBlogSettingsUpdate(BlogSettingsInterface $blog_settings): void
    {
        if (!empty($_POST[My::id() . 'reset'])) {
            $rs = App::log()->getLogs(['log_table' => My::id() . 'rate']);
            while ($rs->fetch()) {
                App::log()->delLog((int) $rs->f('log_id'));
            }
        }
        $blog_settings->get(My::id())->put('active', !empty($_POST[My::id() . 'active']), 'boolean');
    }
}
