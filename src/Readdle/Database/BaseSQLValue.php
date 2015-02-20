<?php
/**
 * Created by PhpStorm.
 * User: andrian
 * Date: 2/19/15
 * Time: 11:32 AM
 */

namespace Readdle\Database;


abstract class BaseSQLValue {

    /**
     * @param \PDOStatement $statement
     * @param string $placeholder
     * @throws \Exception
     */
    public function bind($statement, $placeholder) {
        throw new \Exception("unimplemented");
    }


}