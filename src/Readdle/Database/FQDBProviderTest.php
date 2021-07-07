<?php declare(strict_types=1);

namespace Readdle\Database;

use Readdle\Database\FQDBProvider;

class FQDBProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testParseMyCnf(): void
    {
        $oldHome         = $_SERVER['HOME'];
        $_SERVER['HOME'] = __DIR__ . "/test-data/mycnf";
        
        $my = FQDBProvider::parseMyCnf();
        $this->assertEquals("username", $my['user']);
        $this->assertEquals("passw0rd", $my['password']);
        $this->assertEquals('', $my['database']);
        $this->assertEquals('localhost', $my['host']);
        
        $_SERVER['HOME'] = $oldHome;
    }
    
    public function testParseMyCnfWithDB(): void
    {
        $oldHome         = $_SERVER['HOME'];
        $_SERVER['HOME'] = __DIR__ . "/test-data/mycnf-with-db";
        
        $my = FQDBProvider::parseMyCnf();
        $this->assertEquals("username", $my['user']);
        $this->assertEquals("passw0rd", $my['password']);
        $this->assertEquals('database_name', $my['database']);
        $this->assertEquals('db.host', $my['host']);
        
        $_SERVER['HOME'] = $oldHome;
    }
    
    public function testSetDefault(): void
    {
        $fqdb = FQDBProvider::dbWithDSN('sqlite::memory:');
        FQDBProvider::setDefaultFQDB($fqdb);
        $this->assertEquals($fqdb, FQDBProvider::defaultFQDB());
        FQDBProvider::setDefaultFQDB(null);
    }
    
    public function testSetDefaultCallback(): void
    {
        $fqdb = FQDBProvider::dbWithDSN('sqlite::memory:');
        FQDBProvider::setDefaultFQDBCreator(
            function () use ($fqdb) {
                return $fqdb;
            }
        );
        $this->assertEquals($fqdb, FQDBProvider::defaultFQDB());
        
        FQDBProvider::setDefaultFQDBCreator(null);
    }
}
