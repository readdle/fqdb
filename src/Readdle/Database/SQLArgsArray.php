<?php declare(strict_types=1);

namespace Readdle\Database;

class SQLArgsArray implements \JsonSerializable
{
    private array $argsArray;
    
    public function __construct(array $argsArray)
    {
        $this->argsArray = [];
        
        foreach ($argsArray as $arg) {
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
