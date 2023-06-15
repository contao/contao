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

class PhpFunctionFlag
{
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
    public function __invoke(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        if (
            !\in_array(
                $flag->getName(),
                [
                    'addslashes',
                    'strtolower',
                    'strtoupper',
                    'ucfirst',
                    'lcfirst',
                    'ucwords',
                    'trim',
                    'rtrim',
                    'ltrim',
                    'urlencode',
                    'rawurlencode',
                ],
                true,
            )
        ) {
            throw new \LogicException(sprintf('Invalid flag "%s".', $flag->getName()));
        }

        return new InsertTagResult(
            $flag->getName()($result->getValue()),
            OutputType::html === $result->getOutputType() ? OutputType::html : OutputType::text,
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }
}
