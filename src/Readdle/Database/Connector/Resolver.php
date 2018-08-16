<?php

namespace Readdle\Database\Connector;

class Resolver
{
    /** @var ConnectorInterface[] */
    private $connectors = [];
    
    public function __construct()
    {
        $this->registerConnector(new DSNConnector());
    }
    
    public function registerConnector(ConnectorInterface $connector)
    {
        $this->connectors[get_class($connector)] = $connector;
    }
    
    public function resolve($options)
    {
        foreach ($this->connectors as $connector) {
            if ($connector->supports($options)) {
                return $connector;
            }
        }
        
        throw new \InvalidArgumentException("There are no supported connectors for provided options");
    }
}
