<?php

namespace Contao\Fixtures;

class Result
{
    private $data;
    private $index = -1;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __get($key)
    {
        return $this->data[$this->index]->$key;
    }

    public function next()
    {
        if (++$this->index >= count($this->data)) {
            return false;
        }

        return true;
    }
}
