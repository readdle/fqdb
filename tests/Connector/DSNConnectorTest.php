<?php

class DSNConnectorTest extends \PHPUnit_Framework_TestCase
{
    private $connector;
    
    public function setUp()
    {
        parent::setUp();
        $this->connector = new \Readdle\Database\Connector\DSNConnector();
    }
    
    /**
     * @param $option
     * @param $expected
     * @dataProvider supportProvider
     */
    public function testSupport($option, $expected)
    {
        $actual = $this->connector->supports($option);
        $this->assertEquals($expected, $actual);
    }
    
    public function supportProvider()
    {
        return [
            ["username" => "john", false],
            [["username" => "john"], false],
            [["dsn" => new stdClass()], false],
            [["dsn" => "any:dsn:here"], true],
        ];
    }
    
    public function testConnectReturnsPDO()
    {
        $pdo = $this->connector->connect(["dsn" => "sqlite::memory:"]);
        $this->assertInstanceOf(\PDO::class, $pdo);
    }
}
