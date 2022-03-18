<?php declare(strict_types=1);

namespace Readdle\Database;

use Readdle\Database\Connector\ConnectorInterface;
use Readdle\Database\Connector\Resolver;
use Readdle\Database\Event\TransactionStarted;
use Readdle\Database\Event\TransactionCommitted;
use Readdle\Database\Event\TransactionRolledBack;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class FQDBExecutor implements FQDBInterface
{
    public const QUOTE_DEFAULT    = 1;
    public const QUOTE_IDENTIFIER = 2;

    private const DB_DEFAULT = 'ansi';
    private const DB_MYSQL   = 'mysql';
    private const DB_SQLITE  = 'sqlite';
    
    private const MYSQL_CONNECTION_TIMEOUT = 28790;
    
    /** @var Resolver */
    private static $connectionResolver;
    private ?EventDispatcherInterface $dispatcher = null;
    private bool $warningReporting                = false;
    private string $databaseServer                = self::DB_DEFAULT; // for SQL specific stuff
    private int $lastCheckTime;
    private array $connectData;
    /** @var callable|null */
    private $warningHandler;
    /** @var callable|null */
    private $errorHandler;
    private \PDO $pdo;

    public function __construct(string $dsn, string $username = '', string $password = '', array $driver_options = [])
    {
        $this->connectData = [
            "dsn"            => $dsn,
            "username"       => $username,
            "password"       => $password,
            "driver_options" => $driver_options,
        ];
        $this->connect();
    }
    
    public static function registerConnector(ConnectorInterface $connector): void
    {
        self::connectorResolver()->registerConnector($connector);
    }
    
    public function setEventDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }
    
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Execute given SQL query. Please DON'T use instead of other functions
     *
     * example - use this if you need something like "TRUNCATE TABLE `users`"
     * use it VERY CAREFULLY!
     *
     * @return int affected rows count
     */
    public function execute(string $query, array $params = [], string $prefix = ""): int
    {
        if ('' !== $prefix) {
            $this->assertQueryStarts($query, $prefix);
        }
    
        $statement = $this->executeQuery($query, $params);
        return $statement->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->checkConnection();

        try {
            $this->pdo->beginTransaction();
            $this->dispatch(new TransactionStarted());
            $this->lastCheckTime = \time();
        } catch (\PDOException $e) {
            $this->error(FQDBException::pdo($e));
        }
    }

    public function commitTransaction(): void
    {
        try {
            $this->pdo->commit();
            $this->dispatch(new TransactionCommitted());
            $this->lastCheckTime = \time();
        } catch (\PDOException $e) {
            $this->rollbackTransaction();
            $this->error(FQDBException::pdo($e));
        }
    }

    public function rollbackTransaction(): void
    {
        try {
            $this->pdo->rollBack();
            $this->dispatch(new TransactionRolledBack());
            $this->lastCheckTime = \time();
        } catch (\PDOException $e) {
            $this->error(FQDBException::pdo($e));
        }
    }

    /**
     * @param int $mode -- FQDB::QUOTE_DEFAULT (for regular data) or FQDB::QUOTE_IDENTIFIER for table and field names
     */
    public function quote(string $string, int $mode = self::QUOTE_DEFAULT): string
    {
        if (self::QUOTE_IDENTIFIER == $mode) {
            // SQL ANSI default
            $quoteSymbol = '"';

            // MySQL and SQLite specific
            if (self::DB_MYSQL == $this->databaseServer || self::DB_SQLITE == $this->databaseServer) {
                $quoteSymbol = '`';
            }

            // quotes inside mysql fieQld are so rare, that we'd rather ban them
            if (false !== \strpos($string, $quoteSymbol)) {
                $this->error(FQDBException::unableToQuote($string));
            }

            return $quoteSymbol . $string . $quoteSymbol;
        } else {
            return $this->pdo->quote($string);
        }
    }
    
    public function setWarningHandler(?callable $func = null): void
    {
        $this->warningHandler = $func;
    }
    
    public function getWarningHandler(): ?callable
    {
        return $this->warningHandler;
    }
    
    public function setWarningReporting(bool $bool = true): void
    {
        $this->warningReporting = $bool;
    }
    
    public function getWarningReporting(): bool
    {
        return $this->warningReporting;
    }
    
    public function setErrorHandler(?callable $func = null): void
    {
        $this->errorHandler = $func;
    }
    
    public function getErrorHandler(): ?callable
    {
        return $this->errorHandler;
    }
    
    public function connect(): void
    {
        try {
            $this->pdo = self::connectorResolver()
                ->resolve($this->connectData)
                ->connect($this->connectData);
            
            $driverName = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            
            if (false !== \strpos($driverName, 'mysql')) {
                $this->databaseServer = self::DB_MYSQL;
            } elseif (false !== \strpos($driverName, 'sqlite')) {
                $this->databaseServer = self::DB_SQLITE;
            }

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->lastCheckTime = \time();
        } catch (\PDOException $e) {
            $this->error(FQDBException::pdo($e));
        }
    }
    
    /**
     * @throws \Readdle\Database\FQDBException if its not
     */
    protected function assertQueryStarts(string $query, string $needle): void
    {
        $query = \trim($query, " \t\n\r");
        if (!\preg_match("/^{$needle}.*/i", $query)) {
            $this->error(FQDBException::queryDontStart($query, $needle));
        }
    }
    
    /** @throws \Readdle\Database\FQDBException */
    protected function error(FQDBException $error): void
    {
        if (isset($this->errorHandler)) {
            \call_user_func($this->errorHandler, $error);
        }
        throw $error;
    }
    
    protected function dispatch(Event $event): void
    {
        if (null === $this->dispatcher) {
            return;
        }
        
        $this->dispatcher->dispatch($event);
    }
    

    /** @return \PDOStatement|string */
    protected function executeQuery(string $query, array $params, bool $needsLastInsertId = false)
    {
        $this->checkConnection();
        
        try {
            [$query, $params] = $this->prepareStatement($query, $params);
            
            $statement = $this->pdo->prepare($query);
            
            $this->_preExecuteOptionsCheck($query, $params);
            
            $this->bindOptionsToStatement($params, $statement);
            
            $statement->execute(); //options are already bound to query
            
            $this->lastCheckTime = \time();
            
            if ($needsLastInsertId) {
                $lastInsertId = $this->pdo->lastInsertId(); // if table has no PRI KEY, there will be 0
            }
        } catch (\PDOException $e) {
            $this->error(FQDBException::pdo($e, ["query" => $query, "params" => $params]));
        }
        
        $this->reportWarnings($query, $params);
        
        return $lastInsertId ?? $statement;
    }
    
    private static function connectorResolver(): Resolver
    {
        if (null === self::$connectionResolver) {
            self::$connectionResolver = new Resolver();
        }
        return self::$connectionResolver;
    }
    
    /**
     * if last query was too long time ago - reconnect
     */
    private function checkConnection(): void
    {
        if (self::DB_MYSQL !== $this->databaseServer) {
            return;
        }

        $interval = (\time() - (int)$this->lastCheckTime);

        if ($interval >= self::MYSQL_CONNECTION_TIMEOUT) {
            $this->connect();
        }
    }

    /**
     * gathers Warning info from \PDO
     */
    private function getWarnings(string $query, array $params = []): string
    {
        if (self::DB_MYSQL === $this->databaseServer) {
            $stm           = $this->pdo->query('SHOW WARNINGS');
            $queryWarnings = $stm->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $queryWarnings = [['Message' => 'WarningReporting not impl. for ' . $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME)]];
        }


        if ((false !== $queryWarnings) && \count($queryWarnings) > 0) {
            $warnings = "Query:\n{$query}\n";

            if (!empty($params)) {
                $warnings .= "Params: (";

                foreach ($params as $key => $value) {
                    $warnings .= $key . '=' . \json_encode($value) . ', ';
                }

                $warnings = \substr($warnings, 0, -2) . ")\n";
            }


            $warnings .= "Produced Warnings:";

            foreach ($queryWarnings as $warn) {
                $warnings .= "\n* " . $warn['Message'];
            }

            return $warnings;
        }

        return '';
    }

    /**
     * Find WHERE IN statements and converts sqlQueryString and $params
     * to format needed for WHERE IN statement run
     */
    private function prepareStatement(string $query, array $params): array
    {
        $statementNum = 0;
        foreach ($params as $placeholder => $value) {
            if (($value instanceof SQLArgs || $value instanceof SQLArgsArray)) {
                $args = $value->toArray();

                $statementNum++;
                $valueInStatementNum = 0;
                $whereInStatement    = [];
                foreach ($args as $inStatementValue) {
                    $valueInStatementNum++;
                    $whereInStatement[":where_in_statement_{$statementNum}_{$valueInStatementNum}"] = $inStatementValue;
                }

                $query  = \str_replace($placeholder, \implode(', ', \array_keys($whereInStatement)), $query);
                $params = \array_merge($params, $whereInStatement);

                unset($params[$placeholder]);
            }
        }

        return [$query, $params];
    }

    private function reportWarnings(string $queryQueryString, array $params): void
    {
        if ($this->warningReporting) {
            $warningMessage = $this->getWarnings($queryQueryString, $params);

            if (!empty($warningMessage)) {
                if (isset($this->warningHandler)) {
                    \call_user_func($this->warningHandler, $warningMessage);
                } else {
                    \trigger_error($warningMessage, E_USER_WARNING); // default warning handler
                }
            }
        }
    }

    private function bindOptionsToStatement(array &$params, \PDOStatement $statement): void
    {
        // warning! it is important to pass $value by reference here, since
        // bindParam also binds parameter by reference (and the value itself is changing)
        foreach ($params as $placeholder => &$value) {
            if (\is_array($value)) {
                $this->error(FQDBException::deprecatedApi());
            } else if ($value instanceof BaseSQLValue) {
                $value->bind($statement, $placeholder);
            } else {
                $statement->bindParam($placeholder, $value);
            }
        }
    }

    /**
     * @throws FQDBException - when placeholders are not set properly
     */
    private function _preExecuteOptionsCheck(string $query, array $params): void
    {
        \preg_match_all('/:[a-z]\w*/u', $query, $placeholders);
        // !!!!WARNING!!!! placeholders SHOULD start form lowercase letter!

        if (empty($placeholders) || empty($placeholders[0])) {
            return; //no placeholders found
        }

        foreach ($placeholders[0] as $placeholder) {
            if (!\array_key_exists($placeholder, $params)) {
                $this->error(FQDBException::badPlaceholders($query, $params, $placeholders));
            }
        }
    }
}
