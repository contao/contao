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

class DeleteAction extends AbstractAction
{
    use CurrentTrait;

    private array $current = [];

    public function __construct(
        private string $dataSource,
        private string $currentId,
    ) {
        parent::__construct($dataSource);
        $this->current['id'] = $this->currentId;
    }

    protected function getSubjectInfo(): array
    {
        $subject = parent::getSubjectInfo();
        $subject[] = 'ID: '.$this->getCurrentId();

        return $subject;
    }
}
