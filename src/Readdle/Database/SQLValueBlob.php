<?php
/**
 * Created by PhpStorm.
 * User: andrian
 * Date: 2/19/15
 * Time: 11:30 AM
 */

namespace Readdle\Database;


class SQLValueBlob extends BaseSQLValue
{


    /**
     * @var string $blog ;
     */
    private $blob;

    /**
     * @param $blob string
     */
    public function __construct($blob)
    {
        $this->blob = $blob;
    }


    public function getBlob()
    {
        return $this->blob;
    }

    /**
     * @param \PDOStatement $statement
     * @param string $placeholder
     */
    public function bind($statement, $placeholder)
    {
        $statement->bindParam($placeholder, $this->blob, \PDO::PARAM_LOB);
    }

    public function jsonSerialize()
    {
        return base64_encode($this->blob);
    }

}