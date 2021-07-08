<?php declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// phpcs:disable Squiz.Classes.ClassFileName.NoMatch

namespace Readdle\Database\Connector;

final class SQLiteMemoryConnector implements \Readdle\Database\Connector\ConnectorInterface
{
    public function connect(array $options): \PDO
    {
        return new \PDO("sqlite::memory:");
    }
    
    public function supports(array $options): bool
    {
        return true;
    }
}

class ResolverTest extends \PHPUnit\Framework\TestCase
{
    private Resolver $resolver;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new Resolver();
    }
    
    public function testNewlyRegisteredConnectorWorks(): void
    {
        $this->resolver->registerConnector(new SQLiteMemoryConnector());
        $connector = $this->resolver->resolve([]);
        $this->assertInstanceOf(SQLiteMemoryConnector::class, $connector);
    }
}
