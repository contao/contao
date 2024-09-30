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
use Contao\CoreBundle\InsertTag\OutputType;

#[AsInsertTagFlag('addslashes')]
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
            foreach ((new \ReflectionClass(self::class))->getAttributes(AsInsertTagFlag::class) as $attribute) {
                $allowedNames[] = $attribute->newInstance()->name;
            }
        }

        // Do not allow arbitrary PHP functions for security reasons
        if (!\in_array($flag->getName(), $allowedNames, true)) {
            throw new \LogicException(\sprintf('Invalid flag "%s".', $flag->getName()));
        }

        // These flags are safe for HTML, otherwise change to text
        if (\in_array($flag->getName(), ['addslashes', 'urlencode', 'rawurlencode'], true)) {
            $result = $result->withOutputType(OutputType::html === $result->getOutputType() ? OutputType::html : OutputType::text);
        }

        return $result->withValue($flag->getName()($result->getValue()));
    }
}
