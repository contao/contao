<?php

namespace Contao\Fixtures;

use Doctrine\DBAL\Driver\Connection as DoctrineConnection;

class Connection implements DoctrineConnection
{
    private $index = -1;
    private $prepareString;
    private $languages = ['en-US', 'en'];

    function prepare($prepareString)
    {
        $this->prepareString = $prepareString;

        return $this;
    }

    function execute()
    {

    }

    function fetch($type = \PDO::FETCH_OBJ)
    {
        if (++$this->index >= count($this->languages)) {
            return false;
        }

        $class = new \stdClass();
        $class->language = $this->languages[$this->index];

        return $class;
    }

    function query()
    {

    }

    function quote($input, $type = \PDO::PARAM_STR)
    {

    }

    function exec($statement)
    {

    }

    function lastInsertId($name = null)
    {

    }

    function beginTransaction()
    {

    }

    function commit()
    {

    }

    function rollBack()
    {

    }

    function errorCode()
    {

    }

    function errorInfo()
    {

    }
}
