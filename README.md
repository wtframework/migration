# What the Framework?! migration

This library extends the [DBAL](https://github.com/wtframework/dbal) library with a migration and seeding tool.

## Installation
```bash
composer require wtframework/migration
vendor/bin/migration init
```

## Documentation

### Configuration

Use the [Config](https://github.com/wtframework/config) library to set the database and migration configuration settings. See the [DBAL](https://github.com/wtframework/dbal) library for documentation on the database configuration settings.

```php
use WTFramework\Config\Config;

Config::set([
  'migration' => [
    'dir' => __DIR__ . '/database',
    'table' => 'migrations',
  ],
  'database' => [
    'default' => 'sqlite',
    'connections' => [
      'sqlite' => []
    ]
  ]
]);
```

### Settings

`migration`\
The route migration setting.

`migration.dir`\
The root directory for migrations and seeders. If not set then this will default to `__DIR__ . '/database'`. Within this directory will be a `/migrations` and a `/seeders` subdirectory.

`migration.table`\
The table to record all migrations. If not set then this will default to `migrations`.

### Create a migration

```bash
vendor/bin/migration create create_users_table
```

This will create a timestamped migration file in the `/migrations` subdirectory.

```php
use WTFramework\DBAL\DB;
use WTFramework\Migration\Migration;

return new class extends Migration
{

  public function up(): void
  {
    DB::create('example')->column(DB::column('id')->int())->execute();
  }

  public function down(): void
  {
    DB::drop('example')->execute();
  }

};
```

The `up` method is called when running the `migrate` command and the `down` method is called when running the `rollback` command.

### Run migrations

```bash
vendor/bin/migration migrate
```

This will run all pending migrations in the `/migrations` subdirectory in alphabetical (timestamped) order.

### Rollback migrations

```bash
vendor/bin/migration rollback
```

This will rollback the most recent migration.

```bash
vendor/bin/migration rollback 2
```

This will rollback the two most recent migrations.

```bash
vendor/bin/migration reset
```

This will rollback all migrations.

```bash
vendor/bin/migration refresh
```

This will rollback and rerun all migrations.

### Create a seeder

```bash
vendor/bin/migration seeder Users
```

This will create a seeder file in the `/seeders` subdirectory with a single `run` method.

```php

use WTFramework\DBAL\DB;
use WTFramework\Migration\Seeder;

return new class extends Seeder
{

  public function run(): void
  {
    DB::insert('example')->values([1])->execute();
  }

};
```

### Run seeders

```bash
vendor/bin/migration seed Users
```

This will run the `Users` seeder.

```bash
vendor/bin/migration seed
```

This will run all seeders.