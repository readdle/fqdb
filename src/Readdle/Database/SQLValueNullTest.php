<?php declare(strict_types=1);

namespace Readdle\Database;

final class SQLValueNullTest extends \PHPUnit\Framework\TestCase
{
    public function testSQLValueNull(): void
    {
        $item = new \Readdle\Database\SQLValueNull();
        $this->assertEquals(\json_encode($item), 'null');
    }
}
