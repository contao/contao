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
use Contao\Widget;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook("addCustomRegexp")
 */
class CustomRgxpListener
{
    public const RGXP_NAME = 'custom';

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

        if (empty($widget->customRgxp) || !\is_string($input)) {
            return true;
        }

        if (!preg_match($widget->customRgxp, $input)) {
            $widget->addError($widget->errorMsg ?: $this->translator->trans('ERR.customRgxp', [$widget->customRgxp], 'contao_default'));
        }

        return true;
    }
}
