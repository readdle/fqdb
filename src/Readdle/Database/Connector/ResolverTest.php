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

final class EverythingConnector implements \Readdle\Database\Connector\ConnectorInterface
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
    
    public function testConnectorWithHigherPriorityTakesOverFromDSNConnector(): void
    {
        $this->resolver->registerConnector(new SQLiteMemoryConnector(), 10);
        $connector = $this->resolver->resolve(["dsn" => "mysql:host=localhost;dbname=test"]);
        $this->assertInstanceOf(SQLiteMemoryConnector::class, $connector);
    }
    
    public function testDSNConnectorStillWinsOverDefaultPriorityConnectors(): void
    {
        $this->resolver->registerConnector(new SQLiteMemoryConnector());
        $connector = $this->resolver->resolve(["dsn" => "mysql:host=localhost;dbname=test"]);
        $this->assertInstanceOf(DSNConnector::class, $connector);
    }
    
    public function testEqualPrioritiesAreResolvedInRegistrationOrder(): void
    {
        $this->resolver->registerConnector(new SQLiteMemoryConnector(), 5);
        $this->resolver->registerConnector(new EverythingConnector(), 5);
        $connector = $this->resolver->resolve([]);
        $this->assertInstanceOf(SQLiteMemoryConnector::class, $connector);
    }
    
    public function testResolveThrowsWhenNothingSupportsTheOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->resolve(["dsn" => ""]);
    }
}
