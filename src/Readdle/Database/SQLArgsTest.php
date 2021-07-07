<?php declare(strict_types=1);

namespace Readdle\Database;

final class SQLArgsTest extends \PHPUnit\Framework\TestCase
{
    public function testSQLArg(): void
    {
        $item = new \Readdle\Database\SQLArgs();
        $this->assertEquals(\json_encode($item), '[]');

        $item = new \Readdle\Database\SQLArgs(null);
        $this->assertEquals(\json_encode($item), '[null]');

        $item = new \Readdle\Database\SQLArgs(1, 2, 3, 4, 5, 6);
        $this->assertEquals(\json_encode($item), '[1,2,3,4,5,6]');

        $item = new \Readdle\Database\SQLArgs('foo', 'bar', 'baz');
        $this->assertEquals(\json_encode($item), '["foo","bar","baz"]');

        $item = new \Readdle\Database\SQLArgs('foo', 1, 'baz', null);
        $this->assertEquals(\json_encode($item), '["foo",1,"baz",null]');
    }
}
