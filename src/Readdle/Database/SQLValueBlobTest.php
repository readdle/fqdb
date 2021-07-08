<?php declare(strict_types=1);

namespace Readdle\Database;

final class SQLValueBlobTest extends \PHPUnit\Framework\TestCase
{
    public function testSQLValueBlob(): void
    {
        $content = \file_get_contents(__DIR__ . '/test-data/blob');
        $item    = new \Readdle\Database\SQLValueBlob($content);
        $this->assertEquals(
            \json_encode($item),
            '"iVBORw0KGgoAAAANSUhEUgAAANwAAADcBAMAAADpdNKyAAAAA3NCSVQICAjb4U\/gAAAACXBIWXMAAAsSAAALEgHS3X78AAAAHHRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M1cbXjNgAAABZ0RVh0Q3JlYXRpb24gVGltZQAxMS8xOC8xMeLI7MIAAAAqUExURaysrJ+fn5aWlo+Pj5CQkISEhIKCgn5+fn19fXt7e3p6enl5eXh4eHd3d8OdjHwAAAANdFJOUzNATVlZgI2ms8DN2eY1xRzPAAAAhElEQVQYGe3BOw0CARAFwEcIFc3pwMMlSEAAFpCAARIk0FARKkyg4BI+xXrhVGwoZiYAAAAAAAAAAAAAAH9iMaTTapdO60c6jVM6nT9pszzdq+63bZoca\/ZOl1XNdmkzVk3pM1ZN6XOoeqXP5br5ps8z2afPkCwCAAAAAAAAAAAAAPAXfsHaHLCjNMAaAAAAAElFTkSuQmCC"'
        );
    }
}
