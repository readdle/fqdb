<?php
require_once '../src/Readdle/Database/FQDB.php';
require_once '../src/Readdle/Database/FQDBException.php';

use Readdle\Database\FQDB;
use Readdle\Database\FQDBException;

class FQDBTest extends PHPUnit_Framework_TestCase {

    /**
     * @var FQDB $fqdb;
     */
    private $fqdb;

    public function setUp() {
        $this->fqdb = new FQDB('sqlite::memory:');
        $this->assertInstanceOf('\Readdle\Database\FQDB', $this->fqdb);
        $result = $this->fqdb->execute("CREATE TABLE test ( id INTEGER PRIMARY KEY ASC, content TEXT, data BLOB );");
        $this->assertTrue($result === 0);
        $this->fqdb->insert("INSERT INTO test (content, data) VALUES ('test', 'data')");
        $this->fqdb->insert("INSERT INTO test (content, data) VALUES ('test', 'data')");
    }

    public function testInsert() {
        $lastInsertId1 = $this->fqdb->insert("INSERT INTO test (content, data) VALUES ('test', :data)", [':data' => 'data']);
        $lastInsertId2 = $this->fqdb->insert("INSERT INTO test (content, data) VALUES ('test', :data)", [':data' => 'data']);

        $this->assertGreaterThan($lastInsertId1, $lastInsertId2);
    }


    public function testReplace() {
        $affected = $this->fqdb->replace("REPLACE INTO test (id, content, data) VALUES (1, 'test', 'data')");
        $this->assertEquals(1, $affected);
    }

    public function testDelete() {

        $this->fqdb->insert("INSERT INTO test(content, data) VALUES('delme', 'please')");
        $this->fqdb->insert("INSERT INTO test(content, data) VALUES('delme', 'please')");

        $count = $this->fqdb->delete("DELETE FROM test WHERE content=:content", [':content' => 'delme']);
        $this->assertEquals($count, 2);
    }

    public function testQueryValue() {
        $noValue = $this->fqdb->queryValue("SELECT content FROM test WHERE id=100");
        $this->assertEquals($noValue, false);

        $value = $this->fqdb->queryValue("SELECT id FROM test WHERE id=1");
        $this->assertEquals('1', $value);
    }

    public function testQueryList() {
        $noValues = $this->fqdb->queryList("SELECT * FROM test WHERE id=100");
        $this->assertFalse($noValues);

        $values = $this->fqdb->queryList("SELECT * FROM test WHERE id=1");
        $this->assertArrayHasKey(0, $values);
        $this->assertArrayHasKey(1, $values);
        $this->assertArrayHasKey(2, $values);

    }

    public function testQueryAssoc() {
        $noValues = $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=100");
        $this->assertFalse($noValues);


        $values = $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=1");
        $this->assertArrayHasKey('id', $values);
        $this->assertArrayHasKey('content', $values);
        $this->assertArrayHasKey('data', $values);
    }

    public function testQueryVector() {
        $noValues = $this->fqdb->queryVector("SELECT * FROM test WHERE id=100");
        $this->assertFalse($noValues);

        $count = intval($this->fqdb->queryValue("SELECT COUNT(*) FROM test"));
        $values = $this->fqdb->queryVector("SELECT * FROM test");
        $this->assertCount($count, $values);
        $this->assertInternalType('string', $values[0]);
    }

    public function testQueryTable() {
        $noValues = $this->fqdb->queryTable("SELECT * FROM test WHERE id=100");
        $this->assertFalse($noValues);

        $count = intval($this->fqdb->queryValue("SELECT COUNT(*) FROM test"));
        $values = $this->fqdb->queryTable("SELECT * FROM test");
        $this->assertCount($count, $values);
    }


    public function testQueryObj() {
        $noObject = $this->fqdb->queryObj("SELECT * FROM test WHERE id=100", '\QueryObject');
        $this->assertFalse($noObject);

        $object = $this->fqdb->queryObj("SELECT * FROM test WHERE id=1", '\QueryObject');
        $this->assertInstanceOf('\QueryObject', $object);
        $this->assertEquals(1, $object->id);
    }

