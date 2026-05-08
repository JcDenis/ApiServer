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
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

$this->registerModule(
    'Dotclear public API',
    'Serv your blog through API',
    'Jean-Christian Paul Denis',
    '0.10',
    [
        'requires' => [
            ['php', '8.3'],
            ['core', '2.36'],
        ],
        'settings' => [
            'blog' => '#params.' . $this->id . '_params',
            'pref' => '#user-favorites.' . $this->id . '_prefs',
        ],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/src/branch/master/README.md',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2026-04-24T18:52:00+00:00',
    ]
);
