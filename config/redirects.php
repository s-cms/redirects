<?php

// config for SmartCms/Redirects
return [
    'table_name' => 'redirects',

    'cache' => [
        'enabled' => true,
        'ttl' => 60 * 60 * 24, // 24 hours in seconds
        'key' => 'redirects_cache',
    ],
];
