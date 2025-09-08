<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer;

use Autoloader;
use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       ApiServer module prepend.
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class Prepend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Json Web Token library v6.11.0, see https://github.com/firebase/php-jwt
        Autoloader::me()->addNamespace('Firebase\JWT', implode(DIRECTORY_SEPARATOR, [
            My::path(), 'lib', 'firebase', 'php-jwt', 'src',
        ]));

        // API user permission
        App::auth()->setPermissionType(
            My::id(),
            __('API client')
        );

        // Register public URLs
        App::url()->register(
            My::id(),
            'api',
            '^api/((\w+)(/.+)?(/)?)$',
            function (?string $args): void { new ApiServer((string) $args); }
        );

        return true;
    }
}
