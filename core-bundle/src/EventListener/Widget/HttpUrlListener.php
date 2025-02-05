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
use Contao\Validator;
use Contao\Widget;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook('addCustomRegexp')]
class HttpUrlListener
{
    final public const RGXP_NAME = 'httpurl';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(string $regexp, mixed $input, Widget $widget): bool
    {
        if (self::RGXP_NAME !== $regexp) {
            return false;
        }

        if (!\is_string($input)) {
            return true;
        }

        if (!preg_match('~^https?://~i', $input)) {
            $widget->addError($this->translator->trans('ERR.invalidHttpUrl', [$widget->label], 'contao_default'));
        } elseif (!Validator::isUrl($input)) {
            $widget->addError($this->translator->trans('ERR.url', [$widget->label], 'contao_default'));
        }

        return true;
    }
}
