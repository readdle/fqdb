<?php

use Readdle\Database\FQDBProvider;

class FQDBProviderTest extends PHPUnit_Framework_TestCase {

    function testParseMyCnf() {
        $oldHome = $_SERVER['HOME'];
        $_SERVER['HOME'] = __DIR__ . "/mycnf";

        $my = FQDBProvider::parseMyCnf();
        $this->assertEquals("username", $my['user']);
        $this->assertEquals("passw0rd", $my['password']);
        $this->assertEquals('', $my['database']);
        $this->assertEquals('localhost', $my['host']);

        $_SERVER['HOME'] = $oldHome;
    }

    function testParseMyCnfWithDB() {
        $oldHome = $_SERVER['HOME'];
        $_SERVER['HOME'] = __DIR__ . "/mycnf-with-db";

        $my = FQDBProvider::parseMyCnf();
        $this->assertEquals("username", $my['user']);
        $this->assertEquals("passw0rd", $my['password']);
        $this->assertEquals('database_name', $my['database']);
        $this->assertEquals('db.host', $my['host']);

        $_SERVER['HOME'] = $oldHome;
    }


    function testSetDefault() {
        $fqdb = FQDBProvider::dbWithDSN('sqlite::memory:');
        FQDBProvider::setDefaultFQDB($fqdb);
        $this->assertEquals($fqdb, FQDBProvider::defaultFQDB());
        FQDBProvider::setDefaultFQDB(null);
    }


    function testSetDefaultCallback() {
        $fqdb = FQDBProvider::dbWithDSN('sqlite::memory:');
        FQDBProvider::setDefaultFQDBCreator(function() use ($fqdb) {
            return $fqdb;
        });
        $this->assertEquals($fqdb, FQDBProvider::defaultFQDB());

        FQDBProvider::setDefaultFQDBCreator(null);
    }
}
