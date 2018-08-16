<?php

namespace Readdle\Database;

use Readdle\Database\Connector\ConnectorInterface;

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
    public function connect();
    public function registerConnector(ConnectorInterface $connector);
}
