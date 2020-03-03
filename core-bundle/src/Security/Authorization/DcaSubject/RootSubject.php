<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authorization\DcaSubject;

class RootSubject
{
    /**
     * @var string
     */
    private $table;

    /**
     * RootSubject constructor.
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function getTable(): string
    {
        return $this->table;
    }
}