    public function testQueryTableCallbackOk() {
        $noValues = $this->fqdb->queryTableCallback("SELECT * FROM test WHERE id=:id", [':id' => 1], function($row) {return $row;});
        $this->assertTrue($noValues);
    }

    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testQueryTableCallbackFail() {
        $noValues = $this->fqdb->queryTableCallback("SELECT * FROM test WHERE id=1", [], 'not a valid callback');
        $this->assertTrue($noValues);
    }

    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testQueryObjException() {
        $noObject = $this->fqdb->queryObj("SELECT * FROM test WHERE id=100", '\NoObject');
        return $noObject;
    }


    public function testQueryObjArray() {
        $noValues = $this->fqdb->queryObjArray("SELECT * FROM test WHERE id=100", '\QueryObject');
        $this->assertFalse($noValues);

        $count = intval($this->fqdb->queryValue("SELECT COUNT(*) FROM test"));
        $objects = $this->fqdb->queryObjArray("SELECT * FROM test", '\QueryObject');
        $this->assertCount($count, $objects);
        foreach($objects as $object)
            $this->assertInstanceOf('\QueryObject', $object);
    }


    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testQueryObjArrayException() {
        $noObject = $this->fqdb->queryObjArray("SELECT * FROM test", '\NoObject');
        return $noObject;
    }



    public function testUpdate() {
        $countInTable = intval($this->fqdb->queryValue("SELECT COUNT(*) FROM test"));
        $count = $this->fqdb->update("UPDATE test SET content=:new", [':new' => 'new']);
        $this->assertEquals($countInTable, $count);
    }

    public function testBeginRollbackTransaction() {
        $this->fqdb->beginTransaction();
        $this->fqdb->insert("INSERT INTO test(id, content, data) VALUES(100, 'test', 'data')");
        $this->fqdb->rollbackTransaction();

        $noValues = $this->fqdb->queryList("SELECT * FROM test WHERE id=100");
        $this->assertFalse($noValues);
    }

    public function testBeginCommitTransaction() {
        $this->fqdb->beginTransaction();
        $this->fqdb->insert("INSERT INTO test(id, content, data) VALUES(8, 'test', 'data')");
        $this->fqdb->commitTransaction();

        $eight = $this->fqdb->queryValue("SELECT id FROM test WHERE id=8");
        $this->assertEquals(8, $eight);
    }

    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testCommitException() {
        $this->fqdb->commitTransaction();
    }


    public function testPlaceholder()
    {
        $test = $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=:id", [':id' => 1]);
        $this->assertArrayHasKey('id', $test);


        $test = $this->fqdb->queryAssoc("SELECT :key1,:key2,:key3", [':key1' => 1, ':key2' => 2, ':key3' => 3]);
        $this->assertArrayHasKey(':key1', $test);
        $this->assertArrayHasKey(':key2', $test);
        $this->assertArrayHasKey(':key3', $test);
    }


    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testPlaceholderException1()
    {
        $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=:id");
    }


    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testPlaceholderException2()
    {
        $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=:id AND content=:content", [':id' => 1, 'content' => 2]);
    }


    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testPlaceholderException3()
    {
        $this->fqdb->queryAssoc("SELECT * FROM test WHERE id=:id AND content=:content AND data=:id",
                                 [':id' => 1, ':content' => 2, ':test' => 3]);
    }



    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testGeneralException() {
        $this->fqdb->insert("INSSSSERT!");
    }

    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testInsertException() {
        $this->fqdb->insert("UPDATE test SET content='new'");
    }

    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testQueryValueException() {
        $this->fqdb->queryValue("UPDATE test SET content='new'");
    }



    public function testGetPDO() {
        $this->assertInstanceOf('\PDO', $this->fqdb->getPdo());
    }

    public function testQuote() {
        $quoted = $this->fqdb->quote("'test'");
        $this->assertEquals("'''test'''", $quoted);
    }

