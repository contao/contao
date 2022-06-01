<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\DataContainer;

class CreateAction extends AbstractAction
{
    use NewTrait;

    public function __construct(
        private string $dataSource,
        private ?array $new = null,
    ) {
        parent::__construct($dataSource);
    }
}
