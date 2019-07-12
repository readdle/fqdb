<?php

namespace Readdle\Database;

use Readdle\Database\Event\DeleteQueryStarted;
use Readdle\Database\Event\UpdateQueryStarted;

class FQDBWriteAPI extends FQDBQueryAPI {

    /**
     * executes DELETE query with placeholders in 2nd param
     * @param string $query
     * @param array $params
     * @return int affected rows count
     */
    public function delete($query, $params = array())
    {
        $this->_testQueryStarts($query, 'delete');
        $this->dispatch(new DeleteQueryStarted($query, $params));
    
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
        $this->dispatch(new UpdateQueryStarted($query, $params));
    
        $statement = $this->_executeQuery($query, $params);
        return $statement->rowCount();
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
}