<?php

declare(strict_types=1);

use WTFramework\Config\Config;
use WTFramework\DBAL\DB;
use WTFramework\Migration\Service;

Config::set([
  'database' => [
    'default' => 'sqlite',
    'connections' => [
      'sqlite' => []
    ]
  ],
]);

Service::$output_success = false;

function resetConfig(): void
{
  Config::set('migration');
}

function deleteConfig(): void
{

  if (file_exists($config = Service::config()))
  {
    unlink($config);
  }

}

function deleteDir(): void
{

  $dir = Service::dir();

  foreach (['/seeders', '/migrations', ''] as $subdir)
  {

    $path = $dir . $subdir;

    if (is_dir($path))
    {

      foreach (glob("$path/*") as $file)
      {
        unlink($file);
      }

      rmdir($path);

    }

  }

}

function deleteTable(): void
{

  DB::drop(Service::table())
  ->ifExists()
  ->unprepared();

}

function createTable(string $name): void
{

  DB::create($name)
  ->column(
    DB::column('id')
    ->integer()
    ->unsigned()
    ->autoIncrement()
    ->primaryKey()
  )
  ->execute();

}

function resetTables(): void
{

  foreach (['test1', 'test2', 'test3', 'example'] as $table)
  {

    DB::drop($table)
    ->ifExists()
    ->unprepared();

  }

  DB::delete('migrations')
  ->unprepared();

}