<?php declare(strict_types=1);

namespace Readdle\Database;

class SQLArgs implements \JsonSerializable
{
    private array $argsArray;
    
    public function __construct()
    {
        $this->argsArray = [];
        $args            = \func_get_args();
        
        foreach ($args as $arg) {
            if (\is_null($arg)) {
                $this->argsArray[] = new SQLValueNull();
            } else {
                $this->argsArray[] = $arg;
            }
        }
    }
    
    public function toArray(): array
    {
        return $this->argsArray;
    }
    
    public function jsonSerialize()
    {
        return $this->argsArray;
    }
}
