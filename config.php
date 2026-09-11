<?php
/**
 * FarmFlow Central Configuration
 * Edit values here instead of inside each file.
 */

define('FF_ROOT', __DIR__);

return [
    'db' => [
        'host'    => '127.0.0.1',
        'ports'   => [3306, 3307],
        'names'   => ['farmflow_db', 'farmflow'],
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
        'timeout' => 2,
        'schema'  => FF_ROOT . '/farmflow_db.sql',
    ],
    'app' => [
        'dist_html'    => FF_ROOT . '/dist/index.html',
        'fallback_html' => FF_ROOT . '/index.html',
    ],
];
