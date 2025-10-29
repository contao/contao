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

use Contao\CoreBundle\EventListener\DataContainer\ActiveMemberGroupsListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\MemberGroupModel;
use Contao\Model\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

class ActiveMemberGroupsListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']);
    }

    #[DataProvider('registerCallbackProvider')]
    public function testRegistersCallback(bool $backend, string $table, bool $expected): void
    {
        $this->assertFalse(isset($GLOBALS['TL_DCA']['tl_member']['fields']['groups']['options_callback']));

        $scopeMatcher = $this->mockLocalScopeMatcher($backend);
        $listener = new ActiveMemberGroupsListener($this->mockContaoFramework(), $scopeMatcher);

        $listener($table);

        $this->assertSame($expected, \is_callable($GLOBALS['TL_DCA']['tl_member']['fields']['groups']['options_callback'] ?? null));
    }

    public static function registerCallbackProvider(): iterable
    {
        yield [
            false,
            'tl_member',
            true,
        ];

        yield [
            true,
            'tl_member',
            false,
        ];

        yield [
            false,
            'tl_foobar',
            false,
        ];

        yield [
            true,
            'tl_foobar',
            false,
        ];
    }

    public function testCallbackReturnsGroupNames(): void
    {
        $group1 = $this->mockMemberGroup(['id' => 42, 'name' => 'foo']);
        $group2 = $this->mockMemberGroup(['id' => 21, 'name' => 'bar']);

        $collection = new Collection([$group1, $group2], 'tl_member_group');

        $memberGroupAdapter = $this->mockAdapter(['findAllActive']);
        $memberGroupAdapter
            ->expects($this->once())
            ->method('findAllActive')
            ->willReturn($collection)
        ;

        $framework = $this->mockContaoFramework([MemberGroupModel::class => $memberGroupAdapter]);

        $scopeMatcher = $this->mockLocalScopeMatcher(false);

        $listener = new ActiveMemberGroupsListener($framework, $scopeMatcher);
        $listener('tl_member');

        $this->assertIsCallable($GLOBALS['TL_DCA']['tl_member']['fields']['groups']['options_callback']);

        $groups = $GLOBALS['TL_DCA']['tl_member']['fields']['groups']['options_callback']();

        $this->assertSame([42 => 'foo', 21 => 'bar'], $groups);
    }

    public function testCallbackReturnsEmptyArrayIfNoGroupsAreFound(): void
    {
        $memberGroupAdapter = $this->mockAdapter(['findAllActive']);
        $memberGroupAdapter
            ->expects($this->once())
            ->method('findAllActive')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([MemberGroupModel::class => $memberGroupAdapter]);

        $scopeMatcher = $this->mockLocalScopeMatcher(false);

        $listener = new ActiveMemberGroupsListener($framework, $scopeMatcher);
        $listener('tl_member');

        $this->assertIsCallable($GLOBALS['TL_DCA']['tl_member']['fields']['groups']['options_callback']);

        $groups = $GLOBALS['TL_DCA']['tl_member']['fields']['groups']['options_callback']();

        $this->assertSame([], $groups);
    }

    private function mockLocalScopeMatcher(bool $backend): MockObject&ScopeMatcher
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->atMost(1))
            ->method('isBackendRequest')
            ->willReturn($backend)
        ;

        return $scopeMatcher;
    }

    private function mockMemberGroup(array $data): MemberGroupModel
    {
        $ref = new \ReflectionClass(MemberGroupModel::class);

        $group = $ref->newInstanceWithoutConstructor();

        $ref->getProperty('arrData')->setValue($group, $data);

        return $group;
    }
}
