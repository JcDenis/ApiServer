<?php

/**
 * @file
 * @brief       The plugin ApiServer definition
 * @ingroup     ApiServer
 *
 * @defgroup    ApiServer Plugin daRepo.
 *
 * Serv your blog through API.
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

$this->registerModule(
    'Dotclear public API',
    'Serv your blog through API',
    'Jean-Chirstian Paul Denis',
    '0.8.1',
    [
        'requires' => [
            ['php', '8.3'],
            ['core', '2.36'],
        ],
        'settings' => [
            // @phpstan-ignore binaryOp.invalid
            'blog' => '#params.' . $this->id . '_params',
            // @phpstan-ignore binaryOp.invalid
            'pref' => '#user-favorites.' . $this->id . '_prefs',
        ],
        'permissions' => 'My',
        'type'        => 'plugin',
        // @phpstan-ignore binaryOp.invalid
        'support' => 'https://github.com/JcDenis/' . $this->id . '/issues',
        // @phpstan-ignore binaryOp.invalid
        'details' => 'https://github.com/JcDenis/' . $this->id . '/src/branch/master/README.md',
        // @phpstan-ignore binaryOp.invalid
        'repository' => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'       => '2025-09-13T10:13:44+00:00',
    ]
);
