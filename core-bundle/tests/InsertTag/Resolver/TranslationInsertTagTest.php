<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\InsertTag\Resolver;

use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\InsertTag\Resolver\TranslationInsertTag;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Translation\Translator;

class TranslationInsertTagTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @dataProvider insertTagsProvider
     *
     * @group legacy
     */
    public function testReplacesInsertTagsWithTranslation(string $id, string $result, string|null $domain = null, array $parameters = []): void
    {
        $translator = $this->createMock(Translator::class);
        $translator
            ->expects($this->atLeastOnce())
            ->method('trans')
            ->with($id, $parameters, $domain)
            ->willReturn($result)
        ;

        $listener = new TranslationInsertTag($translator);

        if (null === $domain) {
            $params = [$id];
        } elseif (!$parameters) {
            $params = [$id, $domain];
        } else {
            $params = [$id, $domain, ...$parameters];
        }

        $this->assertSame($result, $listener(new ResolvedInsertTag('trans', new ResolvedParameters($params), []))->getValue());

        if ($parameters) {
            $params = [$id, $domain, implode(':', $parameters)];

            $this->expectDeprecation('%sPassing parameters to the trans insert tag separated by a single colon has has been deprecated%s');
            $this->assertSame($result, $listener(new ResolvedInsertTag('trans', new ResolvedParameters($params), []))->getValue());
        }
    }

    public static function insertTagsProvider(): iterable
    {
        yield ['foo', 'bar'];
        yield ['foo', 'baz', 'bar'];
        yield ['foo', 'else', 'bar', ['baz', 'what']];
    }
}
