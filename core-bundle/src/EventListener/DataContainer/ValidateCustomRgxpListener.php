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

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_form_field", target="fields.custom_rgxp.save")
 */
class ValidateCustomRgxpListener
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke($input)
    {
        if (false === @preg_match($input, null)) {
            throw new \Exception($this->translator->trans('ERR.invalidCustomRgxp', [], 'contao_default'));
        }

        return $input;
    }
}
