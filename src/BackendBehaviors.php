<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use ArrayObject, Exception;
use Dotclear\App;
use Dotclear\Database\{Cursor, MetaRecord };
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Fieldset, Form, Img, Label, Legend, Li, Note, Number, Para, Select, Strong, Submit, Text, Ul };
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
            'reset'  => ApiServerRate::getEndTime(),
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
                            ->label((new Label(sprintf(__('API call limit per %d seconds:'), ApiServerRate::getLifeTime()), Label::OL_TF))),
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

        $fields = [
            (new Para())
                ->items([
                    (new Checkbox(My::id() . 'active', $blog_settings->get(My::id())->get('active')))
                        ->value(1)
                        ->label((new Label(__('Enable public API for this blog'), Label::IL_FT))),
                ]),
            (new Para())
                ->items([
                    (new Checkbox(My::id() . 'signup_perm', (bool) $blog_settings->get(My::id())->get('signup_perm')))
                        ->value(1)
                        ->label(new Label(__('Add user permission to use API on sign up'), Label::IL_FT)),
                ]),
        ];

        if (!$rs->isEmpty()) {
            $fields[] = (new Para())
                ->items([
                    (new Checkbox(My::id() . 'reset', false))
                        ->value(1)
                        ->label((new Label(sprintf(__('Reset anonymous API calls limit. (%d calls remaining for next %d seconds)'), (int) $rs->f('log_msg'), ApiServerRate::getLifeTime()), Label::IL_FT))),
                ]);
        }

        if (!defined('APISERVER_DEFAULT_TOKEN_LIFETIME')) {
            $values = [
                __('One minute') => '60',
                __('One hour')   => '3600',
                __('One day')    => '86400',
                __('One week')   => '604800',
                __('One month')  => '2592000',
            ];

            $fields[] = (new Para())
                ->items([
                    (new Select(My::id() . 'token_lifetime'))
                        ->items($values)
                        ->default((string) ((int) $blog_settings->get(My::id())->get('token_lifetime') ?: '3600'))
                        ->label((new Label(__('User token lifetime:'), Label::OUTSIDE_TEXT_BEFORE))),
                ]);
        }

        if (!defined('APISERVER_DEFAULT_RATE_LIFETIME')) {
            $values = [
                __('One minute')      => '60',
                __('Fifteen minutes') => '900',
                __('Half an hour')    => '1800',
                __('One hour')        => '3600',
                __('Two hours')       => '7200',
                __('One day')         => '86400',
            ];

            $fields[] = (new Para())
                ->items([
                    (new Select(My::id() . 'rate_lifetime'))
                        ->items($values)
                        ->default((string) ((int) $blog_settings->get(My::id())->get('rate_lifetime') ?: '3600'))
                        ->label((new Label(__('API calls rate limit period:'), Label::OUTSIDE_TEXT_BEFORE))),
                ]);
        }

        if (!defined('APISERVER_DEFAULT_CACHE_LIFETIME')) {
            $values = [
                __('One minute')      => '60',
                __('Fifteen minutes') => '900',
                __('Half an hour')    => '1800',
                __('One hour')        => '3600',
                __('Two hours')       => '7200',
                __('One day')         => '86400',
            ];

            $fields[] = (new Para())
                ->items([
                    (new Select(My::id() . 'cache_lifetime'))
                        ->items($values)
                        ->default((string) ((int) $blog_settings->get(My::id())->get('cache_lifetime') ?: '3600'))
                        ->label((new Label(__('API server cache lifetime:'), Label::OUTSIDE_TEXT_BEFORE))),
                ]);
        }

        echo (new Fieldset(My::id() . '_params'))
            ->legend(new Legend((new Img(My::icons()[0]))->class('icon-small')->render() . ' ' . My::name()))
            ->fields($fields)
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
        if (!defined('APISERVER_DEFAULT_TOKEN_LIFETIME') && !empty($_POST[My::id() . 'token_lifetime'])) {
            $blog_settings->get(My::id())->put('token_lifetime', (int) $_POST[My::id() . 'token_lifetime'], 'integer');
        }
        if (!defined('APISERVER_DEFAULT_RATE_LIFETIME') && !empty($_POST[My::id() . 'rate_lifetime'])) {
            $blog_settings->get(My::id())->put('rate_lifetime', (int) $_POST[My::id() . 'rate_lifetime'], 'integer');
        }
        if (!defined('APISERVER_DEFAULT_CACHE_LIFETIME') && !empty($_POST[My::id() . 'cache_lifetime'])) {
            $blog_settings->get(My::id())->put('cache_lifetime', (int) $_POST[My::id() . 'cache_lifetime'], 'integer');
        }

        $blog_settings->get(My::id())->put('active', !empty($_POST[My::id() . 'active']), 'boolean');
        $blog_settings->get(My::id())->put('signup_perm', !empty($_POST[My::id() . 'signup_perm']), 'boolean');
    }

    /**
     * Dashboard user options form.
     */
    public static function adminDashboardOptionsForm(): string
    {
        echo
        (new Fieldset(My::id() . '_prefs'))
        ->legend(new Legend((new Img(My::icons()[0]))->class('icon-small')->render() . ' ' . My::name()))
        ->fields([
            (new Para())->items([
                (new Checkbox(My::id() . 'dashboard_statistics', My::prefs()->dashboard_statistics))
                    ->value(1)
                    ->label((new Label(__('Display API server statistics on dashboard'), Label::INSIDE_TEXT_AFTER))),
            ]),
        ])
        ->render();

        return '';
    }

    /**
     * Dashboard user options save.
     */
    public static function adminAfterDashboardOptionsUpdate(): string
    {
        try {
            My::prefs()->put('dashboard_statistics', !empty($_POST[My::id() . 'dashboard_statistics']), App::userWorkspace()::WS_BOOL);
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return '';
    }

    /**
     * Dashboard content display.
     *
     * @param      ArrayObject<int, ArrayObject<int, string>>  $contents  The contents
     */
    public static function adminDashboardContentsV2(ArrayObject $contents): string
    {
        if (My::prefs()->dashboard_statistics) {
            if (!empty($_POST[My::id() . 'dellogs'])) {
                ApiServerLogs::delLogs();
            }

            $lines = [];

            if (($logs = ApiServerLogs::parseLogs()) !== []) {
                foreach ($logs as $key => $value) {
                    $lines[] = (new Li())
                        ->separator(' : ')
                        ->items([
                            (new Strong($key)),
                            (new Text(null, (string) $value)),
                        ]);
                }
            }

            if ($lines === []) {
                $lines[] = (new Li())->text(__('No statistics'));
            }
            $res = (new Div(My::id() . 'statistics'))
                ->class(['box', 'small'])
                ->items([
                    (new Text('h3', (new Img(My::icons()[0]))->class('icon-small')->render() . ' ' . My::name())),
                    (new Note())
                        ->text(sprintf(__('Public API endpoints usage since %s'), ApiServerLogs::parseDate())),
                    (new Ul())
                        ->items($lines),
                    (new Form(My::id() . '-form'))
                        ->method('post')
                        ->action(App::backend()->url()->get('admin.home'))
                        ->items([
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    (new Submit(My::id() . 'dellogs', __('Clear statistics'))),
                                    App::nonce()->formNonce(),
                                ]),
                        ]),
                ])
            ->render();

            $contents->append(new ArrayObject([$res]));
        }

        return '';
    }
}
