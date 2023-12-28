<?php

declare(strict_types=1);

namespace WTFramework\Migration;

use WTFramework\Config\Config;
use WTFramework\DBAL\DB;
use WTFramework\SQL\Statements\Select;

abstract class Service
{

  public static bool $output_success = true;

  public static function cli(array $args): void
  {

    match ($command = $args[1] ?? null)
    {
      'init' => static::init(),
      'create' => static::create($args[2] ?? null),
      'migrate' => static::migrate(),
      'rollback' => static::rollback((int) ($args[2] ?? 1)),
      'reset' => static::reset(),
      'refresh' => static::refresh(),
      'seeder' => static::seeder($args[2] ?? null),
      'seed' => static::seed($args[2] ?? null),
      default => throw new MigrationException("Unknown command '$command'.")
    };

  }

  public static function init(): void
  {
    static::createConfig();
  }

  public static function config(): string
  {
    return getcwd() . '/migration.php';
  }

  public static function createConfig(): void
  {

    if (
      !is_file($config = static::config())
      &&
      !copy(__DIR__ . '/templates/config.php', $config)
    )
    {
      throw new MigrationException("Could not create $config.");
    }

    static::success("Config file created.");

  }

  public static function dir(): string
  {
    return Config::get('migration.dir', getcwd() . '/database');
  }

  public static function createDir(): void
  {

    $dir = static::dir();

    foreach (['', '/migrations', '/seeders'] as $subdir)
    {

      $path = $dir . $subdir;

      if (!is_dir($path))
      {

        if (!mkdir($path, 0775))
        {
          throw new MigrationException("Could not create $path.");
        }

      }

      if (!is_writable($path))
      {
        throw new MigrationException("$path is not writable.");
      }

    }

  }

  public static function table(): string
  {
    return Config::get('migration.table', 'migrations');
  }

  public static function createTable(): void
  {

    DB::create($table = static::table())
    ->ifNotExists()
    ->column(
      DB::column('migration')
      ->varchar(255)
      ->primaryKey()
    )
    ->column(
      DB::column('date_time')
      ->datetime()
      ->notNull()
    )
    ->if("OBJECT_ID('$table', 'U')", null)
    ->execute();

  }

  public static function create(string $name = null): string
  {

    require_once static::config();

    static::createDir();

    if (!$name)
    {
      throw new MigrationException("No name provided.");
    }

    $migration = static::dir() . '/migrations/' . date('YmdHis') . "_$name.php";

    if (!copy(__DIR__ . '/templates/migration.php', $migration))
    {
      throw new MigrationException("Unable to create migration file.");
    }

    static::success("$name: migration created.");

    return $migration;

  }

  public static function migrate(): void
  {

    require_once static::config();

    static::createTable();

    $dir = static::dir();

    foreach (glob("$dir/migrations/*") as $path)
    {

      $file = pathinfo($path, PATHINFO_FILENAME);

      if (!DB::select(static::table())
      ->where('migration', $file)
      ->get())
      {

        static::up(
          migration: require $path,
          file: $file
        );

      }

    }

  }

  public static function up(
    Migration $migration,
    string $file
  ): void
  {

    static::success("$file: migrating...");

    $migration->up();

    DB::insert(static::table())
    ->values([$file, date('Y-m-d H:i:s')])
    ->execute();

  }

  public static function rollback(?int $count = 1): void
  {

    require_once static::config();

    static::createTable();

    $dir = static::dir();

    foreach (DB::select(static::table())
    ->orderByDesc(['date_time', 'migration'])
    ->when(null !== $count, function (Select $stmt) use ($count)
    {
      $stmt->limit($count)->top($count);
    })
    ->all() as $migration)
    {

      static::down(
        migration: require "$dir/migrations/$migration->migration.php",
        file: $migration->migration
      );

    }

  }

  public static function reset(): void
  {
    static::rollback(count: null);
  }

  public static function refresh(): void
  {
    static::reset();
    static::migrate();
  }

  public static function down(
    Migration $migration,
    string $file
  ): void
  {

    static::success("$file: rolling back...");

    $migration->down();

    DB::delete(static::table())
    ->where('migration', $file)
    ->execute();

  }

  public static function seeder(string $name = null): string
  {

    require_once static::config();

    static::createDir();

    if (!$name)
    {
      throw new MigrationException("No name provided.");
    }

    $seeder = static::dir() . "/seeders/$name.php";

    if (!copy(__DIR__ . '/templates/seeder.php', $seeder))
    {
      throw new MigrationException("Unable to create seeder file.");
    }

    static::success("$name: seeder created.");

    return $seeder;

  }

  public static function seed(string $seeder = null): void
  {

    require_once static::config();

    $dir = static::dir() . '/seeders';

    if ($seeder)
    {

      if (!is_file($path = "$dir/$seeder.php"))
      {
        throw new MigrationException("Unknown seeder '$seeder'.");
      }

      static::run(
        seeder: require $path,
        name: $seeder
      );

    }

    else
    {

      foreach (glob("$dir/*") as $path)
      {

        static::run(
          seeder: require $path,
          name: pathinfo($path, PATHINFO_FILENAME)
        );

      }

    }

  }

  public static function run(
    Seeder $seeder,
    string $name
  ): void
  {

    static::success("$name: seeding...");

    $seeder->run();

  }

  public static function success(string $message): void
  {

    if (static::$output_success)
    {
      echo "\e[32m$message\e[0m" . PHP_EOL;
    }

  }

}