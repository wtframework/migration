<?php

declare(strict_types=1);

use WTFramework\Config\Config;
use WTFramework\DBAL\DB;
use WTFramework\Migration\Migration;
use WTFramework\Migration\MigrationException;
use WTFramework\Migration\Seeder;
use WTFramework\Migration\Service;

beforeEach(fn () => resetConfig());

it('can create config', function ()
{

  deleteConfig();

  Service::createConfig();

  expect(file_exists(Service::config()))
  ->toBeTrue();

});

it('can get dir', function ()
{

  expect(Service::dir())
  ->toBe(getcwd() . '/database');

  Config::set('migration', ['dir' => 'test']);

  expect(Service::dir())
  ->toBe('test');

});

it('can create dir', function ()
{

  deleteDir();

  Service::createDir();

  $dir = Service::dir();

  foreach (['', '/migrations', '/seeders'] as $subdir)
  {

    expect(is_dir($dir . $subdir))
    ->toBeTrue();

  }

});

it('can get table', function ()
{

  expect(Service::table())
  ->toBe('migrations');

  Config::set('migration', ['table' => 'test']);

  expect(Service::table())
  ->toBe('test');

});

it('can create table', function ()
{

  deleteTable();

  Service::createTable();

  expect(DB::select('migrations')->all())
  ->toBe([]);

});

it('can init', function ()
{

  deleteConfig();

  Service::init();

  expect(file_exists(Service::config()))
  ->toBeTrue();

});

it('can\'t create migration if no name', function ()
{
  Service::create();
})
->throws(MigrationException::class, 'No name provided.');

it('can create migration', function ()
{

  $migration = require $path = Service::create('test');

  expect($migration)
  ->toBeInstanceOf(Migration::class);

  expect($path)
  ->toMatch('/\/\d{14}_test.php$/');

});

it('can migrate up', function ()
{

  resetTables();

  $migration = require $path = Service::create('test');

  Service::up(
    migration: $migration,
    file: pathinfo($path, PATHINFO_FILENAME)
  );

  expect(DB::select('example')->all())
  ->toBe([]);

  $migrations = DB::select('migrations')->all();

  expect(count($migrations))
  ->toBe(1);

  expect($migrations[0]?->migration)
  ->toMatch('/^\d{14}_test$/');

  expect($migrations[0]?->date_time)
  ->toMatch('/^\d{4}(-\d{2}){2} \d{2}(:\d{2}){2}$/');

});

it('can migrate down', function ()
{

  resetTables();

  $migration = require $path = Service::create('test');

  Service::up(
    migration: $migration,
    file: $file = pathinfo($path, PATHINFO_FILENAME)
  );

  Service::down(
    migration: $migration,
    file: $file
  );

  expect(DB::select('migrations')->all())
  ->toBe([]);

  DB::select('example')->all();

})
->throws(PDOException::class, 'SQLSTATE[HY000]: General error: 1 no such table: example');

it('can migrate all', function ()
{

  resetTables();

  Config::set('migration', ['dir' => __DIR__ . '/database']);

  Service::migrate();

  foreach ([1, 2, 3] as $num)
  {

    expect(DB::select("test$num")->all())
    ->toBe([]);

  }

  expect(count(DB::select('migrations')->all()))
  ->toBe(3);

});

it('can rollback', function ()
{

  resetTables();

  Config::set('migration', ['dir' => __DIR__ . '/database']);

  Service::migrate();
  Service::rollback();

  expect(count(DB::select('migrations')->all()))
  ->toBe(2);

  foreach ([1, 2, 3] as $num)
  {
    DB::select("test$num")->all();
  }

})
->throws(PDOException::class, 'SQLSTATE[HY000]: General error: 1 no such table: test3');

it('can rollback specified count', function ()
{

  resetTables();

  Config::set('migration', ['dir' => __DIR__ . '/database']);

  Service::migrate();
  Service::rollback(2);

  expect(count(DB::select('migrations')->all()))
  ->toBe(1);

  foreach ([1, 2, 3] as $num)
  {
    DB::select("test$num")->all();
  }

})
->throws(PDOException::class, 'SQLSTATE[HY000]: General error: 1 no such table: test2');

it('can reset', function ()
{

  resetTables();

  Config::set('migration', ['dir' => __DIR__ . '/database']);

  Service::migrate();
  Service::reset();

  expect(count(DB::select('migrations')->all()))
  ->toBe(0);

  DB::select('test1')->all();

})
->throws(PDOException::class, 'SQLSTATE[HY000]: General error: 1 no such table: test1');

it('can refresh', function ()
{

  resetTables();

  Config::set('migration', ['dir' => __DIR__ . '/database']);

  Service::migrate();
  Service::refresh();

  expect(count(DB::select('migrations')->all()))
  ->toBe(3);

});

it('can\'t create seeder if no name', function ()
{
  Service::seeder();
})
->throws(MigrationException::class, 'No name provided.');

