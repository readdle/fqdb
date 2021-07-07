<?php declare(strict_types=1);

namespace Readdle\Database\Connector;

class DSNConnectorTest extends \PHPUnit\Framework\TestCase
{
    private DSNConnector $connector;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->connector = new DSNConnector();
    }
    
    /**
     * @dataProvider supportProvider
     */
    public function testSupport(array $option, $expected): void
    {
        $actual = $this->connector->supports($option);
        $this->assertEquals($expected, $actual);
    }
    
    public function supportProvider(): array
    {
        return [
            [["username" => "john"], false],
            [["dsn" => new \stdClass()], false],
            [["dsn" => "any:dsn:here"], true],
        ];
    }
    
    public function testConnectReturnsPDO(): void
    {
        $pdo = $this->connector->connect(["dsn" => "sqlite::memory:"]);
        $this->assertInstanceOf(\PDO::class, $pdo);
    }
}
