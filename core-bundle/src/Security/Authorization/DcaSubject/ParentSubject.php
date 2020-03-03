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

class ParentSubject extends RootSubject
{
    /**
     * @var string
     */
    private $pid;

    /**
     * @var string
     */
    private $ptable;

    public function __construct(string $table, string $pid, string $ptable)
    {
        parent::__construct($table);

        $this->pid = $pid;
        $this->ptable = $ptable;
    }

    public function getPid(): string
    {
        return $this->pid;
    }

    public function getPtable(): string
    {
        return $this->ptable;
    }
}
