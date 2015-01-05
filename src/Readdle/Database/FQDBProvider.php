<?php
namespace Readdle\Database;

class FQDBProvider {

    protected static $defaultFQDB;
    protected static $FQDBCreatorCallback;
    protected static $providerMode;


    /**
     * @return array - parsed key-value array of ~/.my.cnf
     * @throws FQDBException if there is no file or unable to find HOME dir
     */
    public static function parseMyCnf() {

        if (!isset($_SERVER['HOME']))
            throw new FQDBException("unable to find home dir", FQDBException::FQDB_PROVIDER_CODE);

        $my_cnf_path = $_SERVER['HOME'].'/.my.cnf';

        if (!file_exists($my_cnf_path))
            throw new FQDBException("unable to find {$my_cnf_path}", FQDBException::FQDB_PROVIDER_CODE);

        $lines = file($my_cnf_path);
        $database = $user = $password = "";

        foreach($lines as $line) {
            $kv = explode("=", $line);
            if (count($kv) != 2)
                continue;
            $kv = array_map('trim', $kv);

            if ($kv[0] == 'user')
                $user = $kv[1];

            if (strpos($kv[0], 'pass') == 0)
                $password = $kv[1];

            if ($kv == 'database')
                $database = $kv[1];
        }

        if ($user == "")
            throw new FQDBException("unable to find user in .my.cnf", FQDBException::FQDB_PROVIDER_CODE);


        return compact('user', 'password', 'database');

    }


    /**
     * @param string $database database to override (or set) instead of ~/my.cnf
     * @return FQDB instance
     * @throws FQDBException if there is no database
     */
    public static function dbWithMyCnf($database='') {
        $my = static::parseMyCnf();
        if ($database != '')
            $my['database'] = $database;

        if ($my['database'] == '')
            throw new FQDBException("no database specified in config or argument", FQDBException::FQDB_PROVIDER_CODE);

        return new FQDB("mysql:host=localhost;dbname={$my['database']};charset=utf8mb4", $my['user'], $my['password']);
    }


    /**
     * @param $host MySQL Host
     * @param $user MySQL User
     * @param $password MySQL Password
     * @param $database MySQL database
     * @return FQDB instance
     */
    public static function dbWithMySQLHostUserPasswordDatabase($host, $user, $password, $database) {
        return new FQDB("mysql:host={$host};dbname={$database};charset=utf8mb4", $user, $password);
    }


    /**
     * @param $fqdb - FQDB instance to set as default
     * @return \Readdle\Database\FQDB that instance
     */
    public static function setDefaultFQDB($fqdb) {
        self::$defaultFQDB = $fqdb;
        return $fqdb;
    }


    /**
     * @param $callback - callable that creates default FQDB instance
     */
    public static function setDefaultFQDBCreator($callback) {
        self::$FQDBCreatorCallback = $callback;
    }




    /**
     * returns default FQDB
     * @return \Readdle\Database\FQDB
     */
    public static function defaultFQDB() {
        if (self::$defaultFQDB)
            return self::$defaultFQDB;

        if (is_callable(self::$FQDBCreatorCallback))
            return self::setDefaultFQDB(call_user_func(self::$FQDBCreatorCallback));
    }


}