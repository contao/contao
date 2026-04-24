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
use Contao\DC_Table;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback('tl_image_size', 'fields.lazyLoading.save')]
class LazyLoadingSaveCallbackListener
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(mixed $value, DC_Table $dc): mixed
    {
        if (!$value && 1 === preg_match('/auto/i', $dc->getActiveRecord()['sizes'] ?? '')) {
            throw new \RuntimeException($this->translator->trans('ERR.lazyLoadingSizesAuto', [], 'contao_default'));
        }

        return $value;
    }
}
