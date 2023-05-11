<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer\Content;

use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

class DescriptionListLabelListener
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(array $attributes, DataContainer $dc = null): array
    {
        if (null === $dc || 'description_list' !== ($dc->getCurrentRecord()['type'] ?? null)) {
            return $attributes;
        }

        $attributes['label'] = $this->translator->trans('tl_content.dl_label.0', [], 'contao_tl_content');
        $attributes['description'] = $this->translator->trans('tl_content.dl_label.1', [], 'contao_tl_content');
        $attributes['keyLabel'] = $this->translator->trans('tl_content.dl_key', [], 'contao_tl_content');
        $attributes['valueLabel'] = $this->translator->trans('tl_content.dl_value', [], 'contao_tl_content');
        $attributes['mandatory'] = true;
        $attributes['allowEmptyKeys'] = true;

        return $attributes;
    }
}
