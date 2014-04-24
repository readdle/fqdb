<?php
namespace Readdle\Database;

class FQDB
{

    /**
     * @var $_pdo \PDO object
     * @var $_query \PDO query
     */
    protected $_pdo;
    protected $_query;
    protected $_options;

    protected $_beforeUpdateHandler;
    protected $_beforeDeleteHandler;
    protected $_warningHandler;
    protected $_warningReporting = false;
    protected $_errorHandler;
    protected $_lastInsertId;

    const NO_DB_CONNECTION_ERROR = 'No DB connection';
    const NO_ACTIVE_QUERY_ERROR = 'No active query error';
    const DB_ALREADY_CONNECTED = 'Already have active connection to a DB';
    const NOT_CALLABLE_ERROR = 'param is not callable';
    const WRONG_QUERY = 'given query doesn\'t fit called method';
    const CLASS_NOT_EXIST = ' class not exists';
    const PLACEHOLDERS_ERROR = 'Placeholders not set properly!';

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    public function __destruct()
    {
        $this->_pdo = null; //close \PDO connection
    }

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

        if (isset($this->_beforeDeleteHandler)) call_user_func_array($this->_beforeDeleteHandler, array($query, $options));

        $this->_preparePdoQuery($query);
        $this->_execute($options);

        return $this->_query->rowCount();
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

        if (isset($this->_beforeUpdateHandler)) call_user_func_array($this->_beforeUpdateHandler, array($query, $options));

        $this->_preparePdoQuery($query);
        $this->_execute($options);

        return $this->_query->rowCount();
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
        $this->_preparePdoQuery($query);
        $this->_execute($options);

        return $this->_query->rowCount();
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
        $this->_preparePdoQuery($query);
        $this->_execute($options);

        return $this->_lastInsertId;
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
        $this->_preparePdoQuery($query);
        $this->_execute($options);

