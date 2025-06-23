<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;

/**
 * @brief       ApiServer core logs helper.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerLogs
{
    /**
     * @var     null|MetaRecord  $record
     */
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

        return Date::dt2str(__('%Y-%m-%d %H:%M'), $record->isEmpty() ? 'now' : $record->f('log_dt'), App::auth()->getInfo('user_tz'));
    }

    /**
     * Parse logs into array.
     *
     * @return  array<string, int>  The log.
     */
    public static function parseLogs(): array
    {
        $record = self::getLogs();
        $logs   = $record->isEmpty() ? [] : json_decode($record->f('log_msg'), true);
        arsort($logs);

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
            $ids[] = $record->f('log_id');
        }
        App::log()->delLogs($ids);
    }

    /**
     * Add a log.
     *
     * This increments log of an endpoint.
     */
    public static function addLog(string $endpoint): void
    {
        $record = self::getLogs();
        $time   = $record->isEmpty() ? time() : (int) strtotime($record->f('log_dt'));
        $logs   = $record->isEmpty() ? [] : json_decode($record->f('log_msg'), true);

        self::delLogs();

        if (!isset($logs[$endpoint])) {
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
