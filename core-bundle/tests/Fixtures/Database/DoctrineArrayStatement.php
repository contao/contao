<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Database;

use Doctrine\DBAL\Cache\ArrayStatement;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

class DoctrineArrayStatement extends ArrayStatement implements Statement
{
    private $rowCount;

    public function __construct(array $data)
    {
        $this->rowCount = \count($data);

        parent::__construct($data);
    }

    public function rowCount()
    {
        return $this->rowCount;
    }

    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        throw new \RuntimeException('Not implemented');
    }

    public function bindParam($column, &$variable, $type = ParameterType::STRING, $length = null)
    {
        throw new \RuntimeException('Not implemented');
    }

    public function errorCode()
    {
        throw new \RuntimeException('Not implemented');
    }

    public function errorInfo()
    {
        throw new \RuntimeException('Not implemented');
    }

    public function execute($params = null)
    {
        throw new \RuntimeException('Not implemented');
    }
}
