<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\InsertTags;

use Contao\CoreBundle\EventListener\InsertTags\TranslationListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Translation\Translator;

class TranslationListenerTest extends TestCase
{
    /**
     * @dataProvider insertTagsProvider
     */
    public function testReplacesInsertTagsWithTranslation(string $id, string $result, string|null $domain = null, array $parameters = []): void
    {
        $translator = $this->createMock(Translator::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with($id, $parameters, $domain)
            ->willReturn($result)
        ;

        $listener = new TranslationListener($translator);

        if (null === $domain) {
            $insertTag = sprintf('trans::%s', $id);
        } elseif (!$parameters) {
            $insertTag = sprintf('trans::%s::%s', $id, $domain);
        } else {
            $insertTag = sprintf('trans::%s::%s::%s', $id, $domain, implode(':', $parameters));
        }

        $this->assertSame($result, $listener->onReplaceInsertTags($insertTag));
    }

    public function insertTagsProvider(): \Generator
    {
        yield ['foo', 'bar'];
        yield ['foo', 'baz', 'bar'];
        yield ['foo', 'else', 'bar', ['baz', 'what']];
    }

    public function testIgnoresOtherInsertTags(): void
    {
        $translator = $this->createMock(Translator::class);
        $translator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $listener = new TranslationListener($translator);

        $this->assertFalse($listener->onReplaceInsertTags('env::pageTitle'));
    }
}
