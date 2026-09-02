<?php declare(strict_types=1);

namespace Readdle\Database\Connector;

use InvalidArgumentException;

final class Resolver
{
    /** @var ConnectorInterface[] */
    private $connectors = [];
    /** @var int[] connector class name => priority */
    private $priorities = [];
    
    public function __construct()
    {
        $this->registerConnector(new DSNConnector());
    }
    
    /**
     * @param int $priority connectors with a higher priority are resolved first;
     *                      ties are resolved in registration order. The built-in
     *                      DSNConnector is registered with priority 0, so pass
     *                      anything greater than that to take over from it.
     */
    public function registerConnector(ConnectorInterface $connector, int $priority = 0): void
    {
        $className                    = get_class($connector);
        $this->connectors[$className] = $connector;
        $this->priorities[$className] = $priority;
    }
    
    public function resolve(array $options): ConnectorInterface
    {
        foreach ($this->sortedConnectors() as $connector) {
            if ($connector->supports($options)) {
                return $connector;
            }
        }
        
        throw new InvalidArgumentException("There are no supported connectors for provided options");
    }
    
    /**
     * @return ConnectorInterface[]
     */
    private function sortedConnectors(): array
    {
        $connectors = $this->connectors;
        
        if (count(array_unique($this->priorities)) < 2) {
            return $connectors; // nothing to sort, keep registration order
        }
        
        $priorities = $this->priorities;
        $order = array_combine(array_keys($connectors), range(1, count($connectors)));
        
        uksort(
            $connectors,
            static function (string $left, string $right) use ($priorities, $order): int {
                return ($priorities[$right] <=> $priorities[$left]) ?: ($order[$left] <=> $order[$right]);
            }
        );
        
        return $connectors;
    }
}
