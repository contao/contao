<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\AddCustomRegexp;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Widget;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Hook("addCustomRegexp")
 */
class CustomRgxpListener
{
    public const RGXP_NAME = 'custom';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(string $regexp, $input, Widget $widget): bool
    {
        if (self::RGXP_NAME !== $regexp) {
            return false;
        }

        if (empty($widget->custom_rgxp) || !\is_string($input)) {
            return true;
        }

        if (!preg_match($widget->custom_rgxp, $input)) {
            $widget->addError($this->translator->trans('ERR.customRgxp', [], 'contao_default'));
        }

        return true;
    }
}
