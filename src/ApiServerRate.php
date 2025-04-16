<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use ArrayObject;
use DateTime;
use DateTimeZone;
use Dotclear\App;

/**
 * @brief       ApiServer core rate limit.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerRate
{
    /**
     * User current API calls limit.
     */
    private int $limit;

    /**
     * User current API calls remain.
     */
    private int $remain;

    /**
     * User current API calls reset time.
     */
    private int $reset;

    /**
     * Create new API rate instance.
     *
     * @param   string  $user   The user ID
     * @param   int     $cost   The endpoint call cost
     */
    public function __construct(string $user, int $cost = 0)
    {
        $this->limit = $this->remain = self::getDefaultCallsLimit();
        $this->reset = self::getTime(true);

        if ($user === '') {
            // Get anonymous rate limit
            $rate = [
                'limit'  => $this->limit,
                'remain' => $this->remain,
                'reset'  => $this->reset,
            ];
            // Get remaining call from log
            $rs = App::log()->getLogs([
                'log_table' => My::id() . 'rate',
                'limit'     => 1,
            ]);
            if (!$rs->isEmpty()) {
                $dt             = DateTime::createFromFormat('Y-m-d H:i:s', $rs->f('log_dt'), new DateTimeZone('UTC'));
                $rate['remain'] = (int) $rs->f('log_msg');
                $rate['reset']  = $dt ? $dt->format('U') : time();
            }
        } else {
            // Get authenticate rate limit
            $rate    = App::auth()->getOption(My::id());
            $options = App::auth()->getOptions();
        }

        // Parse user values
        if (is_array($rate)) {
            if (isset($rate['limit'])) {
                $this->limit = abs((int) $rate['limit']);
            }
            if (isset($rate['remain'])) {
                $this->remain = abs((int) $rate['remain']);
            }
            if (isset($rate['reset'])) {
                $this->reset = abs((int) $rate['reset']);
            }
            if ($this->remain > $this->limit) {
                $this->remain = $this->limit;
            }
            if ($this->reset < self::getTime(false)) {
                $this->reset  = self::getTime(true);
                $this->remain = $this->limit;
            }
        }

        // Decrement values on API call
        if ($cost > 0) {
            // API call rate limit is reached
            if ($this->remain < 1) {
                $this->sendHeaders();

                throw new ApiServerException(429);
            }

            $this->remain -= $cost;
            if ($this->remain < 0) {
                $this->remain = 0;
            }

            if ($user === '') {
                // Clean old logs
                while (App::log()->getLogs(['log_table' => My::id() . 'rate'])->fetch()) {
                    App::log()->delLog((int) $rs->f('log_id'));
                }

                // Set anonymous rate limit
                $cur            = App::log()->openLogCursor();
                $cur->log_table = My::id() . 'rate';
                $cur->log_msg   = $this->remain;
                $cur->log_dt    = self::formatTime($this->reset, 'Y-m-d H:i:s');

                App::log()->addLog($cur);
            } else {
                $options[My::id()] = [
                    'limit'  => $this->limit,
                    'remain' => $this->remain,
                    'reset'  => $this->reset,
                ];

                // Set authenticate rate limit
                $cur               = App::auth()->openUserCursor();
                $cur->user_options = new ArrayObject($options);

                App::auth()->sudo([App::users(), 'updUser'], $user, $cur);
            }
        }
    }

    /**
     * Send API rate limit HTTP headers.
     *
     * API rate headers follows something like :
     * https://developer.atlassian.com/cloud/jira/platform/rate-limiting/#rate-limit-related-headers
     */
    public function sendHeaders(): void
    {
        if ($this->getLimit() > 1) {
            header('X-RateLimit-NearLimit: ' . (($this->getRemain() * 100 / $this->getLimit()) < 20 ? '1' : '0'));
            header('X-RateLimit-Limit: ' . $this->getLimit());
            header('X-RateLimit-Remaining: ' . $this->getRemain());
            header('X-RateLimit-Reset: ' . static::formatTime($this->getReset()));
        }
        if ($this->getRemain() < 1) {
            header('Retry-After: ' . ($this->getReset() - static::getTime(false)));
        }
    }

    /**
     * Get call limit.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Get remaining calls.
     */
    public function getRemain(): int
    {
        return $this->remain;
    }

    /**
     * Get rate limit reset time.
     */
    public function getReset(): int
    {
        return $this->reset;
    }

    /**
     * Get rate limit reset ISO8601 date.
     */
    public function getResetDate(): string
    {
        return self::formatTime($this->getReset());
    }

    /**
     * Get an UTC TS time.
     *
     * @param   bool    $reset  Add reset time
     */
    public static function getTime(bool $reset = true): int
    {
        $date = new DateTime('now', new DateTimeZone('UTC'));
        if ($reset) {
            $date->modify(self::getDefaultTimeFrame() . 'sec');
        }

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

    /**
     * Get default API calls limit.
     *
     * Constant can be defined in Dotclear's config file.
     * The API calls limit can be set per user (in system => user)
     */
    public static function getDefaultCallsLimit(): int
    {
        return (int) (defined('API_SERVER_DEFAULT_CALLS_LIMIT') ? API_SERVER_DEFAULT_CALLS_LIMIT : 2000);
    }

    /**
     * Get default API calls limit time frame in seconds.
     *
     * Constant can be defined in Dotclear's config file.
     */
    public static function getDefaultTimeFrame(): int
    {
        return (int) (defined('APISERVER_DEFAULT_TIME_FRAME') ? APISERVER_DEFAULT_TIME_FRAME : 3600);
    }
}
