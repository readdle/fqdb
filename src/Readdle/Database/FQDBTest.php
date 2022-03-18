<?php declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// phpcs:disable Squiz.Classes.ClassFileName.NoMatch

namespace Readdle\Database;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Readdle\Database\Event\DeleteQueryStarted;
use Readdle\Database\Event\TransactionCommitted;
use Readdle\Database\Event\TransactionRolledBack;
use Readdle\Database\Event\TransactionStarted;
use Readdle\Database\Event\UpdateQueryStarted;

final class FQDBTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;
    
    private FQDB $fqdb;
    /** @var \Prophecy\Prophecy\ObjectProphecy|EventDispatcherInterface */
    private \Prophecy\Prophecy\ObjectProphecy $dispatcher;
    
    protected function setUp(): void
    {
        $this->fqdb = \Readdle\Database\FQDBProvider::dbWithDSN('sqlite::memory:');
        $this->assertInstanceOf('\Readdle\Database\FQDB', $this->fqdb);
        $result = $this->fqdb->execute("CREATE TABLE test ( id INTEGER PRIMARY KEY ASC, content TEXT, data BLOB );");
        $this->assertTrue(0 === $result);
        $this->fqdb->insert("INSERT INTO test (content, data) VALUES ('test', 'data')");
        $this->fqdb->insert("INSERT INTO test (content, data) VALUES ('test', 'data')");
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
    }
    
    public function testInsert(): void
    {
        $lastInsertId1 = $this->fqdb->insert(
            "INSERT INTO test (content, data) VALUES ('test', :data)",
            [':data' => 'data']
        );
        $lastInsertId2 = $this->fqdb->insert(
            "INSERT INTO test (content, data) VALUES ('test', :data)",
            [':data' => 'data']
        );
        
        $this->assertGreaterThan($lastInsertId1, $lastInsertId2);
        $this->assertIsNumeric($lastInsertId1);
        $this->assertIsNumeric($lastInsertId2);
    }
    
    
    public function testReplace(): void
    {
        $affected = $this->fqdb->replace("REPLACE INTO test (id, content, data) VALUES (1, 'test', 'data')");
        $this->assertEquals(1, $affected);
    }
    
    public function testDelete(): void
    {
        $this->dispatcher
            ->dispatch(Argument::type(DeleteQueryStarted::class))
            ->shouldBeCalledOnce()
        ;
        $this->fqdb->setEventDispatcher($this->dispatcher->reveal());
        
        $this->fqdb->insert("INSERT INTO test(content, data) VALUES('delme', 'please')");
        $this->fqdb->insert("INSERT INTO test(content, data) VALUES('delme', 'please')");
        
        $count = $this->fqdb->delete("DELETE FROM test WHERE content=:content", [':content' => 'delme']);
        $this->assertEquals($count, 2);
    }
    
    public function testQueryValue(): void
    {
        $noValue = $this->fqdb->queryValue("SELECT content FROM test WHERE id=100");
        $this->assertEquals($noValue, false);
        
        $value = $this->fqdb->queryValue("SELECT id FROM test WHERE id=1");
        $this->assertEquals('1', $value);
        
        $value = $this->fqdb->queryValue("SELECT * FROM test WHERE id=1");
        $this->assertEquals('1', $value);
        
        $value = $this->fqdb->queryValue("SELECT data, content FROM test WHERE id=1");
        $this->assertEquals('data', $value);
    }
    
    public function testQueryList(): void
    {
        $noValues = $this->fqdb->queryList("SELECT * FROM test WHERE id=100");
        $this->assertFalse($noValues);
        
        $values = $this->fqdb->queryList("SELECT * FROM test WHERE id=1");
        $this->assertArrayHasKey(0, $values);
        $this->assertArrayHasKey(1, $values);
        $this->assertArrayHasKey(2, $values);
    }
    
    public function testQueryAssoc(): void
    {
        $noValues = $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=100");
        $this->assertFalse($noValues);
        
        
        $values = $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=1");
        $this->assertArrayHasKey('id', $values);
        $this->assertArrayHasKey('content', $values);
        $this->assertArrayHasKey('data', $values);
    }
    
    public function testQueryVector(): void
    {
        $noValues = $this->fqdb->queryVector("SELECT * FROM test WHERE id=100");
        $this->assertCount(0, $noValues);
        
        $count  = \intval($this->fqdb->queryValue("SELECT COUNT(*) FROM test"));
        $values = $this->fqdb->queryVector("SELECT cast(id as text) FROM test");
        $this->assertCount($count, $values);
        $this->assertIsString($values[0]);
    }
    
    public function testQueryTable(): void
    {
        $noValues = $this->fqdb->queryTable("SELECT * FROM test WHERE id=100");
        $this->assertCount(0, $noValues);
        
        $count  = \intval($this->fqdb->queryValue("SELECT COUNT(*) FROM test"));
        $values = $this->fqdb->queryTable("SELECT * FROM test");
        $this->assertCount($count, $values);
    }
    
    
    public function testQueryObj(): void
    {
        $noObject = $this->fqdb->queryObj("SELECT * FROM test WHERE id=100", QueryObject::class);
        $this->assertFalse($noObject);
        
        $object = $this->fqdb->queryObj("SELECT * FROM test WHERE id=1", QueryObject::class);
        $this->assertInstanceOf(QueryObject::class, $object);
        $this->assertEquals(1, $object->id);
    }
    
    public function testQueryTableCallbackOk(): void
    {
        $sql        = "SELECT * FROM test";
        $sqlOptions = [];
        
        $callbackResultArray = [];
        $this->fqdb->queryTableCallback(
            $sql,
            $sqlOptions,
            function ($row) use (&$callbackResultArray) {
                $callbackResultArray[] = $row;
                return $row;
            }
        );
        
        $resultArray = $this->fqdb->queryTable($sql, $sqlOptions);
        $this->assertEquals($resultArray, $callbackResultArray);
    }
    
    public function testQueryObjException(): void
    {
        $this->expectException(\Readdle\Database\FQDBException::class);
        $this->fqdb->queryObj("SELECT * FROM test WHERE id=100", '\NoObject');
    }
    
    public function testQueryObjArray(): void
    {
        $noValues = $this->fqdb->queryObjArray("SELECT * FROM test WHERE id=100", QueryObject::class);
        $this->assertCount(0, $noValues);
        
        $count   = \intval($this->fqdb->queryValue("SELECT COUNT(*) FROM test"));
        $objects = $this->fqdb->queryObjArray("SELECT * FROM test", QueryObject::class);
        $this->assertCount($count, $objects);
        foreach ($objects as $object) {
            $this->assertInstanceOf(QueryObject::class, $object);
        }
    }
    
    public function testQueryObjArrayException(): void
    {
        $this->expectException(\Readdle\Database\FQDBException::class);
        $this->fqdb->queryObjArray("SELECT * FROM test", '\NoObject');
    }
    
    public function testUpdate(): void
    {
        $this->dispatcher
            ->dispatch(Argument::type(UpdateQueryStarted::class))
            ->shouldBeCalledOnce()
        ;
        $this->fqdb->setEventDispatcher($this->dispatcher->reveal());
        
        $countInTable = \intval($this->fqdb->queryValue("SELECT COUNT(*) FROM test"));
        $count        = $this->fqdb->update("UPDATE test SET content=:new", [':new' => 'new']);
        $this->assertEquals($countInTable, $count);
    }
    
    public function testBeginRollbackTransaction(): void
    {
        $this->dispatcher
            ->dispatch(Argument::type(TransactionStarted::class))
            ->shouldBeCalledOnce()
        ;
        $this->dispatcher
            ->dispatch(Argument::type(TransactionRolledBack::class))
            ->shouldBeCalledOnce()
        ;
        
        $this->fqdb->setEventDispatcher($this->dispatcher->reveal());
        
        $this->fqdb->beginTransaction();
        $this->fqdb->insert("INSERT INTO test(id, content, data) VALUES(100, 'test', 'data')");
        $this->fqdb->rollbackTransaction();
        
        $noValues = $this->fqdb->queryList("SELECT * FROM test WHERE id=100");
        $this->assertFalse($noValues);
    }
    
    public function testBeginCommitTransaction(): void
    {
        $this->dispatcher
            ->dispatch(Argument::type(TransactionStarted::class))
            ->shouldBeCalledOnce()
        ;
        $this->dispatcher
            ->dispatch(Argument::type(TransactionCommitted::class))
            ->shouldBeCalledOnce()
        ;
        $this->fqdb->setEventDispatcher($this->dispatcher->reveal());
        
        $this->fqdb->beginTransaction();
        $this->fqdb->insert("INSERT INTO test(id, content, data) VALUES(8, 'test', 'data')");
        $this->fqdb->commitTransaction();
        
        $eight = $this->fqdb->queryValue("SELECT id FROM test WHERE id=8");
        $this->assertEquals(8, $eight);
    }
    
    public function testCommitException(): void
    {
        $this->expectException(\Readdle\Database\FQDBException::class);
        $this->fqdb->commitTransaction();
    }
    
    
    public function testPlaceholder(): void
    {
        $test = $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=:id", [':id' => 1]);
        $this->assertArrayHasKey('id', $test);
        
        
        $test = $this->fqdb->queryAssoc("SELECT :key1,:key2,:key3", [':key1' => 1, ':key2' => 2, ':key3' => 3]);
        $this->assertArrayHasKey(':key1', $test);
        $this->assertArrayHasKey(':key2', $test);
        $this->assertArrayHasKey(':key3', $test);
    }
    
    public function testPlaceholderException1(): void
    {
        $this->expectException(\Readdle\Database\FQDBException::class);
        
        $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=:id");
    }
    
    public function testPlaceholderException2(): void
    {
        $this->expectException(\Readdle\Database\FQDBException::class);
        
        $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=:id AND content=:content", [':id' => 1, 'content' => 2]);
    }
    
    public function testPlaceholderException3(): void
    {
        $this->expectException(\Readdle\Database\FQDBException::class);
        
        $this->fqdb->queryAssoc(
            "SELECT * FROM test WHERE id=:id AND content=:content AND data=:id",
            [':id' => 1, ':content' => 2, ':test' => 3]
        );
    }
    
    public function testGeneralException(): void
    {
        $this->expectException(\Readdle\Database\FQDBException::class);
        
        $this->fqdb->insert("INSSSSERT!");
    }
    
    public function testInsertException(): void
    {
        $this->expectException(\Readdle\Database\FQDBException::class);
        
        $this->fqdb->insert("UPDATE test SET content='new'");
    }
    
    public function testQueryValueException(): void
    {
        $this->expectException(\Readdle\Database\FQDBException::class);
        
        $this->fqdb->queryValue("UPDATE test SET content='new'");
    }
    
    
    public function testGetPDO(): void
    {
        $this->assertInstanceOf('\PDO', $this->fqdb->getPdo());
    }
    
    public function testQuote(): void
    {
        $quoted = $this->fqdb->quote("'test'");
        $this->assertEquals("'''test'''", $quoted);
    }
    
    public function testQuoteIdentifier(): void
    {
        $quoted = $this->fqdb->quote("test", FQDB::QUOTE_IDENTIFIER);
        $this->assertEquals("`test`", $quoted);
    }
    
    public function testErrorHandler(): void
    {
        $this->expectException(SpecialException::class);
        
        $fqdb = $this->fqdb;
        
        $handler = function (FQDBException $error) use ($fqdb) {
            $fqdb->setErrorHandler(null);
            throw new SpecialException($error->getMessage(), $error->getCode(), $error);
        };
        
        $this->fqdb->setErrorHandler($handler);
        $this->assertEquals($handler, $this->fqdb->getErrorHandler());
        $this->assertNull($this->fqdb->getWarningHandler());
        
        $this->fqdb->queryValue("SELECT something");
    }
    
    
    public function testWarningHandler(): void
    {
        
        $warningText = false;
        
        $handler = function ($msg) use (&$warningText) {
            $warningText = $msg;
        };
        
        $this->fqdb->setWarningHandler($handler);
        $this->assertEquals($handler, $this->fqdb->getWarningHandler());
        $this->assertNull($this->fqdb->getErrorHandler());
        
        $this->fqdb->setWarningReporting(true);
        
        //
        $four = $this->fqdb->queryValue("SELECT :num1+:num2", [':num1' => 2, ':num2' => 2]);
        // sqlite does not have warning reporting and has warning about this
        
        $this->assertEquals(4, $four); // just in case
        
        $this->assertStringContainsString('SELECT :num1+:num2', $warningText);
        $this->assertStringContainsString('WarningReporting not impl.', $warningText);
        
        $this->fqdb->setWarningHandler(null);
        $this->assertNull($this->fqdb->getWarningHandler());
        
        $this->fqdb->setWarningReporting(false);
        $this->assertFalse($this->fqdb->getWarningReporting());
    }
    
    public function testWhereInStatement(): void
    {
        $this->fqdb->insert(
            "INSERT INTO test (id, content, data) VALUES (1000, 'where_in_test', :data)",
            [':data' => 'data']
        );
        $this->fqdb->insert(
            "INSERT INTO test (id, content, data) VALUES (1001, 'where_in_test', :data)",
            [':data' => 'data']
        );
        $this->fqdb->insert(
            "INSERT INTO test (id, content, data) VALUES (1002, 'where_in_test', :data)",
            [':data' => 'data']
        );
        $this->fqdb->insert(
            "INSERT INTO test (id, content, data) VALUES (1003, 'where_in_test', :data)",
            [':data' => 'data']
        );
        $this->fqdb->insert(
            "INSERT INTO test (id, content, data) VALUES (1004, 'where_in_test', :data)",
            [':data' => 'data']
        );
        
        $result = $this->fqdb->queryTable(
            "SELECT * FROM test WHERE id IN (:idArray)",
            [':idArray' => new \Readdle\Database\SQLArgs(1000, 1001, 1002, 1003, 1004)]
        );
        
        $this->assertEquals(5, \count($result));
        
        $result = $this->fqdb->queryTable(
            "SELECT * FROM test WHERE id IN (:idArray)",
            [':idArray' => new \Readdle\Database\SQLArgsArray([1000, 1001, 1002, 1003])]
        );
        
        $this->assertEquals(4, \count($result));
        
        $this->fqdb->insert(
            "INSERT INTO test (id, content, data) VALUES (NULL, 'where_in_test', :data)",
            [':data' => 'data']
        );
        $this->fqdb->insert(
            "INSERT INTO test (id, content, data) VALUES (NULL, 'where_in_test', :data)",
            [':data' => 'data']
        );
        
        
        $result = $this->fqdb->queryTable(
            "SELECT * FROM test WHERE id IN (:idArray)",
            [':idArray' => new \Readdle\Database\SQLArgs(null)]
        );
        
        $this->assertEquals(0, \count($result));
    }
    
    public function testBlobInsert(): void
    {
        $blobData = \chr(7) . 'test' . \chr(0);
        
        $this->fqdb->insert(
            "INSERT INTO test (id, content, data) VALUES (1000, 'where_in_test', :data)",
            [':data' => new \Readdle\Database\SQLValueBlob($blobData)]
        );
        
        $blob = $this->fqdb->queryValue("SELECT `data` FROM test WHERE id=1000");
        
        $this->assertEquals($blobData, $blob);
    }
    
    public function testTrimmedQuery(): void
    {
        $blobData = \chr(7) . 'test' . \chr(0);
        
        $rowId = 10001;
        
        $this->fqdb->insert(
            "INSERT INTO test (id, content, data) VALUES (:rowId, 'test_trimmed_query', :data)",
            [
                ':data'  => new \Readdle\Database\SQLValueBlob($blobData),
                ':rowId' => $rowId,
            ]
        );
        
        $blob = $this->fqdb->queryValue(
            "SELECT `data` FROM test WHERE id=:rowId",
            [':rowId' => $rowId,]
        );
        
        $this->assertEquals($blobData, $blob);
        
        $blob = $this->fqdb->queryValue(
            " SELECT `data` FROM test WHERE id=:rowId",
            [':rowId' => $rowId,]
        );
        
        $this->assertEquals($blobData, $blob);
        
        $blob = $this->fqdb->queryValue(
            "\tSELECT `data` FROM test WHERE id=:rowId",
            [':rowId' => $rowId,]
        );
        
        $this->assertEquals($blobData, $blob);
        
        $blob = $this->fqdb->queryValue(
            "\nSELECT `data` FROM test WHERE id=:rowId",
            [':rowId' => $rowId,]
        );
        
        $this->assertEquals($blobData, $blob);
        
        $blob = $this->fqdb->queryValue(
            "\rSELECT `data` FROM test WHERE id=:rowId",
            [':rowId' => $rowId,]
        );
        
        $this->assertEquals($blobData, $blob);
    }
    
    public function testQueryHash(): void
    {
        $this->fqdb->execute(
            "CREATE TABLE `test_hash` (
              id INTEGER PRIMARY KEY ASC,
              field1 TEXT,
              field2 TEXT,
              field3 TEXT
            );"
        );
        $this->fqdb->insert("INSERT INTO test_hash (field1, field2, field3) VALUES ('first1', 'first2', 'first3')");
        $this->fqdb->insert("INSERT INTO test_hash (field1, field2, field3) VALUES ('second1', 'second2', 'second3')");
        
        $hash12 = $this->fqdb->queryHash("SELECT field1,field2 FROM test_hash");
        $this->assertEquals(2, \count($hash12));
        $this->assertArrayHasKey('first1', $hash12);
        $this->assertArrayHasKey('second1', $hash12);
        $this->assertEquals($hash12['first1'], 'first2');
        $this->assertEquals($hash12['second1'], 'second2');
        
        $hash23 = $this->fqdb->queryHash("SELECT field2,field3 FROM test_hash");
        $this->assertEquals(2, \count($hash23));
        $this->assertArrayHasKey('first2', $hash23);
        $this->assertArrayHasKey('second2', $hash23);
        $this->assertEquals($hash23['first2'], 'first3');
        $this->assertEquals($hash23['second2'], 'second3');
    }
    
    public function testQueryTableGenerator(): void
    {
        $this->fqdb->execute(
            "CREATE TABLE `test_querygenerator` (
              id INTEGER PRIMARY KEY ASC,
              somevalue VARCHAR (255)
            );"
        );
        $values = ['first', 'second'];
        foreach ($values as $value) {
            $this->fqdb->insert("INSERT INTO test_querygenerator (somevalue) VALUES (:val)", [':val' => $value]);
        }
        
        $generator = $this->fqdb->queryTableGenerator("SELECT * FROM test_querygenerator");
        $this->assertInstanceOf('\Generator', $generator);
        $result = [];
        foreach ($generator as $idx => $row) {
            $this->assertIsArray($row);
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('somevalue', $row);
            $result[] = $row['somevalue'];
        }
        $this->assertEquals($values, $result);
    }
}


// for queryObject tests
class QueryObject
{
    public int $id;
    public string $test;
    public string $data;
}

class SpecialException extends \Exception
{

}
