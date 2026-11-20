<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Exception;

/**
 * @brief       ApiServer core exception.
 * @ingroup     ApiServer
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerException extends Exception
{
    /**
     * Send API Exception.
     *
     * @param   int     $code       The status code
     * @param   string  $message    The custom status message
     */
    public function __construct(int $code, string $message = '')
    {
        $codes = self::codes();
        if (!isset($codes[$code])) {
            $code = 100;
        }

        if ($message === '') {
            $message = $codes[$code];
        }

        // Count the call
        ApiServer::countCall(self::httpCode($code));

        // API code is in response content, HTTP status follows it
        http_response_code(self::httpCode($code));
        ApiServer::sendHeaders();
        echo (new ApiServerResponse(code: $code, message: $message))->encode();
        exit(1);
    }

    /**
     * Get API codes and messages.
     *
     * @return  array<int, string>
     */
    public static function codes(): array
    {
        return [
            100 => __('Unknown error'),
            101 => __('Service is down for maintenance'),
            102 => __('Method Not Allowed'),
            103 => __('The specified API version is not supported'),
            104 => __('Missing parameter'),
            105 => __('No content'),
            106 => __('Not found'),
            107 => __('Contribution is temporaly closed'),
            108 => __('Submission failed'),
            109 => __('Unauthorized'),
            200 => __('Ok'),
            429 => __('API rate limit reach.'),
        ];
    }

    /**
     * Get HTTP status code of an API code.
     *
     * API codes from 100 to 110 are not HTTP codes and must not be sent as is.
     */
    public static function httpCode(int $code): int
    {
        return match ($code) {
            101, 107 => 503,
            102      => 405,
            103, 104 => 400,
            105, 106 => 404,
            108      => 422,
            109      => 401,
            200      => 200,
            429      => 429,
            default  => 500,
        };
    }
}
