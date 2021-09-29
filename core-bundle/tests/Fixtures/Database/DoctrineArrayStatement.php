<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Database;

use Doctrine\DBAL\Cache\ArrayStatement;
use Doctrine\DBAL\ParameterType;

/**
 * @deprecated
 */
class DoctrineArrayStatement extends ArrayStatement
{
    private int $rowCount;

    public function __construct(array $data)
    {
        $this->rowCount = \count($data);

        parent::__construct($data);
    }

    public function rowCount()
    {
        return $this->rowCount;
    }

    public function bindValue($param, $value, $type = ParameterType::STRING): void
    {
        throw new \RuntimeException('Not implemented');
    }

    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null): void
    {
        throw new \RuntimeException('Not implemented');
    }

    public function errorCode(): void
    {
        throw new \RuntimeException('Not implemented');
    }

    public function errorInfo(): void
    {
        throw new \RuntimeException('Not implemented');
    }

    public function execute($params = null): void
    {
        throw new \RuntimeException('Not implemented');
    }
}
