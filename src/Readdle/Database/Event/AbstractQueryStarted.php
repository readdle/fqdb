<?php

namespace Readdle\Database\Event;

use Symfony\Component\EventDispatcher\Event;

abstract class AbstractQueryStarted extends Event
{
    private $query;
    private $params;
    
    /**
     * @param string $query
     * @param array $params
     */
    public function __construct($query, $params)
    {
        $this->query = $query;
        $this->params = $params;
    }
    
    /**
     * @return string
     */
    public function query()
    {
        return $this->query;
    }
    
    /**
     * @return array
     */
    public function params()
    {
        return $this->params;
    }
}
