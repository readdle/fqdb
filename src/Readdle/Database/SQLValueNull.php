<?php
/**
 * Created by PhpStorm.
 * User: andrian
 * Date: 2/19/15
 * Time: 11:24 AM
 */

namespace Readdle\Database;


class SQLValueNull extends BaseSQLValue {

    /**
     * @param \PDOStatement $statement
     * @param string $placeholder
     */
    public function bind($statement, $placeholder) {
        $statement->bindValue($placeholder, NULL, \PDO::PARAM_NULL);
    }
}
