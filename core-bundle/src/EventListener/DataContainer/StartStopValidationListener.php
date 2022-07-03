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

use Contao\CoreBundle\Exception\ValidationErrorException;
use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

class StartStopValidationListener
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function __invoke(array $values, DataContainer $dc): array
    {
        $start = (string) (\array_key_exists('start', $values) ? $values['start'] : $dc->activeRecord->start);
        $stop = (string) (\array_key_exists('stop', $values) ? $values['stop'] : $dc->activeRecord->stop);

        if ('' !== $start && '' !== $stop && $stop < $start) {
            throw new ValidationErrorException($this->translator->trans('ERR.startStop', [], 'contao_default'), ['start', 'stop']);
        }

        return $values;
    }
}
