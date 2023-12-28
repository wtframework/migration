<?php

declare(strict_types=1);

namespace WTFramework\Migration;

abstract class Seeder
{
  abstract public function run(): void;
}