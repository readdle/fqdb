<?php

class SQLiteMemoryConnector implements \Readdle\Database\Connector\ConnectorInterface
{
    public function connect($options)
    {
        return new \PDO("sqlite::memory:");
    }
    
    public function supports($options)
    {
        return true;
    }
}

class ResolverTest extends \PHPUnit_Framework_TestCase
{
    private $resolver;
    
    public function setUp()
    {
        parent::setUp();
        $this->resolver = new \Readdle\Database\Connector\Resolver();
    }
    
    public function testNewlyRegisteredConnectorWorks()
    {
        $this->resolver->registerConnector(new SQLiteMemoryConnector());
        $connector = $this->resolver->resolve(null);
        $this->assertInstanceOf(SQLiteMemoryConnector::class, $connector);
    }
}