<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\App;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       ApiServer install class.
 * @ingroup     ApiServer
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Upgrade from version < 0.13: move users rate limits from user options to user preferences
            $current = App::version()->getVersion(My::id());
            if ($current !== '' && version_compare($current, '0.13', '<')) {
                self::moveUserOptionsToPreferences();
            }
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }

    /**
     * Move users rate limits from (deprecated) user options to user preferences.
     */
    private static function moveUserOptionsToPreferences(): void
    {
        $table = App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME;

        $sql = new SelectStatement();
        $rs  = $sql
            ->columns(['user_id', 'user_options'])
            ->from($table)
            ->where($sql->like('user_options', '%' . My::id() . '%'))
            ->select();

        if (is_null($rs)) {
            return;
        }

        while ($rs->fetch()) {
            $user_id = $rs->strField('user_id');
            $options = @unserialize($rs->strField('user_options'));
            if ($user_id === '' || !is_array($options) || !isset($options[My::id()])) {
                continue;
            }

            // Copy rate limit to user preferences
            $rate = $options[My::id()];
            if (is_array($rate)) {
                $limit  = isset($rate['limit'])  && is_numeric($rate['limit']) ? abs((int) $rate['limit']) : ApiServerRate::getDefaultCallsLimit();
                $remain = isset($rate['remain']) && is_numeric($rate['remain']) ? abs((int) $rate['remain']) : $limit;
                $reset  = isset($rate['reset'])  && is_numeric($rate['reset']) ? abs((int) $rate['reset']) : ApiServerRate::getEndTime();

                ApiServerRate::setUserRate($user_id, $limit, $remain, $reset);
            }

            // Remove rate limit from user options
            unset($options[My::id()]);

            $upd = new UpdateStatement();
            $upd
                ->ref($table)
                ->set('user_options = ' . $upd->quote(serialize($options)))
                ->where('user_id = ' . $upd->quote($user_id))
                ->update();
        }
    }
}
