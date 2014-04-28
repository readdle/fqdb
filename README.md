fqdb
====

Wrapper for PDO with specific DB operations and more checks. Available [via composer](https://packagist.org/packages/readdle/fqdb). 

[![Build Status](https://travis-ci.org/readdle/fqdb.svg?branch=master)](https://travis-ci.org/readdle/fqdb)

Example: 

```php
$fqdb = new \Readdle\Database\FQDB('mysql:host=localhost;dbname=test', 'user', 'password');

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
- select and show (queryValue, queryList, queryVector, queryAssoc, queryTable, queryObj, queryObjArray) 


FQDB uses PDO named parameters with additional checks for unused parameters and unbind parameters.

For MySQL driver FQDB has optional warning reporting that emits MySQL warnings as PHP warnings. 

