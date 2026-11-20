<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\App;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Date;
use Throwable;

/**
 * @brief       ApiServer core statistics helper.
 * @ingroup     ApiServer
 *
 * One record per blog and endpoint, with the number of calls
 * that succeeded and failed. Counters are incremented in SQL,
 * so simultaneous calls are all counted.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerStats
{
    /**
     * Statistics table name (without prefix).
     *
     * @var     string  TABLE_NAME
     */
    public const TABLE_NAME = 'apiserver_stat';

    /**
     * Max length of an endpoint ID.
     *
     * @var     int     ENDPOINT_LENGTH
     */
    public const ENDPOINT_LENGTH = 64;

    /**
     * Count a call of an endpoint, on current blog.
     *
     * @param   string  $endpoint   The endpoint ID
     * @param   bool    $success    True if call succeeded, false on error
     */
    public static function addCall(string $endpoint, bool $success): void
    {
        if ($endpoint === '' || strlen($endpoint) > self::ENDPOINT_LENGTH || !App::blog()->isDefined()) {
            return;
        }

        try {
            // Existing counter
            if (self::increment($endpoint, $success)) {
                return;
            }

            // First call since reset
            $cur = App::db()->con()->openCursor(self::table());
            $cur->setStrField('blog_id', App::blog()->id());
            $cur->setStrField('stat_endpoint', $endpoint);
            $cur->setIntField('stat_success', $success ? 1 : 0);
            $cur->setIntField('stat_error', $success ? 0 : 1);
            $cur->setStrField('stat_dt', date('Y-m-d H:i:s'));
            $cur->insert();
        } catch (Throwable) {
            // Record created by a simultaneous call
            try {
                self::increment($endpoint, $success);
            } catch (Throwable) {
                // Statistics never break an API response
            }
        }
    }

    /**
     * Get statistics of current blog, most called endpoints first.
     *
     * @return  array<string, array{success: int, error: int}>  The statistics by endpoint ID
     */
    public static function getStats(): array
    {
        $stats = [];

        $sql = new SelectStatement();
        $rs  = $sql
            ->columns(['stat_endpoint', 'stat_success', 'stat_error'])
            ->from(self::table())
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->select();

        if (is_null($rs)) {
            return $stats;
        }

        while ($rs->fetch()) {
            $stats[$rs->strField('stat_endpoint')] = [
                'success' => $rs->intField('stat_success'),
                'error'   => $rs->intField('stat_error'),
            ];
        }

        uasort($stats, fn (array $a, array $b): int => ($b['success'] + $b['error']) <=> ($a['success'] + $a['error']));

        return $stats;
    }

    /**
     * Get date of first counted call since reset, in user timezone.
     *
     * Empty string if there are no statistics.
     */
    public static function getDate(): string
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->column($sql->min('stat_dt', 'stat_dt'))
            ->from(self::table())
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->select();

        $dt = is_null($rs) || $rs->isEmpty() ? '' : $rs->strField('stat_dt');
        if ($dt === '') {
            return '';
        }

        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

        return Date::dt2str(__('%Y-%m-%d %H:%M'), $dt, $user_tz);
    }

    /**
     * Delete statistics of current blog.
     */
    public static function delStats(): void
    {
        $sql = new DeleteStatement();
        $sql
            ->from(self::table())
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->delete();
    }

    /**
     * Get statistics table name with prefix.
     */
    private static function table(): string
    {
        return App::db()->con()->prefix() . self::TABLE_NAME;
    }

    /**
     * Increment counter of an existing record.
     *
     * @return  bool    False if there is no record
     */
    private static function increment(string $endpoint, bool $success): bool
    {
        $field = $success ? 'stat_success' : 'stat_error';

        $sql = new UpdateStatement();
        $sql
            ->ref(self::table())
            ->set($field . ' = ' . $field . ' + 1')
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('stat_endpoint = ' . $sql->quote($endpoint))
            ->update();

        return App::db()->con()->changes() > 0;
    }
}
