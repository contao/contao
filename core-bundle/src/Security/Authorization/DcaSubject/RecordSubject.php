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

class RecordSubject extends RootSubject
{
    /**
     * @var string
     */
    private $id;

    /**
     * RootSubject constructor.
     */
    public function __construct(string $table, string $id)
    {
        parent::__construct($table);
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
