<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer\Schema;

use Contao\CoreBundle\Dca\Schema\Dca;
use Contao\CoreBundle\Dca\Schema\Operation;
use Contao\CoreBundle\Event\Dca\SchemaCreatedEvent;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @internal
 */
#[AsEventListener]
class ToggleOperationListener
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function __invoke(SchemaCreatedEvent $event): void
    {
        $schema = $event->getSchema();

        if (!$schema instanceof Operation || $schema->isHidden() || !$schema->isToggle()) {
            return;
        }

        $field = $schema->getParam('field');

        /** @var Dca $dca */
        $dca = $schema->getRoot();
        $field = $dca->fields()->field($field);

        // Hide the toggle icon if the target field is not toggleable
        // or if the user does not have access to the target field
        if (
            (!$field->isToggle() && !$field->isReverseToggle())
            || ($field->isExcluded() && !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $dca->getName().'::'.$field->getName()))
        ) {
            $event->setSchema(
                $schema->copyWith(
                    $schema->getData()->set('hidden', true),
                ),
            );
        }
    }
}
