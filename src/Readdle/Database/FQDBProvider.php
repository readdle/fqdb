<?php
namespace Readdle\Database;

class FQDBProvider {

    static private $defaultFQDB;
    static private $FQDBCreatorCallback;


    static function dbWithMyCnf($database='') {
        if (!isset($_SERVER['HOME']))
            throw new FQDBException("unable to find home dir");

        $my_cnf_path = $_SERVER['HOME'].'/.my.cnf';

        if (!file_exists($my_cnf_path))
            throw new FQDBException("unable to find {$my_cnf_path}");

        $lines = file($my_cnf_path);
        $user = $pass = "";
        foreach($lines as $line) {
            $kv = explode("=", $line);
            if (count($kv) != 2)
                continue;
            $kv = array_map('trim', $kv);
            if ($kv[0] == 'user')
                $user = $kv[1];
            if ($kv[0] == 'pass')
                $pass = $kv[1];
            if ($database == '' && $kv == 'database')
                $database = $kv[1];
        }

        if ($user == "")
            throw new FQDBException("unable to find user in .my.cnf");

        return new FQDB("mysql:host=localhost;dbname={$database};charset=utf8", $user, $pass);

    }

    /**
     * @param $fqdb - FQDB instance to set as default
     * @return \Readdle\Database\FQDB that instance
     */
    static function setDefaultFQDB($fqdb) {
        self::$defaultFQDB = $fqdb;
        return $fqdb;
    }

    static function setDefaultFQDBCreator($callback) {
        self::$FQDBCreatorCallback = $callback;
    }


    /**
     * returns default FQDB
     * @return \Readdle\Database\FQDB
     */
    static function defaultFQDB() {
        if (self::$defaultFQDB)
            return self::$defaultFQDB;

        if (is_callable(self::$FQDBCreatorCallback))
            return self::setDefaultFQDB(call_user_func(self::$FQDBCreatorCallback));
    }


}