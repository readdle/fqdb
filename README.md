fqdb
====

Wrapper for PDO with specific DB operations and more checks. Available [via composer](https://packagist.org/packages/readdle/fqdb). 

[![Latest Stable Version](https://poser.pugx.org/readdle/fqdb/v/stable)](https://packagist.org/packages/readdle/fqdb) [![Total Downloads](https://poser.pugx.org/readdle/fqdb/downloads)](https://packagist.org/packages/readdle/fqdb) [![License](https://poser.pugx.org/readdle/fqdb/license)](https://packagist.org/packages/readdle/fqdb) [![Build Status](https://travis-ci.org/readdle/fqdb.svg?branch=master)](https://travis-ci.org/readdle/fqdb)

Initialization:
##### Create FQDB instance directly (requires a call of method 'connect')

```php
$fqdb = new \Readdle\Database\FQDB('mysql:host=localhost;dbname=test', 'user', 'password');
$fqdb->connect();
```

##### Create FQDB instance via FQDBProvider (makes 'connect' call for you) **RECOMMENDED**
```php
// parses ~/.my.cnf
$fqdb = FQDBProvider::dbWithMyCnf($database); 

// dsn example: mysql:host=127.0.0.1;dbname=database;charset=utf8mb4
$fqdb = FQDBProvider::dbWithDSN($dsn, $user, $password);

$fqdb = FQDBProvider::dbWithMySQLHostUserPasswordDatabase($host, $user, $password, $database);

```

##### Examples of usage: 

```php
$fqdb = new \Readdle\Database\FQDB('mysql:host=localhost;dbname=test', 'user', 'password');
$fqdb->connect();

$value = $fqdb->queryValue("SELECT 2+2");
// $value == 4

$hash = $fqdb->queryAssoc("SELECT id, content FROM idcontent WHERE id=13");
// $hash = ['id' => 13, 'content'=>'...'] 

```

FQDB has separate methods for different SQL queries. It throws exception if SQL query and method name does not match.

- insert 
- delete 
- replace 
- update
- set 
- select and show (queryValue, queryList, queryVector, queryAssoc, queryTable, queryObj, queryObjArray, queryTableCallback, queryTableGenerator, queryHash) 


FQDB uses PDO named parameters with additional checks for unused parameters and unbind parameters.

For MySQL driver FQDB has optional warning reporting that emits MySQL warnings as PHP warnings. 

