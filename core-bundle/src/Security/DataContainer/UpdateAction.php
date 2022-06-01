<?php

namespace Contao\CoreBundle\Security\DataContainer;

class UpdateAction extends AbstractAction
{
    use CurrentTrait, NewTrait;

    public function __construct(
        private string $dataSource,
        private array $current,
        private ?array $new,
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
