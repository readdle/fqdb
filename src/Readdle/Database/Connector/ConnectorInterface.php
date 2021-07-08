<?php

namespace Readdle\Database\Connector;

interface ConnectorInterface
{
    /** @throws \PDOException */
    public function connect(array $options): \PDO;
    
    public function supports(array $options): bool;
}
