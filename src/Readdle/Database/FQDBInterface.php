<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 6/11/15
 * Time: 5:51 PM
 */

namespace Readdle\Database;


interface FQDBInterface {
    public function execute($sqlQuery, $params, $prefix);
    public function quote($string, $mode);
    public function beginTransaction();
    public function commitTransaction();
    public function rollbackTransaction();
    public function getPdo();
    public function setWarningHandler($func);
    public function getWarningHandler();
    public function setWarningReporting($bool = true);
    public function getWarningReporting();
    public function setErrorHandler($func);
    public function getErrorHandler();
}
