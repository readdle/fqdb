<?php
namespace Readdle\Database;

// TODO: unit test
// TODO: error and handling


final class FQDB implements \Serializable
{

    const QUOTE_DEFAULT = 1;
    const QUOTE_IDENTIFIER = 2;


    const DB_DEFAULT = 'ansi';
    const DB_MYSQL = 'mysql';
    const DB_SQLITE = 'sqlite';

    /**
     * @var \PDO $_pdo - PDO object
     */
    private $_pdo;


    private $_beforeUpdateHandler;
    private $_beforeDeleteHandler;
    private $_warningHandler;
    private $_warningReporting = false;
    private $_databaseServer = self::DB_DEFAULT; // for SQL specific stuff
    private $_errorHandler;


    /**
     * connects to DB, using params below
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $driver_options
     * @return \Readdle\Database\FQDB
     */
    function __construct($dsn, $username = '', $password = '', $driver_options = array())
    {
        try {
            $this->_pdo = new \PDO($dsn, $username, $password, $driver_options);
            if (strpos($dsn, 'mysql') !== false)
                $this->_databaseServer = self::DB_MYSQL;
            else if (strpos($dsn, 'sqlite') !== false)
                $this->_databaseServer = self::DB_SQLITE;

            $this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $this;
        } catch (\PDOException $e) {
            $this->_error($e->getMessage(), FQDBException::PDO_CODE, $e);
            trigger_error('FQDB Fatal', E_ERROR);
        }
    }

    // we could not serialize PDO object anyway
    public function serialize() {
        return null;
    }

    public function unserialize($string) {
        return null;
    }


    /**
     * Returns raw PDO object (for sessions?)
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->_pdo;
    }


    /**
     * executes DELETE query with placeholders in 2nd param
     * @param string $query
     * @param array $params
     * @return int affected rows count
     */
    public function delete($query, $params = array())
    {
        $this->_testQueryStarts($query, 'delete');

        if ($this->_beforeDeleteHandler !== null)
            call_user_func_array($this->_beforeDeleteHandler, [$query, $params]);

        $statement = $this->_executeQuery($query, $params);
        return $statement->rowCount();
    }

    /**
     * executes UPDATE query with placeholders in 2nd param
     * @param string $query
     * @param array $params PDO names params, starting with :
     * @return int affected rows count
     */
    public function update($query, $params = array())
    {
        $this->_testQueryStarts($query, 'update');

        if ($this->_beforeUpdateHandler !== null)
            call_user_func_array($this->_beforeUpdateHandler, [$query, $params]);

        $statement = $this->_executeQuery($query, $params);
        return $statement->rowCount();
    }

    /**
     * executes INSERT query with placeholders in 2nd param
     * @param string $query
     * @param array $params
     * @return int last inserted id
     */
    public function insert($query, $params = array())
    {
        $this->_testQueryStarts($query, 'insert');
        return $this->_executeQuery($query, $params, true);
    }


    /**
     * executes SET query with placeholders in 2nd param
     * @param string $query
     * @param array $params
     * @return int affected rows count
     */
    public function set($query, $params = array())
    {
        return $this->execute($query, $params, 'set');
    }


    /**
     * executes INSERT IGNORE query with placeholders in 2nd param
     * @param string $query
     * @param array $params
     * @return int rows affected
     */
    public function insertIgnore($query, $params = array())
    {
        return $this->execute($query, $params, 'insert ignore');
    }



    /**
     * executes REPLACE query with placeholders in 2nd param
     * @param string $query
     * @param array $params
     * @return int affected rows count
     */
    public function replace($query, $params = array())
    {
        return $this->execute($query, $params, 'replace');
    }

    /**
     * Execute given SQL query. Please DON'T use instead of other functions
     *
     * example - use this if you need something like "TRUNCATE TABLE `users`"
     * use it VERY CAREFULLY!
     *
     * @param string $sqlQuery
     * @param array $params
     * @param string $prefix prefix to check SQL query against
     * @return int affected rows count
     */
    public function execute($sqlQuery, $params = [], $prefix = '')
    {
        if ($prefix !== '')
            $this->_testQueryStarts($sqlQuery, $prefix);

        $statement = $this->_executeQuery($sqlQuery, $params);
        return $statement->rowCount();
    }

