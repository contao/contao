<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\ORM\ExtendableEntity;

/**
 * This interface acts as a tag for doctrine entities with single table
 * inheritance configured that should be able to be extended by 3rd party
 * entities. Make sure to specify a DiscriminatorColumn.
 */
interface ExtendableEntity
{
}
