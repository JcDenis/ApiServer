<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\Helper\Network\Http;
use Exception;

/**
 * @brief       ApiServer core exception.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
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
        $head  = null;
    	$codes = self::codes();
    	if (!isset($codes[$code])) {
    		$code = 100;
        	$head = 400;
    	}
    	if ($message === '') {
    		$message = $codes[$code] ?? '';
        	$head   = 200;
    	}

        Http::head($head ?? $code);
        ApiServer::sendHeaders();
        echo (new ApiServerResponse(code: $code, message: $message))->getContent();
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
}
