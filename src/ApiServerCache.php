<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Network\Http;

/**
 * @brief       ApiServer core cache.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class ApiServerCache
{
    /**
     * Cache sub folder.
     *
     * @var     string  FODLER
     */
    public const FOLDER = 'api';

    /**
     * Create cache handler instance.
     *
     * @param   ApiServer   $api                The API server instance
     * @param   bool        $use_cache          Does Api use cache system
     */
    public function __construct(
        private readonly ApiServer $api,
        private bool $use_cache = true,
    ) {
        Http::$cache_max_age = static::getLifetime();
    }

    /**
     * Get / set cache usage.
     *
     * @param   null|bool   $enable     Set (bool) cache usage or read (null) it
     */
    public function useCache(?bool $enable = null): bool
    {
        if (is_bool($enable)) {
            $this->use_cache = $enable;
        }
        $path = static::getRoot();

        return $this->use_cache && static::getLifetime() > 0 && $path !== '' && is_dir($path) && is_writable($path);
    }

    /**
     * Get cache lifetime.
     *
     * Constant can be defined in Dotclear's config file.
     * Set constant to 0 to always disable API cache system.
     */
    public static function getLifetime(): int
    {
        return (int) (defined('API_SERVER_DEFAULT_CACHE_LIFETIME') ? API_SERVER_DEFAULT_CACHE_LIFETIME : 600);
    }

    /**
     * Get Dotclear cache root directory.
     */
    public static function getRoot(): string
    {
        return (string) Path::real(App::config()->cacheRoot());
    }

    /**
     * Get API cache root directory.
     */
    public static function getPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            self::getRoot(),
            My::id(),
            self::FOLDER,
        ]);
    }

    /**
     * Get request cache file path.
     */
    private function getFile(): string
    {
        $id = $this->getId();

        return implode(DIRECTORY_SEPARATOR, [
            static::getRoot(),
            My::id(),
            self::FOLDER,
            substr($id, 0, 2),
            substr($id, 2, 2),
            $id . '.json',
        ]);
    }

    /**
     * Get request cache uniq id.
     */
    private function getId(): string
    {
        return md5(implode('|', [
            App::blog()->id(),
            $this->api->getEndpoint(),
            serialize($this->api->getParams()),
        ]));
    }

    /**
     * Check cache expiry.
     */
    public function expiredCache(): bool
    {
        $file = $this->getFile();
        clearstatcache();

        return !$this->useCache() || !file_exists($file) || ((int) filemtime($file) + static::getLifetime()) < time();
    }

    /**
     * Get API response from cache.
     */
    public function readCache(): ApiServerResponse
    {
        $rsp = $this->expiredCache() ? new ApiServerResponse(code: 110) : ApiServerResponse::decode((string) file_get_contents($this->getFile()));

        return new ApiServerResponse(
            code:    $rsp->code,
            message: $rsp->message,
            content: $rsp->content,
            cache:   true,
        );
    }

    /**
     * Set API response to cache.
     *
     * @param   ApiServerResponse   $content    The reponse content instance
     */
    public function writeCache(ApiServerResponse $content): void
    {
        if (!$content->cache && $this->expiredCache()) {
            $file = $this->getFile();
            Files::makeDir(dirname($file), true);
            Files::putContent($file, $content->encode());
            Files::inheritChmod($file);
        }
    }

    /**
     * Clear API cache path.
     */
    public static function clearCache(): void
    {
        if (is_dir(self::getPath())) {
            Files::deltree(self::getPath());
        }
    }

    /**
     * Send cache related HTTP headers.
     *
     * If not modified, script stop here.
     */
    public function sendHeaders(): void
    {
        if (!$this->expiredCache()) {
            Http::cache([$this->getFile()]);
        }
    }
}
