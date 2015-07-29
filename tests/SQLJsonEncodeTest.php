<?php

class SQLJsonEncodeTest extends PHPUnit_Framework_TestCase
{
    public function testSQLArgsArray()
    {
        $array = [];
        $item = new \Readdle\Database\SQLArgsArray($array);
        $this->assertEquals(json_encode($item), '[]');

        $array = [1, 2, 3, 4, 5, 6];
        $item = new \Readdle\Database\SQLArgsArray($array);
        $this->assertEquals(json_encode($item), '[1,2,3,4,5,6]');

        $array = ['foo', 'bar', 'baz'];
        $item = new \Readdle\Database\SQLArgsArray($array);
        $this->assertEquals(json_encode($item), '["foo","bar","baz"]');

        $array = ['foo', 1, 'baz', null];
        $item = new \Readdle\Database\SQLArgsArray($array);
        $this->assertEquals(json_encode($item), '["foo",1,"baz",null]');
    }

    public function testSQLArg()
    {
        $item = new \Readdle\Database\SQLArgs();
        $this->assertEquals(json_encode($item), '[]');

        $item = new \Readdle\Database\SQLArgs(null);
        $this->assertEquals(json_encode($item), '[null]');

        $item = new \Readdle\Database\SQLArgs(1, 2, 3, 4, 5, 6);
        $this->assertEquals(json_encode($item), '[1,2,3,4,5,6]');

        $item = new \Readdle\Database\SQLArgs('foo', 'bar', 'baz');
        $this->assertEquals(json_encode($item), '["foo","bar","baz"]');

        $item = new \Readdle\Database\SQLArgs('foo', 1, 'baz', null);
        $this->assertEquals(json_encode($item), '["foo",1,"baz",null]');
    }

    public function testSQLValueNull()
    {
        $item = new \Readdle\Database\SQLValueNull();
        $this->assertEquals(json_encode($item), 'null');
    }

    public function testSQLValueBlob()
    {
        $content = file_get_contents(__DIR__ . '/blob');
        $item = new \Readdle\Database\SQLValueBlob($content);
        $this->assertEquals(json_encode($item), '"iVBORw0KGgoAAAANSUhEUgAAANwAAADcBAMAAADpdNKyAAAAA3NCSVQICAjb4U\/gAAAACXBIWXMAAAsSAAALEgHS3X78AAAAHHRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M1cbXjNgAAABZ0RVh0Q3JlYXRpb24gVGltZQAxMS8xOC8xMeLI7MIAAAAqUExURaysrJ+fn5aWlo+Pj5CQkISEhIKCgn5+fn19fXt7e3p6enl5eXh4eHd3d8OdjHwAAAANdFJOUzNATVlZgI2ms8DN2eY1xRzPAAAAhElEQVQYGe3BOw0CARAFwEcIFc3pwMMlSEAAFpCAARIk0FARKkyg4BI+xXrhVGwoZiYAAAAAAAAAAAAAAH9iMaTTapdO60c6jVM6nT9pszzdq+63bZoca\/ZOl1XNdmkzVk3pM1ZN6XOoeqXP5br5ps8z2afPkCwCAAAAAAAAAAAAAPAXfsHaHLCjNMAaAAAAAElFTkSuQmCC"');
    }

}
