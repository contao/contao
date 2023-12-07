<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Schema;

use Doctrine\DBAL\Schema\Column;
use Psr\Container\ContainerInterface;

/**
 * Object representation of a single field of a data container array.
 */
class Field extends Schema implements ServiceSubscriberSchemaInterface
{
    protected array $schemaClasses = [
        '*_callback' => CallbackCollection::class,
    ];

    protected ContainerInterface $locator;

    public function isExcluded(): bool
    {
        return $this->is('exclude');
    }

    public function inputType(): string
    {
        return $this->get('inputType');
    }

    public function column(): Column
    {
        // TODO: Implement.
        throw new \BadMethodCallException('The column method is not implemented yet.');
    }

    public function setLocator(ContainerInterface $locator): void
    {
        $this->locator = $locator;
    }

    public function isToggle(): bool
    {
        return $this->get('toggle') ?? false;
    }

    public function isReverseToggle(): bool
    {
        return $this->get('reverseToggle') ?? false;
    }

    public static function getSubscribedServices(): array
    {
        return [
            // TODO: Implement service to get the column from the field's SQL.
        ];
    }
}
