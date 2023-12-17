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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use ParagonIE\CSPBuilder\CSPBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback('tl_page', 'fields.csp.save')]
class CspSaveCallbackListener
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke($value)
    {
        try {
            CSPBuilder::fromHeader(trim((string) $value));
        } catch (\Throwable $e) {
            throw new \Exception($this->translator->trans('ERR.invalidCsp', [$e->getMessage()], 'contao_default'), 0, $e);
        }

        return $value;
    }
}
