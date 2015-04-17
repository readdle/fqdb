<?php

namespace Readdle\Database;


class FQDBWriteAPI extends FQDBQueryAPI {

    private $_beforeUpdateHandler;
    private $_beforeDeleteHandler;


    /**
     * code for delete / update
     * @param string $query
     * @param array $params
     * @param string $sqlPrefix
     * @param callable|null $handler
     * @return int affected rows count
     */
    private function doDeleteOrUpdate($query, $params, $sqlPrefix, $handler)
    {
        $this->_testQueryStarts($query, $sqlPrefix);

        if ($handler !== null)
            call_user_func_array($handler, [$query, $params]);

        $statement = $this->_executeQuery($query, $params);
        return $statement->rowCount();
    }


    /**
     * executes DELETE query with placeholders in 2nd param
     * @param string $query
     * @param array $params
     * @return int affected rows count
     */
    public function delete($query, $params = array())
    {
        return $this->doDeleteOrUpdate($query, $params, 'delete', $this->_beforeDeleteHandler);
    }

    /**
     * executes UPDATE query with placeholders in 2nd param
     * @param string $query
     * @param array $params PDO names params, starting with :
     * @return int affected rows count
     */
    public function update($query, $params = array())
    {
        return $this->doDeleteOrUpdate($query, $params, 'update', $this->_beforeUpdateHandler);
    }

    /**
     * executes INSERT query with placeholders in 2nd param
     * @param string $query
     * @param array $params
     * @return string last inserted id
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
     * @return integer affected rows count
     */
    public function replace($query, $params = array())
    {
        return $this->execute($query, $params, 'replace');
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



}