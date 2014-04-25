<?php
namespace Readdle\Database;

// TODO: unit test
// TODO: error and handling


final class FQDB
{

    /**
     * @var \PDO $_pdo - PDO object
     */
    private $_pdo;


    private $_beforeUpdateHandler;
    private $_beforeDeleteHandler;
    private $_warningHandler;
    private $_warningReporting = false;
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
            $this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $this;
        } catch (\PDOException $e) {
            $this->_throwError($e->getMessage());
            die();
        }
    }

    /**
     * executes DELETE query with placeholders in 2nd param
     * @param string $query
     * @param array $options
     * @return int affected rows count
     */
    public function delete($query, $options = array())
    {
        $this->_testQueryStarts($query, 'delete');

        if (isset($this->_beforeDeleteHandler) && is_callable($this->_beforeDeleteHandler))
            call_user_func_array($this->_beforeDeleteHandler, [$query, $options]);

        $statement = $this->_preparePdoQuery($query);
        $this->_executeStatement($statement, $options);
        return $statement->rowCount();
    }

    /**
     * executes UPDATE query with placeholders in 2nd param
     * @param string $query
     * @param array $options
     * @return int affected rows count
     */
    public function update($query, $options = array())
    {
        $this->_testQueryStarts($query, 'update');

        if (isset($this->_beforeUpdateHandler))
            call_user_func_array($this->_beforeUpdateHandler, [$query, $options]);

        $statement = $this->_preparePdoQuery($query);
        $this->_executeStatement($statement, $options);
        return $statement->rowCount();
    }

    /**
     * executes SET query with placeholders in 2nd param
     * @param string $query
     * @param array $options
     * @return int affected rows count
     */
    public function set($query, $options = array())
    {
        $this->_testQueryStarts($query, 'set');
        $statement = $this->_preparePdoQuery($query);
        $this->_executeStatement($statement, $options);
        return $statement->rowCount();
    }

    /**
     * executes INSERT query with placeholders in 2nd param
     * @param string $query
     * @param array $options
     * @return int last inserted id
     */
    public function insert($query, $options = array())
    {
        $this->_testQueryStarts($query, 'insert');

        $statement = $this->_preparePdoQuery($query);
        return $this->_executeStatement($statement, $options, true);
    }

    /**
     * executes INSERT query with placeholders in 2nd param
     * @param string $query
     * @param array $options
     * @return int rows affected
     */
    public function insertIgnore($query, $options = array())
    {
        $this->_testQueryStarts($query, 'insert ignore');
        $statement = $this->_preparePdoQuery($query);
        $this->_executeStatement($statement, $options);
        return $statement->rowCount();
    }



    /**
     * executes REPLACE query with placeholders in 2nd param
     * @param string $query
     * @param array $options
     * @return int affected rows count
     */
    public function replace($query, $options = array())
    {
        $this->_testQueryStarts($query, 'replace');
        $statement = $this->_preparePdoQuery($query);
        $this->_executeStatement($statement, $options);
        return $statement->rowCount();
    }

    /**
     * Execute given SQL query. Please DON'T use instead of other functions
     *
     * example - use this if you need something like "TRUNCATE TABLE `users`"
     * use it VERY CAREFULLY!
     *
     * @param string $query
     * @return int affected rows count
     */
    public function execute($query)
    {
        $statement = $this->_preparePdoQuery($query);
        $this->_executeStatement($statement, []);
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
        if ($statement->rowCount()) {
            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            return $statement->fetch();
        }
        return false; //no results - maybe we should return smth else
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


        return false; //no results - maybe we should return smth else
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
     * @return array|false
     */
    public function queryObjArray($query, $className, $options = array())
    {
        if (!class_exists($className)) {
            $this->_throwError($className . self::CLASS_NOT_EXIST);
        }
        $statement = $this->_runQuery($query, $options);
        if ($statement->rowCount()) {
            $statement->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $className);

            $result = [];
            while ($row = $statement->fetch()) {
                $result[] = $row;
            }

            return $result;
        }
        else
            return false; //no results - maybe we should return smth else
    }


    /**
     * executes SELECT or SHOW query and returns object of given class
     * @param string $query
     * @param string $className
     * @param array $options
     * @return object|false
     */
    public function queryObj($query, $className, $options = array())
    {
        if (!class_exists($className))
            $this->_throwError($className . self::CLASS_NOT_EXIST);

        $statement = $this->_runQuery($query, $options);
        if ($statement->rowCount()) {
            $statement->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $className);
            return $statement->fetch();
        }
        return false;
    }

    /**
     * starts transaction
     */
    public function beginTransaction()
    {
        try {
            $this->_pdo->beginTransaction();
        } catch (\PDOException $e) {
            $this->_throwError($e->getMessage());
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
            $this->_throwError($e->getMessage());
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
            $this->_throwError($e->getMessage());
        }
    }

    /**
     *
     * @param string $string
     * @return string quoted
     */
    public function quote($string)
    {
        return $this->_pdo->quote($string);
    }


    /**
     * prepares \PDO query
     * @param string $query
     * @return \PDOStatement PDO statement from query
     */
    private function _preparePdoQuery($query)
    {
        try {
            return $this->_pdo->prepare($query);
        } catch (\PDOException $e) {
            $this->_error(null, FQDBException::PDO_CODE, $e);
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

        $statement = $this->_preparePdoQuery($query);
        $this->_executeStatement($statement, $options);
        return $statement;
    }

    /**
     * gathers Error information from \PDO
     * @return string
     */
    private function _getErrorInfo()
    {
        if ($err = $this->_query->errorInfo()) {
            return 'SQLSTATE: ' . $err[0] . "\n" . $err[2];
        }
        return '';
    }

    /**
     * gathers Warning info from \PDO
     * @param string SQL query string with placeholders
     * @param array $options options passed to query
     * @return string
     */
    private function _getWarnings($sqlQueryString, $options=[])
    {
        $stm = $this->_pdo->query('SHOW WARNINGS');
        if ($stm->rowCount()) { //there is some warnings
            $stm->setFetchMode(\PDO::FETCH_ASSOC);
            $warnings = "Query:\n /* {$sqlQueryString}\n";


            if (!empty($options)) {
                $warnings .= "Actual values (";

                foreach ($options as $key => $value) {
                    $warnings .= $key . '=' . print_r($value, true) . ', ';
                }

                $warnings = substr($warnings, 0, -2) . ") */\n";
            }


            $warnings .= "Produced Warnings:";
            while ($warn = $stm->fetch()) {
                $warnings .= "\n* " . $warn['Message'];
            }
            return $warnings;
        }
        return '';
    }

    /**
     * executes prepared \PDO query
     * @param \PDOStatement $statement PDO Statement
     * @param array $options placeholders values
     * @param bool $needsLastInsertId should _executeStatement return lastInsertId
     * @return int last insert id or 0
     */
    private function _executeStatement($statement, $options, $needsLastInsertId = false)
    {
        $sqlQueryString = $statement->queryString;
        $lastInsertId = 0;

        try {
            $this->_preExecuteOptionsCheck($sqlQueryString, $options);

            foreach ($options as $placeholder => $value) {
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
            $this->_error(null, FQDBException::PDO_CODE, $e);
        }

        if ($this->_warningReporting) {
            $warningMessage = $this->_getWarnings($sqlQueryString, $options);

            if (!empty($warningMessage)) {
                $this->_throwWarning($warningMessage);
            }
        }

        return $lastInsertId;
    }

    /**
     * @param $sqlQueryString - original SQL string
     * @param $options - options set
     * @throws FQDBException - when placeholders are not set properly
     */
    private function _preExecuteOptionsCheck($sqlQueryString, $options)
    {
        preg_match_all('/:[a-z]\w*/u', $sqlQueryString, $placeholders); // !!!!WARNING!!!! placeholders SHOULD start form lowercase letter!

        if (empty($placeholders) || empty($placeholders[0]))
            return; //no placeholders found

        foreach ($placeholders[0] as $placeholder) {
            if (!array_key_exists($placeholder, $options)) {
                //placeholder not set oops
                throw new FQDBException('Placeholders not set properly!' . json_encode($options) . ' ' . json_encode($placeholders) . "{$sqlQueryString}");
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
        if (!preg_match("/^{$needle}.*/i", $query)) {
            $this->_error(FQDBException::WRONG_QUERY, FQDBException::FQDB_CODE);
        }
    }

    /**
     * handle Errors
     * @param string $message error text
     * @param int $code code 0 - FQDB, 1 - PDO
     * @param \Exception $exception previous Exception
     * @throws \Readdle\Database\FQDBException if its not
     */
    protected function _error($message, $code, $exception = null)
    {
        if (isset($this->_errorHandler)) {
            call_user_func($this->_errorHandler, $message, $code, $exception);
        }
        else {
            throw new FQDBException($message, $code, $exception);
        }
    }

    /**
     * handle Warnings
     * @param string $message
     */
    protected function _throwWarning($message)
    {
        if (isset($this->_warningHandler)) {
            call_user_func($this->_warningHandler, $message);
        } else {
            trigger_error($message, E_USER_WARNING); // default warning handler
        }
    }

    /**
     * sets a callback function to run before any UPDATE query
     * @param callable $func
     */
    public function setBeforeUpdateHandler($func)
    {
        if (is_callable($func)) {
            $this->_beforeUpdateHandler = $func;
        } else {
            $this->_throwError(self::NOT_CALLABLE_ERROR);
        }
    }

    /**
     * sets a callback function to run before any DELETE query
     * @param callable $func
     */
    public function setBeforeDeleteHandler($func)
    {
        if (is_callable($func)) {
            $this->_beforeDeleteHandler = $func;
        } else {
            $this->_throwError(self::NOT_CALLABLE_ERROR);
        }
    }

    /**
     *
     * @return function that runs before DELETE
     */
    public function getBeforeDeleteHandler()
    {
        return $this->_beforeDeleteHandler;
    }

    /**
     *
     * @return function that runs before UPDATE
     */
    public function getBeforeUpdateHandler()
    {
        return $this->_beforeUpdateHandler;
    }

    /**
     * sets Warnings handling function
     * @param callable $func
     */
    public function setWarningHandler($func)
    {
        if (is_callable($func)) {
            $this->_warningHandler = $func;
        } else {
            $this->_throwError(self::NOT_CALLABLE_ERROR);
        }
    }

    /**
     *
     * @return function that handles Warnings
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
     * sets Errors handling function
     * @param callable $func
     */
    public function setErrorHandler($func)
    {
        if (is_callable($func)) {
            $this->_errorHandler = $func;
        } else {
            $this->_throwError(self::NOT_CALLABLE_ERROR);
        }
    }

    /**
     *
     * @return function that handles Errors
     */
    public function getErrorHandler()
    {
        return $this->_errorHandler;
    }


    /**
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->_pdo;
    }

}
