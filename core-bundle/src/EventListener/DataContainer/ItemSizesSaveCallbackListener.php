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
use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback('tl_image_size_item', 'fields.sizes.save')]
class ItemSizesSaveCallbackListener
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(mixed $value, DataContainer $dc): mixed
    {
        $sizes = (string) $value;

        if (1 === preg_match('/(?:^|,)\s*auto/i', $sizes) && 0 === preg_match('/^auto(?:,|$)/i', $sizes)) {
            throw new \RuntimeException($this->translator->trans('ERR.sizesStartWithAuto', [], 'contao_default'));
        }

        if (1 === preg_match('/^auto(?:,|$)/i', $sizes) && !$dc->getCurrentRecord($dc->getCurrentRecord()['pid'], 'tl_image_size')['lazyLoading']) {
            throw new \RuntimeException($this->translator->trans('ERR.lazyLoadingSizesAuto', [], 'contao_default'));
        }

        return $value;
    }
}
