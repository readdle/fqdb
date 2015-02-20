<?php
/**
 * Created by PhpStorm.
 * User: andrian
 * Date: 2/19/15
 * Time: 11:06 AM
 */

namespace Readdle\Database;


class SQLArgs {

    private $argsArray;

    public function __construct() {
        $this->argsArray = [];
        $args = func_get_args();

        foreach($args as $arg) {
            if (is_null($arg)) {
                $this->argsArray[] = new SQLValueNull();
            }
            else {
                $this->argsArray[] = $arg;
            }
        }

    }

    public function toArray() {
        return $this->argsArray;
    }

}