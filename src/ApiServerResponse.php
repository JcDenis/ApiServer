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
     * @param   array<int|string, mixed>    $content    The response content
     * @param   int                         $code       The response status code
     * @param   string                      $message    The repsonse status custom message
     * @param   bool                        $cache      Reponse comes form cache
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
    public function encode(): string
    {
        $message = $this->message;
        if ($message === '') {
            $codes   = ApiServerException::codes();
            $message = isset($codes[$this->code]) ? $message : '';
        }

        return (string) json_encode([
            'code'    => $this->code,
            'message' => $message,
            'content' => $this->content,
            'cache'   => $this->cache ? '1' : '0',
        ]);
    }

    /**
     * Decode a json encoded content.
     *
     * @param   string  $content    The json encoded content.
     */
    public static function decode(string $content): ApiServerResponse
    {
        $res = [
            'content' => [],
            'code'    => 110,
            'message' => '',
            'cache'   => true,
        ];

        $payload = $content !== '' ? json_decode($content, true) : [];
        if (is_array($payload)) {
            if (isset($payload['content']) && is_array($payload['content'])) {
                $res['content'] = $payload['content'];
            }
            if (isset($payload['code']) && is_numeric($payload['code'])) {
                $res['code'] = (int) $payload['code'];
            }
            if (isset($payload['message']) && is_string($payload['message'])) {
                $res['message'] = $payload['message'];
            }
        }

        return new self(
            content: $res['content'],
            code:    $res['code'],
            message: $res['message'],
            cache:   $res['cache'],
        );
    }
}
