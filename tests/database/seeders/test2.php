<?php

declare(strict_types=1);

use WTFramework\DBAL\DB;
use WTFramework\Migration\Seeder;

return new class extends Seeder
{

  public function run(): void
  {

    DB::insert('test2')
    ->column([
      'id',
    ])
    ->values([
      [1],
      [2],
    ])
    ->execute();

  }

};