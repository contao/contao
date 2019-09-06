<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\EventListener;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Terminal42\Escargot\Escargot;

interface EscargotEventSubscriber extends EventSubscriberInterface
{
    /**
     * Has to return a unique subscriber name so that it can be
     * identified.
     */
    public function getName(): string;

    public function getResultAsHtml(Escargot $escargot): string;

    public function addResultToConsole(Escargot $escargot, OutputInterface $output): void;
}
