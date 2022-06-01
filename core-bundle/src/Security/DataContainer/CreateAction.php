<?php

namespace Contao\CoreBundle\Security\DataContainer;

class CreateAction extends AbstractAction
{
    use NewTrait;

    public function __construct(
        private string $dataSource,
        private ?array $new,
    ) {
        parent::__construct($dataSource);
    }
}
