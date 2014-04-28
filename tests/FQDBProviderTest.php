<?php
use Readdle\Database\FQDB;
use Readdle\Database\FQDBProvider;

class FQDBProviderTest extends PHPUnit_Framework_TestCase {

    function testParseMyCnf() {
        $oldHome = $_SERVER['HOME'];
        $_SERVER['HOME'] = __DIR__;

        $my = FQDBProvider::parseMyCnf("test");
        $this->assertEquals("username", $my['user']);
        $this->assertEquals("passw0rd", $my['password']);
        $this->assertEquals('', $my['database']);

        $_SERVER['HOME'] = $oldHome;
    }


    function testSetDefault() {
        $fqdb = new FQDB('sqlite::memory:');
        FQDBProvider::setDefaultFQDB($fqdb);
        $this->assertEquals($fqdb, FQDBProvider::defaultFQDB());
        FQDBProvider::setDefaultFQDB(null);
    }


    function testSetDefaultCallback() {
        $fqdb = new FQDB('sqlite::memory:');
        FQDBProvider::setDefaultFQDBCreator(function() use ($fqdb) {
            return $fqdb;
        });
        $this->assertEquals($fqdb, FQDBProvider::defaultFQDB());

        FQDBProvider::setDefaultFQDBCreator(null);
    }

}