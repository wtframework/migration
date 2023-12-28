<?php

declare(strict_types=1);

use WTFramework\Config\Config;

Config::set([
  'database' => [
    'default' => 'sqlite',
    'connections' => [
      'sqlite' => []
    ]
  ],
  'migration' => [
    'dir' => __DIR__ . '/database',
    'table' => 'migrations',
  ]
]);