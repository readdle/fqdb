<?php

class DSNConnectorTest extends \PHPUnit_Framework_TestCase
{
    private $connectData = [];
    private $connector;
    
    /**
     * @inheritdoc
     */
    public function connect(array $options)
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
        
        return true;
    }
    
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
