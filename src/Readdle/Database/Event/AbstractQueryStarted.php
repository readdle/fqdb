<?php declare(strict_types=1);

namespace Readdle\Database\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractQueryStarted extends Event
{
    private string $query;
    private array $params;
    
    public function __construct(string $query, array $params)
    {
        $this->query  = $query;
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
