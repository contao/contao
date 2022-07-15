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
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback(table: 'tl_form_field', target: 'fields.customRgxp.save')]
class ValidateCustomRgxpListener
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function __invoke(mixed $input): mixed
    {
        // preg_match() returns false if the regular expression is invalid
        if (false === @preg_match($input, '')) {
            throw new \InvalidArgumentException($this->translator->trans('ERR.invalidCustomRgxp', [], 'contao_default'));
        }

        return $input;
    }
}
