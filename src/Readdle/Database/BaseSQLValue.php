<?php declare(strict_types=1);

namespace Readdle\Database;

abstract class BaseSQLValue implements \JsonSerializable
{
    abstract public function bind(\PDOStatement $statement, string $placeholder): void;
}
