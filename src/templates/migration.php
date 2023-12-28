<?php

declare(strict_types=1);

use WTFramework\DBAL\DB;
use WTFramework\Migration\Migration;

return new class extends Migration
{

  public const TABLE = 'example';

  public function up(): void
  {

    $this->drop();

    DB::create(self::TABLE)
    ->column(
      DB::column('id')
      ->integer()
      ->unsigned()
      ->autoIncrement()
      ->primaryKey()
    )
    ->execute();

  }

  public function down(): void
  {
    $this->drop();
  }

  private function drop(): void
  {
    DB::drop(self::TABLE)->ifExists()->execute();
  }

};