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

class UpdateAction extends AbstractAction
{
    use CurrentTrait;
    use NewTrait;

    public function __construct(string $dataSource, private array $current, private array|null $new = null)
    {
        parent::__construct($dataSource);
    }

    protected function getSubjectInfo(): array
    {
        $subject = parent::getSubjectInfo();
        $subject[] = 'ID: '.$this->getCurrentId();

        return $subject;
    }
}
