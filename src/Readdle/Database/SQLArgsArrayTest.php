<?php declare(strict_types=1);

namespace Readdle\Database;

final class SQLArgsArrayTest extends \PHPUnit\Framework\TestCase
{
    public function testSQLArgsArray(): void
    {
        $array = [];
        $item  = new \Readdle\Database\SQLArgsArray($array);
        $this->assertEquals(\json_encode($item), '[]');
        
        $array = [1, 2, 3, 4, 5, 6];
        $item  = new \Readdle\Database\SQLArgsArray($array);
        $this->assertEquals(\json_encode($item), '[1,2,3,4,5,6]');
        
        $array = ['foo', 'bar', 'baz'];
        $item  = new \Readdle\Database\SQLArgsArray($array);
        $this->assertEquals(\json_encode($item), '["foo","bar","baz"]');
        
        $array = ['foo', 1, 'baz', null];
        $item  = new \Readdle\Database\SQLArgsArray($array);
        $this->assertEquals(\json_encode($item), '["foo",1,"baz",null]');
    }
}
