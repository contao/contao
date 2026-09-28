<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\Metadata;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use Contao\ApiBundle\Resource\UserTemplate;

final class UserTemplateResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    public function __construct(private readonly ResourceNameCollectionFactoryInterface $decorated)
    {
    }

    public function create(): ResourceNameCollection
    {
        return new ResourceNameCollection([
            ...iterator_to_array($this->decorated->create()),
            UserTemplate::class,
        ]);
    }
}
