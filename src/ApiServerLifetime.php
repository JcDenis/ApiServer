<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use ArrayObject;
use DateTime;
use DateTimeZone;
use Dotclear\App;

/**
 * @brief       ApiServer core timestamp helper.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerLifetime
{
    /**
     * Get lifetime in seconds.
     */
    public static function getLifeTime(): int
    {
        return 3600;
    }

    /**
     * Get UTC TS start time.
     */
    public static function getStartTime(): int
    {
        $date = new DateTime('now', new DateTimeZone('UTC'));

        return (int) $date->format('U');
    }

    /**
     * Get UTC TS end time.
     */
    public static function getEndTime(): int
    {
        $date = new DateTime('now', new DateTimeZone('UTC'));
        $date->modify(static::getLifeTime() . 'sec');

        return (int) $date->format('U');
    }

    /**
     * Get a formated UTC date from time (default to ISO8601).
     *
     * $format MUST be compatible with https://www.php.net/manual/en/datetime.format.php
     *
     * @param   int     $time       The time to parse
     * @param   string  $format     The returned format
     */
    public static function formatTime(int $time, string $format = 'c'): string
    {
        return (new DateTime('@' . $time, new DateTimeZone('UTC')))->format($format);
    }
}
