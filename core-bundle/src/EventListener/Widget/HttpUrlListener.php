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

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Validator;
use Contao\Widget;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook("addCustomRegexp")
 */
class HttpUrlListener
{
    public const RGXP_NAME = 'httpurl';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param mixed $input
     */
    public function __invoke(string $regexp, $input, Widget $widget): bool
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
