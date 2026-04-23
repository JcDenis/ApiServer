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
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerRate extends ApiServerLifetime
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
        $this->reset = self::getEndTime();

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
                $log_dt  = is_string($log_dt = $rs->f('log_dt')) ? $log_dt : '';
                $log_msg = is_numeric($log_msg = $rs->f('log_msg')) ? (int) $log_msg : 0;

                $dt    = DateTime::createFromFormat('Y-m-d H:i:s', $log_dt, new DateTimeZone('UTC'));
                $reset = $dt instanceof DateTime ? (int) $dt->format('U') : time();

                $rate['remain'] = $log_msg;
                $rate['reset']  = $reset;
            }
        } else {
            // Get authenticate rate limit
            $rate    = App::auth()->getOption(My::id());
            $options = App::auth()->getOptions();
        }

        // Parse user values
        if (is_array($rate)) {
            if (isset($rate['limit']) && is_numeric($rate['limit'])) {
                $this->limit = abs((int) $rate['limit']);
            }
            if (isset($rate['remain']) && is_numeric($rate['remain'])) {
                $this->remain = abs((int) $rate['remain']);
            }
            if (isset($rate['reset']) && is_numeric($rate['reset'])) {
                $this->reset = abs((int) $rate['reset']);
            }
            if ($this->remain > $this->limit) {
                $this->remain = $this->limit;
            }
            if ($this->reset < self::getStartTime()) {
                $this->reset  = self::getEndTime();
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
                    $log_id = is_numeric($log_id = $rs->f('log_id')) ? (int) $log_id : 0;
                    if ($log_id !== 0) {
                        App::log()->delLog($log_id);
                    }
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

                App::auth()->sudo(App::users()->updUser(...), $user, $cur);
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
            header('X-RateLimit-Reset: ' . self::formatTime($this->getReset()));
        }
        if ($this->getRemain() < 1) {
            header('Retry-After: ' . ($this->getReset() - self::getStartTime()));
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
     * Get default API calls limit.
     *
     * Constant can be defined in Dotclear's config file.
     * The API calls limit can be set per user (in system => user)
     */
    public static function getDefaultCallsLimit(): int
    {
        return defined('API_SERVER_DEFAULT_CALLS_LIMIT') && is_numeric($limit = constant('API_SERVER_DEFAULT_CALLS_LIMIT')) ? (int) $limit : 3600;
    }

    /**
     * Get API rate frame time in seconds.
     */
    public static function getLifeTime(): int
    {
        if (defined('APISERVER_DEFAULT_RATE_LIFETIME')) {
            $lifetime = is_numeric($lifetime = constant('APISERVER_DEFAULT_RATE_LIFETIME')) ? (int) $lifetime : 3600;
        } elseif (is_numeric(My::settings()->get('rate_lifetime'))) {
            $lifetime = (int) My::settings()->get('rate_lifetime');
        } else {
            $lifetime = 3600;
        }

        return $lifetime;
    }
}
