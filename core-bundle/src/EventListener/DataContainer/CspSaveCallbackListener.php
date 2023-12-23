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
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback('tl_page', 'fields.csp.save')]
class CspSaveCallbackListener
{
    public function __construct(
        private readonly CspParser $cspParser,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(mixed $value): mixed
    {
        try {
            $this->cspParser->parseHeader((string) $value);
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException($this->translator->trans('ERR.invalidCsp', [$e->getMessage()], 'contao_default'), 0, $e);
        }

        return $value;
    }
}
