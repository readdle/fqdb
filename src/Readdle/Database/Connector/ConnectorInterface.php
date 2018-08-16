<?php

namespace Readdle\Database\Connector;

interface ConnectorInterface
{
    /**
     * @param $options
     * @return \PDO
     * @throws \PDOException
     */
    public function connect($options);
    
    /**
     * @param $options
     * @return bool
     */
    public function supports($options);
}
