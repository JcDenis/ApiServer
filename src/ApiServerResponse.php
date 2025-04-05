<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

/**
 * @brief       ApiServer core reponse.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerResponse
{
    /**
     * Create response content instance.
     *
     * @param   array<int|string, mixed>    $content 	The response content
     * @param 	int 						$code 		The response status code
     * @param 	string 						$message 	The repsonse status custom message
     * @param 	bool						$cache		Reponse comes form cache
     */
    public function __construct(
        public readonly array $content = [],
        public readonly int $code = 200,
        public readonly string $message = '',
        public readonly bool $cache = false,
    ) {
    }

    /**
     * Get json encoded response content.
     */
    public function getContent(): string
    {
        return (string) json_encode([
            'code'    => $this->code,
            'message' => $this->message ?: (ApiServerException::codes()[$this->code] ?? ''),
            'content' => $this->content,
            'cache'   => $this->cache ? '1' : '0',
        ]);
    }

    /**
     * Decode a json encoded content.
     *
     * @param 	string 	$content 	The content to decode.
     *
     * @return  array<int|string, mixed>
     */
    public static function decode(string $content): array
    {
        return json_decode((string) ($content ?: json_encode([])), true);
    }
}
