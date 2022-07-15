<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\Widget;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Widget;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook('addCustomRegexp')]
class CustomRgxpListener
{
    final public const RGXP_NAME = 'custom';

    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function __invoke(string $regexp, mixed $input, Widget $widget): bool
    {
        if (self::RGXP_NAME !== $regexp) {
            return false;
        }

        if (empty($widget->customRgxp) || !\is_string($input)) {
            return true;
        }

        if (!preg_match($widget->customRgxp, $input)) {
            $widget->addError($widget->errorMsg ?: $this->translator->trans('ERR.customRgxp', [$widget->customRgxp], 'contao_default'));
        }

        return true;
    }
}
