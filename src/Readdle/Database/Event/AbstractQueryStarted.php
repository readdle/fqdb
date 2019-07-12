<?php

namespace Readdle\Database\Event;

use Symfony\Component\EventDispatcher\Event;

abstract class AbstractQueryStarted extends Event
{
    private $query;
    private $params;
    
    public function __construct(string $query, array $params)
    {
        $this->query = $query;
        $this->params = $params;
    }
    
    public function query(): string
    {
        return $this->query;
    }
    
    public function params(): array
    {
        return $this->params;
    }
}
