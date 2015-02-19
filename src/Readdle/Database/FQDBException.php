<?php
namespace Readdle\Database;

final class FQDBException extends \RuntimeException
{

    const FQDB_CODE              = 0;
    const PDO_CODE               = 1;
    const FQDB_PROVIDER_CODE     = 2;

    const WRONG_QUERY                     = 'Given query does not fit called method';
    const NO_DB_CONNECTION_ERROR          = 'No DB connection';
    const NO_ACTIVE_QUERY_ERROR           = 'No active query error';
    const DB_ALREADY_CONNECTED            = 'Already have active connection to a DB';
    const NOT_CALLABLE_ERROR              = 'Param is not callable';
    const CLASS_NOT_EXIST                 = 'Class not exists';
    const PLACEHOLDERS_ERROR              = 'Placeholders not set properly';
    const IDENTIFIER_QUOTE_ERROR          = 'Could not quote identifier: contains quote character';
    const WRONG_DATA_TYPE_ON_IN_STATEMENT = 'Data must be array if you use FQDB::PARAM_IN_STATEMENT_VALUES param type';




    public function __construct($message = "", $code, \Exception $previous = null) {
        $code_message_prefix = ['FQDB', 'PDO', 'FQDBProvider'];
        if (empty($message) && $previous != null)
            $message = $previous->getMessage();

        parent::__construct($code_message_prefix[$code].': '.$message, $code, $previous);
    }

}