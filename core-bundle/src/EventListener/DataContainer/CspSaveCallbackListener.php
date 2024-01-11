<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\Csp\CspParser;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

#[AsCallback('tl_page', 'fields.csp.save')]
class CspSaveCallbackListener
{
    public function __construct(private readonly CspParser $cspParser)
    {
    }

    public function __invoke(mixed $value): mixed
    {
        try {
            $this->cspParser->parseHeader((string) $value);
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }

        return $value;
    }
}
