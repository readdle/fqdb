<?php declare(strict_types=1);

namespace Readdle\Database\Connector;

final class Resolver
{
    /** @var ConnectorInterface[] */
    private $connectors = [];
    
    public function __construct()
    {
        $this->registerConnector(new DSNConnector());
    }
    
    public function registerConnector(ConnectorInterface $connector): void
    {
        $this->connectors[\get_class($connector)] = $connector;
    }
    
    public function resolve(array $options): ConnectorInterface
    {
        foreach ($this->connectors as $connector) {
            if ($connector->supports($options)) {
                return $connector;
            }
        }
        
        throw new \InvalidArgumentException("There are no supported connectors for provided options");
    }
}
