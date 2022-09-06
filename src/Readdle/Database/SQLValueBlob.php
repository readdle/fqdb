<?php declare(strict_types=1);

namespace Readdle\Database;

class SQLValueBlob extends BaseSQLValue
{
    private string $blob;
    
    public function __construct(string $blob)
    {
        $this->blob = $blob;
    }
    
    public function getBlob(): string
    {
        return $this->blob;
    }
    
    public function bind(\PDOStatement $statement, string $placeholder): void
    {
        $statement->bindParam($placeholder, $this->blob, \PDO::PARAM_LOB);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return \base64_encode($this->blob);
    }
}
