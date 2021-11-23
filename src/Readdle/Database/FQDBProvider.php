<?php declare(strict_types=1);

namespace Readdle\Database;

use Closure;

final class FQDBProvider
{
    private static ?FQDB $defaultFQDB            = null;
    private static ?Closure $FQDBCreatorCallback = null;
    
    /**
     * @return array - parsed key-value array of ~/.my.cnf
     * @throws FQDBException if there is no file or unable to find HOME dir
     */
    public static function parseMyCnf(): array
    {
        if (!isset($_SERVER['HOME'])) {
            throw FQDBException::assertion("unable to find home dir");
        }
        
        $my_cnf_path = $_SERVER['HOME'] . '/.my.cnf';
        
        if (!\file_exists($my_cnf_path)) {
            throw FQDBException::assertion("unable to find {$my_cnf_path}");
        }
        
        $lines    = \file($my_cnf_path);
        $database = $user = $password = "";
        $host     = "localhost";
        
        foreach ($lines as $line) {
            $kv = \explode("=", $line);
            
            if (2 != \count($kv)) {
                continue;
            }
            
            $kv = \array_map('trim', $kv);
            
            if (0 === \strpos($kv[0], 'pass')) {
                $password = $kv[1];
            }
            
            if ('user' === $kv[0]) {
                $user = $kv[1];
            }
            
            if ('database' === $kv[0]) {
                $database = $kv[1];
            }
            
            if ('host' === $kv[0]) {
                $host = $kv[1];
            }
        }
        
        if ("" == $user) {
            throw FQDBException::assertion("unable to find user in .my.cnf");
        }
        
        return \compact('user', 'password', 'database', 'host');
    }
    
    public static function dbWithMyCnf(string $database = ''): FQDB
    {
        $my = static::parseMyCnf();
        
        if ('' != $database) {
            $my['database'] = $database;
        }
        
        if ('' == $my['database']) {
            throw FQDBException::assertion("No database specified in config or argument");
        }
        
        return self::dbWithDSN(
            "mysql:host={$my['host']};dbname={$my['database']};charset=utf8mb4",
            $my['user'],
            $my['password']
        );
    }
    
    public static function dbWithMySQLHostUserPasswordDatabase(string $host, string $user, string $password, string $database): FQDB
    {
        return self::dbWithDSN("mysql:host={$host};dbname={$database};charset=utf8mb4", $user, $password);
    }
    
    public static function dbWithDSN(string $dsn, string $user = '', string $password = ''): FQDB
    {
        return new FQDB($dsn, $user, $password);
    }
    
    public static function setDefaultFQDB(?FQDB $fqdb = null): ?FQDB
    {
        self::$defaultFQDB = $fqdb;
        return $fqdb;
    }
    
    
    /**
     * @param $callback - callable that creates default FQDB instance
     */
    public static function setDefaultFQDBCreator(?callable $callback = null): void
    {
        self::$FQDBCreatorCallback = \is_callable($callback) ? Closure::fromCallable($callback) : null;
    }
    
    public static function defaultFQDB(): FQDB
    {
        if (self::$defaultFQDB) {
            return self::$defaultFQDB;
        }
        
        if (null != self::$FQDBCreatorCallback) {
            self::$defaultFQDB = (self::$FQDBCreatorCallback)();
            return self::$defaultFQDB;
        }
        
        throw FQDBException::assertion("FQDB Creator should be specified or manually injected with setDefaultFQDB");
    }
}
