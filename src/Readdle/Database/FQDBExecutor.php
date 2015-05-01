<?php

namespace Readdle\Database;

class FQDBExecutor
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



    private $_warningHandler;
    private $_warningReporting = false;
    private $_databaseServer = self::DB_DEFAULT; // for SQL specific stuff
    private $_errorHandler;

    /**
     * Like PDO::PARAMS_*
     * Describes that passed data array is need to prepare for WHERE IN statement
     */
    const PARAM_FOR_IN_STATEMENT_VALUES = 101;


    /**
     * connects to DB, using params below
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $driver_options
     */
    public function __construct($dsn, $username = '', $password = '', $driver_options = array())
    {
        try {
            $this->_pdo = new \PDO($dsn, $username, $password, $driver_options);
            if (strpos($dsn, 'mysql') !== false)
                $this->_databaseServer = self::DB_MYSQL;
            else if (strpos($dsn, 'sqlite') !== false)
                $this->_databaseServer = self::DB_SQLITE;

            $this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $this->_error($e->getMessage(), FQDBException::PDO_CODE, $e);
            trigger_error('FQDB Fatal', E_ERROR);
        }
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
     * Find WHERE IN statements and converts sqlQueryString and $options
     * to format needed for WHERE IN statement run
     *
     * @param string $sqlQueryString
     * @param $options placeholders values
     * @return array queryString options
     */
    private function _prepareStatement($sqlQueryString, $options)
    {
        $statementNum = 0;
        foreach($options as $placeholder => $value) {
            if (is_object($value) && ($value instanceof SQLArgs || $value instanceof SQLArgsArray)) {
                $args = $value->toArray();

                if (!is_array($args)) {
                    $this->_error(FQDBException::INTERNAL_ASSERTION_FAIL, FQDBException::FQDB_CODE);
                }

                $statementNum++;
                $valueInStatementNum = 0;
                $whereInStatement = [];
                foreach ($args as $inStatementValue) {
                    $valueInStatementNum++;
                    $whereInStatement[':where_in_statement_'.$statementNum.'_'.$valueInStatementNum] = $inStatementValue;
                }

                $sqlQueryString = str_replace($placeholder, implode(', ', array_keys($whereInStatement)), $sqlQueryString);

                $options = array_merge($options, $whereInStatement);

                unset($options[$placeholder]);
            }
        }

        return [
            $sqlQueryString,
            $options
        ];
    }

    /**
     * @param string $sqlQueryString
     * @param array $options
     */
    private function reportWarnings($sqlQueryString, $options) {

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
    }

    /**
     * @param array $options
     * @param \PDOStatement $statement
     */
    private function bindOptionsToStatement(&$options, \PDOStatement $statement) {

        // warning! it is important to pass $value by reference here, since
        // bindParam also binds parameter by reference (and the value itself is changing)
        foreach ($options as $placeholder => &$value) {

            if (is_array($value)) {
                $this->_error(FQDBException::DEPRECATED_API, FQDBException::FQDB_CODE);
            }
            else if (is_object($value) && $value instanceof BaseSQLValue) {
                $value->bind($statement, $placeholder);
            }
            else {
                $statement->bindParam($placeholder, $value);
            }
        }

    }

    /**
     * executes prepared \PDO query
     * @param  string $sqlQueryString
     * @param array $options placeholders values
     * @param bool $needsLastInsertId should _executeQuery return lastInsertId
     * @return \PDOStatement|string
     */
    protected function _executeQuery($sqlQueryString, $options, $needsLastInsertId = false)
    {
        try {
            list($sqlQueryString, $options) = $this->_prepareStatement($sqlQueryString, $options);

            $statement = $this->_pdo->prepare($sqlQueryString);

            $this->_preExecuteOptionsCheck($sqlQueryString, $options);

            $this->bindOptionsToStatement($options, $statement);

            $statement->execute(); //options are already bound to query

            if ($needsLastInsertId)
                $lastInsertId = $this->_pdo->lastInsertId(); // if table has no PRI KEY, there will be 0

        } catch (\PDOException $e) {
            $this->_error($e->getMessage(), FQDBException::PDO_CODE, $e, [$sqlQueryString, $options]);
            return 0; // for static analysis
        }

        $this->reportWarnings($sqlQueryString, $options);

        return isset($lastInsertId) ? $lastInsertId : $statement;
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
    protected function _testQueryStarts($query, $needle)
    {
        $query = trim($query, " \t\n\r");
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
    protected function _error($message, $code, $exception = null, $context = [])
    {
        if (isset($this->_errorHandler)) {
            call_user_func($this->_errorHandler, $message, $code, $exception, $context);
            trigger_error('FQDB error handler function should die() or throw another exception!', E_ERROR);
        }
        else {
            throw new FQDBException($message, $code, $exception);
        }
    }


    /**
     * @param $func
     * @return null
     */
    protected function _callable($func) {
        if (is_callable($func)) {
            return $func;
        } else {
            if ($func !== null)
                $this->_error(FQDBException::NOT_CALLABLE_ERROR, FQDBException::FQDB_CODE);
            return null;
        }
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
