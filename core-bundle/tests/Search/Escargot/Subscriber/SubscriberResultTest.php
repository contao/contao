<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Escargot\Subscriber;

use Contao\CoreBundle\Search\Escargot\Subscriber\SubscriberResult;
use PHPUnit\Framework\TestCase;

class SubscriberResultTest extends TestCase
{
    public function testSubscriberResultReturnsCorrectValues(): void
    {
        $result = new SubscriberResult(true, 'Summary');

        $this->assertTrue($result->wasSuccessful());
        $this->assertSame('Summary', $result->getSummary());
        $this->assertNull($result->getWarning());
        $this->assertNull($result->getInfo('foobar'));
        $this->assertEmpty($result->getAllInfo());

        $expectedArray = [
            'wasSuccessful' => true,
            'summary' => 'Summary',
            'warning' => null,
            'info' => [],
        ];

        $this->assertSame($expectedArray, $result->toArray());

        $result2 = SubscriberResult::fromArray($expectedArray);

        $this->assertSame($result->toArray(), $result2->toArray());

        $result = new SubscriberResult(false, 'Summary');

        $this->assertFalse($result->wasSuccessful());

        $result->addInfo('foobar', 'baz');
        $result->setWarning('Warning');

        $this->assertSame('Warning', $result->getWarning());
        $this->assertSame('baz', $result->getInfo('foobar'));
        $this->assertSame(['foobar' => 'baz'], $result->getAllInfo());

        $expectedArray = [
            'wasSuccessful' => false,
            'summary' => 'Summary',
            'warning' => 'Warning',
            'info' => ['foobar' => 'baz'],
        ];

        $this->assertSame($expectedArray, $result->toArray());

        $result2 = SubscriberResult::fromArray($expectedArray);

        $this->assertSame($result->toArray(), $result2->toArray());
    }
}
