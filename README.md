fqdb
====

Wrapper for PDO with specific DB operations and more checks. 

Example: 

```php
$fqdb = new \Readdle\Database\FQDB('mysql:host=localhost;dbname=test', 'user', 'password');

$value = $fqdb->queryValue("SELECT 2+2");
// $value == 4

$hash = $fqdb->queryAssoc("SELECT id, content FROM idcontent WHERE id=13");
// $hash = ['id' => 13, 'content'=>'...'] 

```

FQDB has separate methods for different SQL queries. It throws exception is  SQL query and method name does not match.

- insert 
- delete 
- replace 
- update
- set 
- select and show (queryValue, queryList, queryVector, queryAssoc, queryTable, queryObj, queryObjArray) 


FQDB uses PDO named parameters with additional checks, that there are no unused parameters and no unbind parameters.

For MySQL FQDB has automatic warning reporting, that fetches MySQL warnings from the database. 

