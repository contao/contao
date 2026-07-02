<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class DataContainerRecordSchema extends Constraint
{
    public string $message = 'This value is not valid for the Contao data container.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
