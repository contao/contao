<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Crawl\Escargot\Subscriber;

use Terminal42\Escargot\Subscriber\SubscriberInterface;

interface EscargotSubscriberInterface extends SubscriberInterface
{
    /**
     * Has to return a unique subscriber name so that it can be identified.
     */
    public function getName(): string;

    /**
     * Returns the result. As Escargot can pick up on an existing job ID, your results
     * might be e.g. stored between requests, so you might have a previous result of
     * your subscriber.
     */
    public function getResult(SubscriberResult|null $previousResult = null): SubscriberResult;
}
