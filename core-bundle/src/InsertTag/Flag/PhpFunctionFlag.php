<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Flag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTagFlag;
use Contao\CoreBundle\InsertTag\InsertTagFlag;
use Contao\CoreBundle\InsertTag\InsertTagResult;

#[AsInsertTagFlag('addslashes')]
#[AsInsertTagFlag('strtolower')]
#[AsInsertTagFlag('strtoupper')]
#[AsInsertTagFlag('ucfirst')]
#[AsInsertTagFlag('lcfirst')]
#[AsInsertTagFlag('ucwords')]
#[AsInsertTagFlag('trim')]
#[AsInsertTagFlag('rtrim')]
#[AsInsertTagFlag('ltrim')]
#[AsInsertTagFlag('urlencode')]
#[AsInsertTagFlag('rawurlencode')]
class PhpFunctionFlag implements InsertTagFlagInterface
{
    public function __invoke(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        static $allowedNames = null;

        if (null === $allowedNames) {
            foreach ((new \ReflectionClass(__CLASS__))->getAttributes(AsInsertTagFlag::class) as $attribute) {
                $allowedNames[] = $attribute->newInstance()->name;
            }
        }

        // Do not allow arbitrary PHP functions for security reasons
        if (!\in_array($flag->getName(), $allowedNames, true)) {
            throw new \LogicException(sprintf('Invalid flag "%s".', $flag->getName()));
        }

        return $result->withValue($flag->getName()($result->getValue()));
    }
}