    public function testBeforeUpdateHandler() {
        $sqlFromHandler = '';
        $optionsFromHandler = [];


        $handler = function($sql, $options) use (&$sqlFromHandler, &$optionsFromHandler) {
            $sqlFromHandler = $sql;
            $optionsFromHandler = $options;
            return $sqlFromHandler;
        };

        $this->fqdb->setBeforeUpdateHandler($handler);
        $this->assertEquals($handler, $this->fqdb->getBeforeUpdateHandler());
        $this->assertNull($this->fqdb->getBeforeDeleteHandler());
        $this->assertNull($this->fqdb->getErrorHandler());
        $this->assertNull($this->fqdb->getWarningHandler());


        $sql = "UPDATE test SET content='new' WHERE id=:id";
        $this->fqdb->update($sql, [':id' => 100]);

        $this->assertEquals($sql, $sqlFromHandler);
        $this->assertArrayHasKey(':id', $optionsFromHandler);
        $this->assertEquals(100, $optionsFromHandler[':id']);

        $this->fqdb->setBeforeDeleteHandler(null);
        $this->assertNull($this->fqdb->getBeforeDeleteHandler());

    }

    public function testBeforeDeleteHandler() {
        $sqlFromHandler = '';
        $optionsFromHandler = [];


        $handler = function($sql, $options) use (&$sqlFromHandler, &$optionsFromHandler) {
            $sqlFromHandler = $sql;
            $optionsFromHandler = $options;
            return $sqlFromHandler;
        };

        $this->fqdb->setBeforeDeleteHandler($handler);
        $this->assertEquals($handler, $this->fqdb->getBeforeDeleteHandler());
        $this->assertNull($this->fqdb->getBeforeUpdateHandler());
        $this->assertNull($this->fqdb->getErrorHandler());
        $this->assertNull($this->fqdb->getWarningHandler());

        $sql = "DELETE FROM test WHERE id=:id";
        $this->fqdb->delete($sql, [':id' => 100]);

        $this->assertEquals($sql, $sqlFromHandler);
        $this->assertArrayHasKey(':id', $optionsFromHandler);
        $this->assertEquals(100, $optionsFromHandler[':id']);

        $this->fqdb->setBeforeDeleteHandler(null);
        $this->assertNull($this->fqdb->getBeforeDeleteHandler());

    }

    /**
     * @expectedException \SpecialException
     */
    public function testErrorHandler() {

        $fqdb = $this->fqdb;

        $handler = function($msg, $code, $exception) use ($fqdb) {
            $fqdb->setErrorHandler(null);
            throw new \SpecialException($msg, $code, $exception);
        };

        $this->fqdb->setErrorHandler($handler);
        $this->assertEquals($handler, $this->fqdb->getErrorHandler());
        $this->assertNull($this->fqdb->getBeforeUpdateHandler());
        $this->assertNull($this->fqdb->getBeforeDeleteHandler());
        $this->assertNull($this->fqdb->getWarningHandler());

        $this->fqdb->queryValue("SELECT something");
    }



    public function testWarningHandler() {

        $warningText = false;

        $handler = function($msg) use (&$warningText) {
            $warningText = $msg;
        };

        $this->fqdb->setWarningHandler($handler);
        $this->assertEquals($handler, $this->fqdb->getWarningHandler());
        $this->assertNull($this->fqdb->getBeforeUpdateHandler());
        $this->assertNull($this->fqdb->getBeforeDeleteHandler());
        $this->assertNull($this->fqdb->getErrorHandler());

        $this->fqdb->setWarningReporting(true);

        //
        $four = $this->fqdb->queryValue("SELECT :num1+:num2", [':num1' => 2, ':num2' => 2]);
        // sqlite does not have warning reporting and has warning about this

        $this->assertEquals(4, $four); // just in case

        $this->assertContains('SELECT :num1+:num2', $warningText);
        $this->assertContains('WarningReporting not impl.', $warningText);

        $this->fqdb->setWarningHandler(null);
        $this->assertNull($this->fqdb->getWarningHandler());

        $this->fqdb->setWarningReporting(false);
        $this->assertFalse($this->fqdb->getWarningReporting());
    }


}


// for queryObject tests
class QueryObject {
    public $id, $test, $data;
}

class SpecialException extends Exception {

}