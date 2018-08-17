<?php

namespace Readdle\Database\Connector;

class DSNConnector implements ConnectorInterface
{
    private $connectData = [];
    
    /**
     * @inheritdoc
     */
    public function connect($options)
    {
        $this->connectData["dsn"] = $options["dsn"];
        $this->connectData["username"] = isset($options["username"]) ? $options["username"] : null;
        $this->connectData["password"] = isset($options["password"]) ? $options["password"] : null;
        $this->connectData["driver_options"] = isset($options["driver_options"]) ? $options["driver_options"] : [];
        
        return new \PDO(
            $this->connectData["dsn"],
            $this->connectData["username"],
            $this->connectData["password"],
            $this->connectData["driver_options"]
        );
    }
    
    public function supports($options)
    {
        if (!is_array($options)) {
            return false;
        }
    
        if (!array_key_exists("dsn", $options)) {
            return false;
        }
        
        if (!is_string($options["dsn"])) {
            return false;
        }
        
        return true;
    }
}