        return $this->_query->rowCount();
    }

    /**
     * executes INSERT but didn't handle any thrown Exceptions
     *
     * @param string $query
     * @param array $options
     * @return int rows affected
     */
    public function insertException($query, $options = array())
    {
        $this->_testQueryStarts($query, 'insert');
        $this->_preparePdoQuery($query);
        $this->_query->execute($options); //let outside code catch any Exception and handle it

        return $this->_query->rowCount();
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
        $this->_preparePdoQuery($query);
        $this->_execute($options);

        return $this->_query->rowCount();
    }

    /**
     * execute given SQL query. Please DON'T use instead of other functions
     *
     * example - use this if you need something like "TRUNCATE TABLE `users`"
     * use it VERY CAREFULLY!
     *
     * @param string $query
     * @return boolean
     */
    public function execute($query)
    {
        $this->_preparePdoQuery($query);
        return $this->_execute([]);
    }

    /**
     * executes SELECT or SHOW query and returns 1st returned element
     * @param string $query
     * @param array $options
     * @return mixed
     */
    public function queryValue($query, $options = array())
    {
        $this->_runQuery($query, $options);
        if ($this->_numRows()) {
            $this->_query->setFetchMode(\PDO::FETCH_NUM);
            $result = $this->_query->fetch();
            return $result[0];
        }
        return false; //no results - maybe we should return smth else
    }

    /**
     * executes SELECT or SHOW query and returns 1st row as assoc array
     * @param string $query
     * @param array $options
     * @return array|false
     */
    public function queryAssoc($query, $options = array())
    {
        $this->_runQuery($query, $options);
        if ($this->_numRows()) {
            $this->_query->setFetchMode(\PDO::FETCH_ASSOC);
            return $this->_query->fetch();
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
        $this->_runQuery($query, $options);
        if ($this->_numRows()) {
            $this->_query->setFetchMode(\PDO::FETCH_NUM);
            return $this->_query->fetch();
        }
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
        $this->_runQuery($query, $options);
        if ($this->_numRows()) {
            $this->_query->setFetchMode(\PDO::FETCH_NUM);
            $result = array();
            $i = 0;
            while (list($result[$i]) = $this->_query->fetch()) $i++;
            unset($result[$i]);
            return $result;
        }
        return false; //no results - maybe we should return smth else
    }

    /**
     * executes SELECT or SHOW query and returns result as assoc array
     * @param string $query
     * @param array $options
     * @return array|false
     */
    public function queryTable($query, $options = array())
    {
        $this->_runQuery($query, $options);
        if ($this->_numRows()) {
            $this->_query->setFetchMode(\PDO::FETCH_ASSOC);
            return $this->_fetchResult();
        }
        return false; //no results - maybe we should return smth else
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
        if (!class_exists($className)) $this->_throwError($className . self::CLASS_NOT_EXIST);
        $this->_runQuery($query, $options);
        if ($this->_numRows()) {
            $this->_query->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $className);
            return $this->_fetchResult();
        }
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
        if (!class_exists($className)) $this->_throwError($className . self::CLASS_NOT_EXIST);
        $this->_runQuery($query, $options);
        if ($this->_numRows()) {
            $this->_query->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $className);
            return $this->_query->fetch();
        }
        return false; //no results - maybe we should return smth else
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
     * @param $query
     * @return \PDOStatement
     */
    public function prepare($query)
    {
        return $this->_pdo->prepare($query);
    }

    /**
     * checks do we have active query
     */
    protected function _checkQuery()
    {
        if ($this->_query === null) {
            $this->_throwError(self::NO_ACTIVE_QUERY_ERROR);
        }
    }

    /**
     * prepares \PDO query
     * @param string $query
     */
    protected function _preparePdoQuery($query)
    {
        try {
            $this->_query = $this->_pdo->prepare($query);
        } catch (\PDOException $e) {
            $this->_throwError($e->getMessage());
        }
    }

    /**
     * executes prepared \PDO query
     * @param string $query
     * @param array $options
     */
    protected function _runQuery($query, $options)
    {
        $this->_testQueryStarts($query, '[select|show]');
        $this->_preparePdoQuery($query);
        $this->_execute($options);
    }

    /**
     * gathers Error information from \PDO
     * @return string
     */
    protected function _getErrorInfo()
    {
        if ($err = $this->_query->errorInfo()) {
            return 'SQLSTATE: ' . $err[0] . "\n" . $err[2];
        }
        return '';
    }

    /**
     * gathers Warning info from \PDO
     * @return string|boolean
     */
    protected function _getWarnings()
    {
        $stm = $this->_pdo->query('SHOW WARNINGS');
        if ($stm->rowCount()) { //there is some warnings
            $stm->setFetchMode(\PDO::FETCH_ASSOC);
            $warnings = "Query:\n /* " . $this->_query->queryString . "\nactual values (";

            if (!empty($this->_options))
                foreach ($this->_options as $key => $value)
                    $warnings .= $key . '=' . print_r($value, true) . ', ';
            $warnings = substr($warnings, 0, -2) . ") */ \nproduced Warnings:";
            while ($warn = $stm->fetch()) {
                $warnings .= "\n* " . $warn['Message'];
            }
            return $warnings;
        }
        return false;
    }

    /**
     * executes prepared \PDO query
     * @param array $options placeholders values
     */
    protected function _execute($options)
    {
        try {
            $this->_preExecuteOptionsCheck($options);
            $this->_options = $options; //store options for errors/warnings handlers

            foreach ($options as $placeholder => $value) {
                $this->_bindParam($placeholder, $value);
            }

            $this->_query->execute(); //options already binded to query
            $this->_lastInsertId = $this->_pdo->lastInsertId(); //if table has no PRI KEY, there will be 0
        } catch (\PDOException $e) {
            $this->_throwError($e->getMessage());
        }
        if ($this->_warningReporting) {
            if ($warningMessage = $this->_getWarnings()) {
                $this->_throwWarning($warningMessage);
            }
        }
    }

    /**
     * fetches result and returns it as array
     * @return array
     */
    protected function _fetchResult()
    {
        $result = array();
        while ($row = $this->_query->fetch()) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * counts affected rows
     * @return int affected rows count
     */
    protected function _numRows()
    {
        return $this->_query->rowCount();
    }

    /**
     * checks if query starts correctly
     * throws error if its not
     * @param string $query
     * @param string $needle
     */
    protected function _testQueryStarts($query, $needle)
    {
        if (!preg_match("/^$needle.*/i", $query)) {
            $this->_throwError(self::WRONG_QUERY);
        }
    }

    /**
     * handle Errors
     * @param string $message error text
     */
    protected function _throwError($message)
    {
        if (isset($this->_errorHandler)) {
            call_user_func($this->_errorHandler, $message);
        } else {
            trigger_error($message, E_USER_ERROR);
            die(); // default error handler
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
        if (isset($this->_beforeDeleteHandler)) return $this->_beforeDeleteHandler;
        return false;
    }

    /**
     *
     * @return function that runs before UPDATE
     */
    public function getBeforeUpdateHandler()
    {
        if (isset($this->_beforeUpdateHandler)) return $this->_beforeUpdateHandler;
        return false;
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
        if (isset($this->_warningHandler)) return $this->_warningHandler;
        return false;
    }

    /**
     * sets Warnign reporting on\off
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
        if (isset($this->_errorHandler)) return $this->_errorHandler;
        return false;
    }

    protected function _preExecuteOptionsCheck($options)
    {
        preg_match_all('/:[a-z]\w*/u', $this->_query->queryString, $placeholders); //!!!!WARNING!!!! placeholders SHOULD start form lowcase letter!!
        if (empty($placeholders) || empty($placeholders[0])) return; //no placeholders found
        foreach ($placeholders[0] as $placeholder) {
            if (!array_key_exists($placeholder, $options)) {
                //placeholder not set oops
                throw new FQDBException('Placeholders not set properly!' . json_encode($options) . ' ' . json_encode($placeholders) . "{$this->_query->queryString}");
            }
        }
    }

    /**
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->_pdo;
    }

    /**
     * use at your own risk!
     *
     * @return \PDO
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * bind Param for a PDO statement to query
     *
     * $value should contain actual value, or array:
     * ['data' => actualData, 'type' => PDO::PARAM_TYPE] (PDO::PARAM_STR by default)
     * for example:
     * ['data' => 123, 'type' => \PDO::PARAM_INT]
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    protected function _bindParam($name, $value)
    {
        if (is_array($value)) {
            $this->_query->bindParam($name, $value['data'], $value['type']);
        } else {
            $this->_query->bindParam($name, $value);
        }
        return $this;
    }
}
