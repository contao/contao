<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class StartStopValidator
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $startField;

    /**
     * @var string
     */
    private $stopField;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator, string $startField = 'start', string $stopField = 'stop')
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->startField = $startField;
        $this->stopField = $stopField;
    }

    public function validateStartDate($value, DataContainer $dc)
    {
        // No change or empty value
        if ($value === $dc->value || '' === $value) {
            return $value;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        $stop = $request->request->has($this->stopField)
            ? strtotime($request->request->get($this->stopField))
            : $dc->activeRecord->{$this->stopField}
        ;

        if ($stop && $value >= $stop) {
            throw new \RuntimeException($this->translator->trans('ERR.startAfterStop', [], 'contao_default'));
        }

        return $value;
    }

    public function validateStopDate($value, DataContainer $dc)
    {
        // No change or empty value
        if ($value === $dc->value || '' === $value) {
            return $value;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        $start = $request->request->has($this->startField)
            ? strtotime($request->request->get($this->startField))
            : $dc->activeRecord->{$this->startField}
        ;

        if ($start && $start >= $value) {
            throw new \RuntimeException($this->translator->trans('ERR.stopBeforeStart', [], 'contao_default'));
        }

        return $value;
    }
}
