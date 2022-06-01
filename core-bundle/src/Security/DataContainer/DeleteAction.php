<?php

namespace Contao\CoreBundle\Security\DataContainer;

class DeleteAction extends AbstractAction
{
    use CurrentTrait;

    public function __construct(
        private string $dataSource,
        private array $current,
    ) {
        parent::__construct($dataSource);
    }

    protected function getSubjectInfo(): array
    {
        $subject = parent::getSubjectInfo();
        $subject[] = 'ID: '.$this->getCurrentId();

        return $subject;
    }
}
