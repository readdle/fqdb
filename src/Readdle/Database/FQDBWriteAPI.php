<?php declare(strict_types=1);

namespace Readdle\Database;

use Readdle\Database\Event\DeleteQueryStarted;
use Readdle\Database\Event\UpdateQueryStarted;

class FQDBWriteAPI extends FQDBQueryAPI
{
    public function delete(string $query, array $params = []): int
    {
        $this->assertQueryStarts($query, 'delete');
        $this->dispatch(new DeleteQueryStarted($query, $params));
        
        $statement = $this->executeQuery($query, $params);
        return $statement->rowCount();
    }
    
    public function update(string $query, array $params = []): int
    {
        $this->assertQueryStarts($query, 'update');
        $this->dispatch(new UpdateQueryStarted($query, $params));
        
        $statement = $this->executeQuery($query, $params);
        return $statement->rowCount();
    }
    
    public function insert(string $query, array $params = []): string
    {
        $this->assertQueryStarts($query, 'insert');
        return $this->executeQuery($query, $params, true);
    }
    
    public function set(string $query, array $params = []): int
    {
        return $this->execute($query, $params, 'set');
    }
    
    public function insertIgnore(string $query, array $params = []): int
    {
        return $this->execute($query, $params, 'insert ignore');
    }
    
    public function replace(string $query, array $params = []): int
    {
        return $this->execute($query, $params, 'replace');
    }
}
