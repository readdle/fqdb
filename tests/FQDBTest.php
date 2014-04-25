<?php
use Readdle\Database\FQDB;
use Readdle\Database\FQDBException;
use Readdle\Database\FQDBProvider;


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
        $lastInsertId1 = $this->fqdb->insert("INSERT INTO test (content, data) VALUES ('test', 'data')");
        $lastInsertId2 = $this->fqdb->insert("INSERT INTO test (content, data) VALUES ('test', 'data')");
        $this->assertGreaterThan($lastInsertId1, $lastInsertId2);
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

    public function testQueryTable() {
        $noValues = $this->fqdb->queryTable("SELECT * FROM test WHERE id=100");
        $this->assertFalse($noValues);

        $count = intval($this->fqdb->queryValue("SELECT COUNT(*) FROM test"));
        $values = $this->fqdb->queryTable("SELECT * FROM test");
        $this->assertCount($count, $values);
    }

    public function testUpdate() {
        $countInTable = intval($this->fqdb->queryValue("SELECT COUNT(*) FROM test"));
        $count = $this->fqdb->update("UPDATE test SET content='new'");
        $this->assertEquals($countInTable, $count);
    }

    /**
     * @expectedException \Readdle\Database\FQDBException
     */
    public function testInsertException() {
        $this->fqdb->insert("UPDATE test SET content='new'");
    }




//    public function testBasicFunctionality() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $this->assertInstanceOf('Readdle\Database\FQDB', $fqdb);
//    }
//
//    /**
//     * deprecated
//     *
//     * @expectedException \Readdle\Database\FQDBException
//     * @depends testBasicFunctionality
//
//    public function testBasicFunctionalityFail() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $fqdb->queryValue("SELECT `udid` FROM `devices` WHERE `id`=:id", [':idd' => 3]);
//    }
//    */
//    /**
//     * @depends testBasicFunctionality
//     */
//    public function testQueryValue() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $res = $fqdb->queryValue("SELECT `udid` FROM `devices` WHERE `id`=:id", [':id' => 3]);
//        $this->assertEquals($res, 'RID3-333333333');
//
//        $res = $fqdb->queryValue("SELECT `udid` FROM `devices` WHERE `id`=:id", [':id' => 31]);
//        $this->assertEquals($res, false);
//    }
//
//    /**
//     * @depends testBasicFunctionality
//     */
//    public function testQueryAssoc() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $res = $fqdb->queryAssoc("SELECT `udid`, `user_id` FROM `devices` WHERE `id`=:id", [':id' => 3]);
//        $this->assertTrue(is_array($res));
//        $this->assertEquals(count($res), 2);
//        $this->assertArrayHasKey('udid', $res);
//        $this->assertArrayHasKey('user_id', $res);
//
//        $res = $fqdb->queryAssoc("SELECT `udid` FROM `devices` WHERE `id`=:id", [':id' => 31]);
//        $this->assertEquals($res, false);
//    }
//
//    /**
//     * @depends testBasicFunctionality
//     */
//    public function testQueryList() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $res = $fqdb->queryList("SELECT `udid` FROM `devices` WHERE `id` = :id", [':id' => 6]);
//        $this->assertTrue(is_array($res));
//        $this->assertEquals(count($res), 1);
//        $this->assertArrayHasKey(0, $res); //non-assoc array
//
//        $res = $fqdb->queryList("SELECT `udid` FROM `devices` WHERE `id` = :id", [':id' => 31]);
//        $this->assertEquals($res, false);
//    }
//
//    /**
//     * @depends testBasicFunctionality
//     */
//    public function testQueryVector() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $res = $fqdb->queryVector("SELECT `udid` FROM `devices` WHERE `id` > :id", [':id' => 6]);
//        $this->assertTrue(is_array($res));
//        $this->assertEquals(count($res), 3);
//        $this->assertEquals($res[2], 'RID3-999999999');
//
//        $res = $fqdb->queryVector("SELECT `udid` FROM `devices` WHERE `id`> :id", [':id' => 31]);
//        $this->assertEquals($res, false);
//    }
//
//    /**
//     * @depends testBasicFunctionality
//     */
//    public function testQueryTable() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $res = $fqdb->queryTable("SELECT `udid` FROM `devices` WHERE `id` > :id", [':id' => 6]);
//        $this->assertTrue(is_array($res));
//        $this->assertEquals(count($res), 3);
//        $this->assertArrayHasKey('udid', $res[2]);
//
//        $res = $fqdb->queryTable("SELECT `udid` FROM `devices` WHERE `id`> :id", [':id' => 31]);
//        $this->assertEquals($res, false);
//    }
//
//    /**
//     *  QueryObj() and QueryObjArray() Functions deprecated
//     *
//     * @depends testBasicFunctionality
//     *
//     */
//    public function testQueryObjArray() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $res = $fqdb->queryObjArray("SELECT * FROM `devices` WHERE `id` > :id", '\Readdle\Enterprise\Entity\Device' ,[':id' => 6]);
//        $this->assertTrue(is_array($res));
//        $this->assertEquals(count($res), 3);
//        $this->assertInstanceOf('\Readdle\Enterprise\Entity\Device', $res[2]);
//
//        $res = $fqdb->queryObjArray("SELECT * FROM `devices` WHERE `id`> :id", '\Readdle\Enterprise\Entity\Device', [':id' => 31]);
//        $this->assertEquals($res, false);
//    }
//
//    /**
//     * @depends testBasicFunctionality
//     */
//    public function testQueryObj() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $res = $fqdb->queryObj("SELECT * FROM `devices` WHERE `id` = :id", '\Readdle\Enterprise\Entity\Device' , [':id' => 6]);
//        $this->assertInstanceOf('\Readdle\Enterprise\Entity\Device', $res);
//        $this->assertEquals($res->id, 6);
//
//        $res = $fqdb->queryObj("SELECT * FROM `devices` WHERE `id` = :id", '\Readdle\Enterprise\Entity\Device' , [':id' => 31]);
//        $this->assertEquals($res, false);
//    }
//
//    public function testDelete() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $res = $fqdb->delete("DELETE FROM `devices` WHERE `id` > :id", [':id' => 5]);
//        $this->assertEquals($res, 4); //4 rows affected
//
//        $res = $fqdb->queryObj("SELECT * FROM `devices` WHERE `id` = :id", '\Readdle\Enterprise\Entity\Device' , [':id' => 7]);
//        $this->assertEquals($res, false);
//
//        $res = $fqdb->delete("DELETE FROM `devices` WHERE `id` > :id", [':id' => 6]);
//        $this->assertEquals($res, 0); //0 rows affected
//    }
//
//        public function testUpdate() {
//        $fqdb = Db\DBManager::getInstance()->getEnterpriseDb();
//        $res = $fqdb->update("UPDATE `devices` SET `udid` = 'TEST-12345' WHERE `id` < :id", [':id' => 5]);
//        $this->assertEquals($res, 4); //4 rows affected
//
//        $res = $fqdb->update("UPDATE `devices` SET `udid` = 'TEST-12345' WHERE `id` < :id", [':id' => 5]);
//        $this->assertEquals($res, 0); //0 rows affected
//
//        $res = $fqdb->queryTable("SELECT * FROM `devices` WHERE `udid` = :udid", [':udid' => 'TEST-12345']);
//        $this->assertTrue(is_array($res));
//        $this->assertEquals(count($res), 4); //4 rows found
//    }
}