it('can create seeder', function ()
{

  $seeder = require $path = Service::seeder('test');

  expect($seeder)
  ->toBeInstanceOf(Seeder::class);

  expect($path)
  ->toMatch('/test.php$/');

});

it('can run seeder', function ()
{

  resetTables();

  createTable('example');

  (require Service::seeder('test'))->run();

  expect(DB::select('example')->all())
  ->toEqual([(object) ['id' => 1]]);

});

it('can run named seeder', function ()
{

  resetTables();

  foreach ([1, 2, 3] as $num)
  {
    createTable("test$num");
  }

  Config::set('migration', ['dir' => __DIR__ . '/database']);

  Service::seed('test1');

  expect(DB::select('test1')->all())
  ->toEqual([(object) [
    'id' => 1
  ]]);

  expect(DB::select('test2')->all())
  ->toEqual([]);

  expect(DB::select('test3')->all())
  ->toEqual([]);

});

it('can\'t run unknown seeder', function ()
{
  Service::seed('unknown');
})
->throws(MigrationException::class, "Unknown seeder 'unknown'.");

it('can run all seeders', function ()
{

  resetTables();

  foreach ([1, 2, 3] as $num)
  {
    createTable("test$num");
  }

  Config::set('migration', ['dir' => __DIR__ . '/database']);

  Service::seed();

  expect(DB::select('test1')->all())
  ->toEqual([(object) [
    'id' => 1
  ]]);

  expect(DB::select('test2')->all())
  ->toEqual([(object) [
    'id' => 1
  ], (object) [
    'id' => 2
  ]]);

  expect(DB::select('test3')->all())
  ->toEqual([(object) [
    'id' => 1
  ], (object) [
    'id' => 2
  ], (object) [
    'id' => 3
  ]]);

});

$bin = __DIR__ . '/../bin/migration';

it('can init using cli', function () use ($bin)
{

  deleteConfig();

  exec("$bin init");

  expect(file_exists(Service::config()))
  ->toBeTrue();

});

it('can create using cli', function () use ($bin)
{

  deleteDir();

  exec("$bin create test");

  $migration = glob(Service::dir() . "/migrations/*")[0];

  expect($migration)
  ->toMatch('/\/\d{14}_test.php$/');

});

it('can migrate using cli', function () use ($bin)
{

  resetTables();

  Service::create('test');

  Service::cli(['', 'migrate']);

  expect(DB::select('example')->all())
  ->toBe([]);

});

it('can rollback using cli', function () use ($bin)
{

  resetTables();

  Service::create('test');

  Service::migrate();

  Service::cli(['', 'rollback']);

  DB::select('example')->all();

})
->throws(PDOException::class, "SQLSTATE[HY000]: General error: 1 no such table: example");

it('can reset using cli', function () use ($bin)
{

  resetTables();

  Service::create('test');

  Service::cli(['', 'reset', '0']);

  expect(DB::select('example')->all())
  ->toBe([]);

  Service::cli(['', 'reset']);

  DB::select('example')->all();

})
->throws(PDOException::class, "SQLSTATE[HY000]: General error: 1 no such table: example");

it('can refresh using cli', function () use ($bin)
{

  resetTables();

  Service::create('test');

  Service::cli(['', 'refresh']);

  expect(DB::select('example')->all())
  ->toBe([]);

});

it('can create seeder using cli', function () use ($bin)
{

  deleteDir();

  exec("$bin seeder Test");

  expect(file_exists(Service::dir() . '/seeders/Test.php'))
  ->toBeTrue();

});

it('can run named seeders using cli', function () use ($bin)
{

  resetTables();

  foreach ([1, 2, 3] as $num)
  {
    createTable("test$num");
  }

  Config::set('migration', ['dir' => __DIR__ . '/database']);

  Service::cli(['', 'seed', 'test1']);

  expect(DB::select('test1')->all())
  ->toEqual([(object) [
    'id' => 1
  ]]);

  expect(DB::select('test2')->all())
  ->toEqual([]);

  expect(DB::select('test3')->all())
  ->toEqual([]);

});

it('can run all seeders using cli', function () use ($bin)
{

  resetTables();

  foreach ([1, 2, 3] as $num)
  {
    createTable("test$num");
  }

  Config::set('migration', ['dir' => __DIR__ . '/database']);

  Service::cli(['', 'seed']);

  expect(DB::select('test1')->all())
  ->toEqual([(object) [
    'id' => 1
  ]]);

  expect(DB::select('test2')->all())
  ->toEqual([(object) [
    'id' => 1
  ], (object) [
    'id' => 2
  ]]);

  expect(DB::select('test3')->all())
  ->toEqual([(object) [
    'id' => 1
  ], (object) [
    'id' => 2
  ], (object) [
    'id' => 3
  ]]);

});

afterAll(function ()
{
  resetConfig();
  deleteConfig();
  deleteDir();
});