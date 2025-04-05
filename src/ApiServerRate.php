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
     * API calls limit.
     *
     * rate limit per rate reset per token.
     * Can be changed in user preferences options.
     *
     * @var     int     LIMIT
     */
    public const LIMIT = 2000;

    /**
     * API calls limit reset time (in second).
     *
     * @var     int     RESET
     */
    public const RESET = 3600;

    /**
     * User current API calls limit.
     *
     * @var     int     $limit
     */
    private int $limit  = self::LIMIT;

    /**
     * User current API calls remain.
     *
     * @var     int     $remain
     */
    private int $remain = self::LIMIT;

    /**
     * User current API calls reset time.
     *
     * @var     int     $reset
     */
    private int $reset  = 1;

    /**
     * Create new API rate instance.
     *
     * @param   string  $user   The user ID
     * @param   int     $cost   The endpoint call cost
     */
    public function __construct(string $user, int $cost = 0)
    {
        $this->reset  = self::getRateTime();

        // Get user options
        $rate    = App::auth()->getOption(My::id());
        $options = App::auth()->getOptions();

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
            if ($this->remain >= $this->limit) {
                $this->remain = $this->limit;
            }
            if ($this->reset < self::getRateTime(null, false)) {
                $this->reset  = self::getRateTime();
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
            $options[My::id()] = [
                'limit'  => $this->limit,
                'remain' => $this->remain,
                'reset'  => $this->reset,
            ];
            $cur               = App::auth()->openUserCursor();
            $cur->user_options = new ArrayObject($options);

            App::auth()->sudo([App::users(), 'updUser'], $user, $cur);
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
     * Get raet limit reset time.
     */
    public function getReset(): int
    {
        return $this->reset;
    }

    /**
     * Get raet limit reset ISO8601 date.
     */
    public function getResetDate(): string
    {
        return self::formatRateTime($this->getReset());
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
            header('X-RateLimit-Reset: ' . $this->formatRateTime($this->getReset()));
        }
        if ($this->getRemain() < 1) {
            header('Retry-After: ' . ($this->getReset() - $this->getRateTime(null, false)));
        }
    }

    /**
     * Get an UTC TS time.
     *
     * @param   null|int    $time   The time to parse
     * @param   bool        $reset  Add reset time
     */
    public static function getRateTime(?int $time = null, bool $reset = true): int
    {
        $date = new DateTime('@' . ($time ?? time()), new DateTimeZone('UTC'));
        if ($reset) {
            $date->modify(self::RESET . 'sec');
        }

        return (int) $date->format('U');
    }

    /**
     * Get an UTC ISO8601 time.
     *
     * @param   int     $time   The time to parse
     */
    public static function formatRateTime(int $time): string
    {
        $date = new DateTime('@' . $time, new DateTimeZone('UTC'));

        return $date->format('c');
    }
}