    /**
     * executes SELECT or SHOW query and returns 1st returned element
     * @param string $query
     * @param array $options
     * @return mixed
     */
    public function queryValue($query, $options = array())
    {
        $statement = $this->_runQuery($query, $options);
        $result = $statement->fetch(\PDO::FETCH_NUM);

        if (is_array($result))
            return $result[0];
        else
            return false;
    }

    /**
     * executes SELECT or SHOW query and returns 1st row as assoc array
     * @param string $query
     * @param array $options
     * @return array|false
     */
    public function queryAssoc($query, $options = array())
    {
        $statement = $this->_runQuery($query, $options);
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * executes SELECT or SHOW query and returns as array
     * @param string $query
     * @param array $options
     * @return array|false
     */
    public function queryList($query, $options = array())
    {
        $statement = $this->_runQuery($query, $options);
        return $statement->fetch(\PDO::FETCH_NUM);
    }

    /**
     * executes SELECT or SHOW query and returns result as array
     * @param string $query
     * @param array $options
     * @return array|false
     */
    public function queryVector($query, $options = array())
    {
        $statement = $this->_runQuery($query, $options);
        $result = $statement->fetchAll(\PDO::FETCH_COLUMN, 0);

        if (count($result) == 0)
            return false;
        else
            return $result;
    }

    /**
     * executes SELECT or SHOW query and returns result as assoc array
     * @param string $query
     * @param array $options
     * @return array|false
     */
    public function queryTable($query, $options = array())
    {
        $statement = $this->_runQuery($query, $options);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) == 0)
            return false;
        else
            return $result;
    }

    /**
     * executes SELECT or SHOW query and returns result as array of objects of given class
     * @param string $query
     * @param string $className
     * @param array $options
     * @param array $classConstructorArguments
     * @return array|false
     */
    public function queryObjArray($query, $className, $options = array(), $classConstructorArguments = NULL)
    {
        if (!class_exists($className)) {
            $this->_error(FQDBException::CLASS_NOT_EXIST, FQDBException::FQDB_CODE);
        }

        $statement = $this->_runQuery($query, $options);
        $result = $statement->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $className, $classConstructorArguments);
        if (count($result) == 0)
            return false;
        else
            return $result;
    }


    /**
     * executes SELECT or SHOW query and returns object of given class
     * @param string $query
     * @param string $className
     * @param array $options
     * @param array $classConstructorArguments
     * @return object|false
     */
    public function queryObj($query, $className, $options = array(), $classConstructorArguments = NULL)
    {
        if (!class_exists($className)) {
            $this->_error(FQDBException::CLASS_NOT_EXIST, FQDBException::FQDB_CODE);
        }

        $statement = $this->_runQuery($query, $options);
        $statement->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $className, $classConstructorArguments);

        return $statement->fetch();
    }

    /**
     * Execute query and apply a callback function to each row
     *
     * @param string $query
     * @param array $options
     * @param callable $callback
     * @return true
     */
    public function queryTableCallback($query, $options = [], $callback)
    {
        if(!is_callable($callback)) {
            $this->_error(FQDBException::NOT_CALLABLE_ERROR, FQDBException::FQDB_CODE);
        }
        $statement = $this->_runQuery($query, $options);
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            call_user_func($callback, $row);
        }
        return true; //executed successfully
    }

    /**
     * starts transaction
     */
    public function beginTransaction()
    {
        try {
            $this->_pdo->beginTransaction();
        } catch (\PDOException $e) {
            $this->_error($e->getMessage(), FQDBException::PDO_CODE, $e);
        }
    }

    /**
     * commits transaction
     */
    public function commitTransaction()
    {
        try {
            $this->_pdo->commit();
        } catch (\PDOException $e) {
            $this->rollbackTransaction();
            $this->_error($e->getMessage(), FQDBException::PDO_CODE, $e);
        }
    }

    /**
     * rollbacks transaction
     */
    public function rollbackTransaction()
    {
        try {
            $this->_pdo->rollBack();
        } catch (\PDOException $e) {
            $this->_error($e->getMessage(), FQDBException::PDO_CODE, $e);
        }
    }

    /**
     *
     * @param string $string
     * @param int $mode -- FQDB::QUOTE_DEFAULT (for regular data) or FQDB::QUOTE_IDENTIFIER for table and field names
     * @return string quoted
     */
    public function quote($string, $mode = self::QUOTE_DEFAULT)
    {
        if ($mode == self::QUOTE_IDENTIFIER)
        {
            // SQL ANSI default
            $quoteSymbol = '"';

            // MySQL and SQLite specific
            if ($this->_databaseServer == self::DB_MYSQL || $this->_databaseServer == self::DB_SQLITE) {
                $quoteSymbol = '`';
            }

            // quotes inside mysql field are so rare, that we'd rather ban them
            if (strpos($string, $quoteSymbol) !== false)
                $this->_error(FQDBException::IDENTIFIER_QUOTE_ERROR, FQDBException::FQDB_CODE);

            return $quoteSymbol . $string . $quoteSymbol;
        }
        else {
            return $this->_pdo->quote($string);
        }
    }

    /**
     * executes prepared \PDO query
     * @param string $query
     * @param array $options
     * @return \PDOStatement PDO statement from query
     */
    private function _runQuery($query, $options)
    {
        $this->_testQueryStarts($query, '[select|show]');
        $statement = $this->_executeQuery($query, $options);
        return $statement;
    }


    /**
     * gathers Warning info from \PDO
     * @param string $sqlQueryString SQL query string with placeholders
     * @param array $options options passed to query
     * @return string
     */
    private function _getWarnings($sqlQueryString, $options=[])
    {
        if ($this->_databaseServer === self::DB_MYSQL) {
            $stm = $this->_pdo->query('SHOW WARNINGS');
            $sqlWarnings = $stm->fetchAll(\PDO::FETCH_ASSOC);
        }
        else {
            $sqlWarnings = [['Message' => 'WarningReporting not impl. for '.$this->_pdo->getAttribute(\PDO::ATTR_DRIVER_NAME)]];
        }


        if (count($sqlWarnings) > 0) {
            $warnings = "Query:\n{$sqlQueryString}\n";

            if (!empty($options)) {
                $warnings .= "Params: (";

                foreach ($options as $key => $value) {
                    $warnings .= $key . '=' . json_encode($value) . ', ';
                }

                $warnings = substr($warnings, 0, -2) . ")\n";
            }


            $warnings .= "Produced Warnings:";

            foreach($sqlWarnings as $warn)
                $warnings .= "\n* " . $warn['Message'];

            return $warnings;
        }

        return '';
    }

    /**
     * executes prepared \PDO query
     * @param  string $sqlQueryString
     * @param array $options placeholders values
     * @param bool $needsLastInsertId should _executeQuery return lastInsertId
     * @return int|\PDOStatement|string
     */
    private function _executeQuery($sqlQueryString, $options, $needsLastInsertId = false)
    {
        try {
            $lastInsertId = 0;
            $statement = $this->_pdo->prepare($sqlQueryString);

            $this->_preExecuteOptionsCheck($sqlQueryString, $options);

            // warning! it is important to pass $value by reference here, since
            // bindParam also binds parameter by reference (and the value itself is changing)
            foreach ($options as $placeholder => &$value) {
                if (is_array($value)) {
                    $statement->bindParam($placeholder, $value['data'], $value['type']);
                } else {
                    $statement->bindParam($placeholder, $value);
                }
            }

            $statement->execute(); //options are already bound to query

            if ($needsLastInsertId)
                $lastInsertId = $this->_pdo->lastInsertId(); // if table has no PRI KEY, there will be 0

        } catch (\PDOException $e) {
            $this->_error($e->getMessage(), FQDBException::PDO_CODE, $e, [$sqlQueryString, $options]);
        }

        if ($this->_warningReporting) {
            $warningMessage = $this->_getWarnings($sqlQueryString, $options);

            if (!empty($warningMessage)) {

                if (isset($this->_warningHandler)) {
                    call_user_func($this->_warningHandler, $warningMessage);
                } else {
                    trigger_error($warningMessage, E_USER_WARNING); // default warning handler
                }

            }
        }

        return $needsLastInsertId ? $lastInsertId : $statement;
    }

    /**
     * @param $sqlQueryString - original SQL string
     * @param $options - options set
     * @throws FQDBException - when placeholders are not set properly
     */
    private function _preExecuteOptionsCheck($sqlQueryString, $options)
    {
        preg_match_all('/:[a-z]\w*/u', $sqlQueryString, $placeholders);
        // !!!!WARNING!!!! placeholders SHOULD start form lowercase letter!

        if (empty($placeholders) || empty($placeholders[0]))
            return; //no placeholders found

        foreach ($placeholders[0] as $placeholder) {
            if (!array_key_exists($placeholder, $options)) {
                //placeholder not set oops

                $msg = FQDBException::PLACEHOLDERS_ERROR;
                $msg .= ' '.json_encode($options);
                $msg .= ' '.json_encode($placeholders);
                $msg .= ' '.$sqlQueryString;

                $this->_error($msg, FQDBException::FQDB_CODE);
            }
        }
    }


    /**
     * checks if query starts correctly
     * @param string $query
     * @param string $needle
     * @throws \Readdle\Database\FQDBException if its not
     */
    private function _testQueryStarts($query, $needle)
    {
        if (!preg_match("/^$needle.*/i", $query)) {
            $this->_error(FQDBException::WRONG_QUERY, FQDBException::FQDB_CODE);
        }
    }

    /**
     * handle Errors
     * @param string $message error text
     * @param int $code code 0 - FQDB, 1 - PDO
     * @param \Exception $exception previous Exception
     * @param array $context
     * @throws \Readdle\Database\FQDBException if its not
     */
    private function _error($message, $code, $exception = null, $context = [])
    {
        if (isset($this->_errorHandler)) {
            call_user_func($this->_errorHandler, $message, $code, $exception, $context);
            trigger_error('FQDB error handler function should die() or throw another exception!', E_ERROR);
        }
        else {
            throw new FQDBException($message, $code, $exception);
        }
    }


    private function _callable($func) {
        if (is_callable($func)) {
            return $func;
        } else {
            if ($func !== null)
                $this->_error(FQDBException::NOT_CALLABLE_ERROR, FQDBException::FQDB_CODE);
            return null;
        }
    }

    /**
     * sets a callback function to run before any UPDATE query
     * @param callable $func
     */
    public function setBeforeUpdateHandler($func)
    {
        $this->_beforeUpdateHandler = $this->_callable($func);
    }

    /**
     * sets a callback function to run before any DELETE query
     * @param callable $func
     */
    public function setBeforeDeleteHandler($func)
    {
        $this->_beforeDeleteHandler = $this->_callable($func);
    }

    /**
     * sets Warnings handling function
     * @param callable $func
     */
    public function setWarningHandler($func)
    {
        $this->_warningHandler = $this->_callable($func);
    }

    /**
     *
     * @return callable that runs before DELETE
     */
    public function getBeforeDeleteHandler()
    {
        return $this->_beforeDeleteHandler;
    }

    /**
     *
     * @return callable that runs before UPDATE
     */
    public function getBeforeUpdateHandler()
    {
        return $this->_beforeUpdateHandler;
    }

    /**
     *
     * @return callable that handles Warnings
     */
    public function getWarningHandler()
    {
        return $this->_warningHandler;
    }

    /**
     * sets Warning reporting on\off
     * @param boolean $bool
     */
    public function setWarningReporting($bool = true)
    {
        $this->_warningReporting = (bool)$bool;
    }

    /**
     * @return bool if warning reporting is enabled (default -- no)
     */
    public function getWarningReporting() {
        return (bool)$this->_warningReporting;
    }

    /**
     * sets Errors handling function
     * @param callable $func
     */
    public function setErrorHandler($func)
    {
        $this->_errorHandler = $this->_callable($func);
    }

    /**
     *
     * @return callable that handles Errors
     */
    public function getErrorHandler()
    {
        return $this->_errorHandler;
    }


}
