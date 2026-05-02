<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;

/**
 * @brief       ApiServer core logs helper.
 * @ingroup     ApiServer
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerLogs
{
    private static ?MetaRecord $record = null;

    /**
     * Get logs record.
     */
    public static function getLogs(): MetaRecord
    {
        if (is_null(self::$record)) {
            self::$record = App::log()->getLogs(['log_table' => My::id(), 'limit' => 1]);
        }

        return self::$record;
    }

    /**
     * Parse logs date.
     */
    public static function parseDate(): string
    {
        $record = self::getLogs();

        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';
        $log_dt  = !$record->isEmpty() && is_string($log_dt = $record->f('log_dt')) ? $log_dt : 'now';

        return Date::dt2str(__('%Y-%m-%d %H:%M'), $log_dt, $user_tz);
    }

    /**
     * Parse logs into array.
     *
     * @return  array<string, int>  The log.
     */
    public static function parseLogs(): array
    {
        $record = self::getLogs();

        /**
         * @var array<string, int> $logs
         */
        $logs = [];

        $log_msg = !$record->isEmpty() && is_string($log_msg = $record->f('log_msg')) ? json_decode($log_msg, true) : [];
        if (is_array($log_msg) && $log_msg !== []) {
            arsort($log_msg);
            $logs = array_filter($log_msg, fn ($value, $key): bool => is_string($key) && is_int($value), ARRAY_FILTER_USE_BOTH);
        }

        return $logs;
    }

    /**
     * Delete logs.
     */
    public static function delLogs(): void
    {
        $record = self::getLogs();
        if ($record->isEmpty()) {
            return;
        }

        $ids = [];
        while ($record->fetch()) {
            $log_id = is_numeric($log_id = $record->f('log_id')) ? (int) $log_id : 0;
            if ($log_id !== 0) {
                $ids[] = $log_id;
            }
        }

        App::log()->delLogs($ids);
        self::$record = null;
    }

    /**
     * Add a log.
     *
     * This increments log of an endpoint.
     */
    public static function addLog(string $endpoint): void
    {
        $record = self::getLogs();

        $time = !$record->isEmpty() && is_string($log_dt = $record->f('log_dt')) ? (int) strtotime($log_dt) : time();
        $logs = !$record->isEmpty() && is_string($log_msg = $record->f('log_msg')) ? json_decode($log_msg, true) : [];

        if (!is_array($logs)) {
            $logs = [];
        }

        //$time   = $record->isEmpty() ? time() : (int) strtotime($log_dt);
        //$logs   = $record->isEmpty() ? [] : json_decode($record->f('log_msg'), true);

        self::delLogs();

        if (!isset($logs[$endpoint]) || !is_int($logs[$endpoint])) {
            $logs[$endpoint] = 0;
        }

        $logs[$endpoint] += 1;

        $cur = App::log()->openLogCursor();
        $cur->setField('log_table', My::id());
        $cur->setField('log_dt', date('Y-m-d H:i:s', $time));
        $cur->setField('log_msg', json_encode($logs));

        App::log()->addLog($cur);
    }
}
