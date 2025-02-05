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

use Contao\ContentProxy;
use Contao\CoreBundle\EventListener\DataContainer\ContentElementTypeListener;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;
use Symfony\Bundle\SecurityBundle\Security;

class ContentElementTypeListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CTE'], $GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    /**
     * @dataProvider getDcTableProperties
     */
    public function testGetOptions(string|null $parentTable, int|null $pid): void
    {
        $GLOBALS['TL_CTE'] = [
            'foo' => [
                'bar' => ContentProxy::class,
                'baz' => ContentProxy::class,
            ],
            'fii' => [
                'bas' => ContentProxy::class,
                'bat' => ContentProxy::class,
            ],
        ];

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(4))
            ->method('isGranted')
            ->willReturnCallback(
                function (string $attribute, CreateAction $action) use ($parentTable, $pid): bool {
                    $this->assertSame(ContaoCorePermissions::DC_PREFIX.'tl_content', $attribute);
                    $this->assertSame($parentTable, $action->getNew()['ptable']);
                    $this->assertSame($pid, $action->getNew()['pid']);
                    $this->assertContains($action->getNew()['type'], ['bar', 'baz', 'bas', 'bat']);

                    return \in_array($action->getNew()['type'], ['bar', 'bas'], true);
                },
            )
        ;

        $dataContainer = $this->mockClassWithProperties(DC_Table::class, ['parentTable' => $parentTable, 'currentPid' => $pid]);

        $listener = new ContentElementTypeListener($security);
        $options = $listener->getOptions($dataContainer);

        $this->assertSame(['foo' => ['bar'], 'fii' => ['bas']], $options);
    }

    public static function getDcTableProperties(): iterable
    {
        yield ['tl_foo', 42];
        yield ['tl_foo', null];
        yield [null, 42];
        yield [null, null];
    }

    public function testOverridesTheDefaultType(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields']['type']['sql']['default'] = 'bar';
        $GLOBALS['TL_CTE'] = [
            'foo' => [
                'bar' => ContentProxy::class,
                'baz' => ContentProxy::class,
            ],
        ];
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnCallback(
                function (string $attribute, CreateAction $action): bool {
                    $this->assertSame(ContaoCorePermissions::DC_PREFIX.'tl_content', $attribute);
                    $this->assertSame('tl_foo', $action->getNew()['ptable']);
                    $this->assertSame(42, $action->getNew()['pid']);
                    $this->assertContains($action->getNew()['type'], ['bar', 'baz']);

                    return 'baz' === $action->getNew()['type'];
                },
            )
        ;

        $dataContainer = $this->mockClassWithProperties(DC_Table::class, ['parentTable' => 'tl_foo', 'currentPid' => 42]);

        $listener = new ContentElementTypeListener($security);
        $listener->setDefault($dataContainer);

        $this->assertSame('baz', $GLOBALS['TL_DCA']['tl_content']['fields']['type']['default']);
    }
}
