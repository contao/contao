<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\ThemeLayoutListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ThemeLayoutListenerTest extends TestCase
{
    #[DataProvider('provideLayoutTypes')]
    public function testAdjustsTemplateAttributesForSubmittedType(string $storedType, array $submitted, bool $legacy): void
    {
        $dc = $this->createStub(DataContainer::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['type' => $storedType])
        ;

        $attributes = ['mandatory' => true, 'required' => true, 'submitOnChange' => true];

        $this->assertSame(
            array_fill_keys(array_keys($attributes), !$legacy),
            $this->getListener($submitted)->adjustFieldsForLegacyType($attributes, $dc),
        );
    }

    #[DataProvider('provideTemplateSubmissions')]
    public function testResetsTemplateForType(array $current, array $values, array $submitted, array $expected): void
    {
        $dc = $this->createStub(DataContainer::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($current)
        ;

        $this->assertSame($expected, $this->getListener($submitted)->resetTemplateForType($values, $dc));
    }

    public static function provideLayoutTypes(): iterable
    {
        yield 'stored legacy' => ['default', [], true];
        yield 'stored modern' => ['modern', [], false];
        yield 'submit modern' => ['default', ['type' => 'modern'], false];
        yield 'submit legacy' => ['modern', ['type' => 'default'], true];
        yield 'auto-submit modern with old legacy template' => ['default', ['type' => 'modern', 'SUBMIT_TYPE' => 'auto'], true];
        yield 'auto-submit legacy' => ['modern', ['type' => 'default', 'SUBMIT_TYPE' => 'auto'], true];
    }

    public static function provideTemplateSubmissions(): iterable
    {
        yield 'create modern with template' => [
            ['type' => 'default', 'template' => ''],
            ['type' => 'modern', 'template' => 'page/layout'],
            ['type' => 'modern', 'template' => 'page/layout'],
            ['type' => 'modern', 'template' => 'page/layout'],
        ];

        yield 'switch to legacy with template' => [
            ['type' => 'modern', 'template' => 'page/layout'],
            ['type' => 'default', 'template' => 'fe_page'],
            ['type' => 'default', 'template' => 'fe_page'],
            ['type' => 'default', 'template' => 'fe_page'],
        ];

        yield 'auto-submit clears stale template' => [
            ['type' => 'default', 'template' => 'fe_page'],
            ['type' => 'modern', 'template' => 'fe_page'],
            ['type' => 'modern', 'template' => 'fe_page', 'SUBMIT_TYPE' => 'auto'],
            ['type' => 'modern', 'template' => ''],
        ];

        yield 'type change without template clears stored template' => [
            ['type' => 'modern', 'template' => 'page/layout'],
            ['type' => 'default'],
            ['type' => 'default'],
            ['type' => 'default', 'template' => ''],
        ];

        yield 'unchanged type keeps template' => [
            ['type' => 'modern', 'template' => 'page/layout'],
            ['type' => 'modern'],
            ['type' => 'modern'],
            ['type' => 'modern'],
        ];

        yield 'template-only update' => [
            ['type' => 'modern', 'template' => 'page/layout'],
            ['template' => 'page/layout/custom'],
            ['template' => 'page/layout/custom'],
            ['template' => 'page/layout/custom'],
        ];
    }

    private function getListener(array $submitted): ThemeLayoutListener
    {
        return new ThemeLayoutListener(
            $this->createStub(FinderFactory::class),
            $this->createStub(Inspector::class),
            $this->createStub(ContaoFramework::class),
            new RequestStack([new Request(request: $submitted)]),
            $this->createStub(Connection::class),
        );
    }
}
