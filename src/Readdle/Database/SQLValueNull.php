<?php declare(strict_types=1);

namespace Readdle\Database;

class SQLValueNull extends BaseSQLValue
{
    public function bind(\PDOStatement $statement, string $placeholder): void
    {
        $statement->bindValue($placeholder, null, \PDO::PARAM_NULL);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return null;
    }
}
