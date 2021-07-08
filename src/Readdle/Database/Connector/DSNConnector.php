<?php declare(strict_types=1);

namespace Readdle\Database\Connector;

final class DSNConnector implements ConnectorInterface
{
    private array $connectData = [];
    
    public function connect(array $options): \PDO
    {
        $this->connectData["dsn"]            = $options["dsn"];
        $this->connectData["username"]       = isset($options["username"]) ? $options["username"] : null;
        $this->connectData["password"]       = isset($options["password"]) ? $options["password"] : null;
        $this->connectData["driver_options"] = isset($options["driver_options"]) ? $options["driver_options"] : [];
        
        return new \PDO(
            $this->connectData["dsn"],
            $this->connectData["username"],
            $this->connectData["password"],
            $this->connectData["driver_options"]
        );
    }
    
    public function supports(array $options): bool
    {
        if (!\array_key_exists("dsn", $options)) {
            return false;
        }
        
        if (!\is_string($options["dsn"])) {
            return false;
        }
    
        if ("" === $options["dsn"]) {
            return false;
        }
        
        return true;
    }
}
