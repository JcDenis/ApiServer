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
    '0.1',
    [
        'requires' => [
            ['php', '8.3'],
            ['core', '2.34'],
            ['FrontendSession', '0.12'], // required for public session
        ],
        'settings' => [
            'blog' => '#params.ApiServer_params',
        ],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/ApiServer/issues',
        'details'     => 'https://github.com/JcDenis/ApiServer',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/ApiServer/master/dcstore.xml',
        'date'        => '2025-04-01T16:56:59+00:00',
    ]
);
