<?php declare(strict_types=1);

namespace Readdle\Database;

class FQDBQueryAPI extends FQDBExecutor
{
    /** @return string|false */
    public function queryValue(string $query, array $params = [])
    {
        $statement = $this->runQuery($query, $params);
        return $statement->fetch(\PDO::FETCH_COLUMN);
    }
    
    /** @return array|false */
    public function queryAssoc(string $query, array $params = [])
    {
        $statement = $this->runQuery($query, $params);
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }
    
    /** @return array|false */
    public function queryList(string $query, array $params = [])
    {
        $statement = $this->runQuery($query, $params);
        return $statement->fetch(\PDO::FETCH_NUM);
    }
    
    public function queryVector(string $query, array $params = []): array
    {
        return $this->queryArray(
            $query,
            $params,
            function (\PDOStatement $statement) {
                return $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
            }
        );
    }
    
    public function queryTable(string $query, array $params = []): array
    {
        return $this->queryArray(
            $query,
            $params,
            function (\PDOStatement $statement) {
                return $statement->fetchAll(\PDO::FETCH_ASSOC);
            }
        );
    }
    
    /** @return array */
    public function queryObjArray(string $query, string $className, array $params = [], ?array $classConstructorArguments = null): array
    {
        if (!\class_exists($className)) {
            $this->error(FQDBException::assertion("Class '{$className}' doesn't exist"));
        }
        
        return $this->queryArray(
            $query,
            $params,
            function (\PDOStatement $statement) use ($className, $classConstructorArguments) {
                return $statement->fetchAll(
                    \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE,
                    $className,
                    $classConstructorArguments
                );
            }
        );
    }
    
    /**
     * executes SELECT or SHOW query and returns object of given class
     * @return object|false
     */
    public function queryObj(string $query, string $className, array $params = [], ?array $classConstructorArguments = null)
    {
        if (!\class_exists($className)) {
            $this->error(FQDBException::assertion("Class '{$className}' doesn't exist"));
        }
        
        $statement = $this->runQuery($query, $params);
        $statement->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $className, $classConstructorArguments);
        
        return $statement->fetch();
    }
    
    /**
     * Execute query and apply a callback function to each row
     */
    public function queryTableCallback(string $query, array $params, callable $callback): void
    {
        $statement = $this->runQuery($query, $params);
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            \call_user_func($callback, $row);
        }
    }
    
    /**
     * Execute a query and makes generator from the result
     */
    public function queryTableGenerator(string $query, array $params = []): \Generator
    {
        $statement = $this->runQuery($query, $params);
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }
    
    /**
     * Executes SELECT or SHOW query and returns an assoc array made of two-columns
     * where the first column is a key and the second column is the value
     */
    public function queryHash(string $query, array $params = []): array
    {
        return $this->queryArray(
            $query,
            $params,
            function (\PDOStatement $statement) {
                return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
            }
        );
    }
    
    private function runQuery(string $query, array $params): \PDOStatement
    {
        $this->assertQueryStarts($query, '[select|show]');
        $statement = $this->executeQuery($query, $params);
        if (!$statement instanceof \PDOStatement) {
            $this->error(FQDBException::assertion("Expect select/show query execution returns PODStatement"));
        }
        return $statement;
    }
    
    private function queryArray(string $query, array $params, callable $fetcher): array
    {
        $statement = $this->runQuery($query, $params);
        $result    = \call_user_func($fetcher, $statement);
        return \is_array($result) ? $result : [];
    }
}
