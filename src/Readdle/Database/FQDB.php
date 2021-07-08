<?php declare(strict_types=1);

namespace Readdle\Database;

final class FQDB extends FQDBWriteAPI implements \Serializable
{
    // we could not serialize PDO object anyway
    public function serialize()
    {
        return null;
    }

    public function unserialize($string)
    {
        return null;
    }
}
