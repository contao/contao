<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\User;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\User\BackendUser;
use Contao\CoreBundle\Security\User\BackendUserFactory;
use Contao\CoreBundle\Security\User\FrontendUser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class BackendUserFactoryTest extends TestCase
{
    public function testSupportsBackendUser(): void
    {
        $factory = new BackendUserFactory(
            $this->createStub(ContaoFramework::class),
            $this->createStub(Connection::class),
        );

        $this->assertTrue($factory->supportsClass(BackendUser::class));
        $this->assertFalse($factory->supportsClass(FrontendUser::class));
        $this->assertFalse($factory->supportsClass(UserInterface::class));
        $this->assertFalse($factory->supportsClass(\Contao\BackendUser::class));
    }

    public function testRegistersPermissionFields(): void
    {
        $this->assertNotContains('foobar', BackendUserFactory::getPermissionFields());

        BackendUserFactory::registerPermissionField('foobar');

        $this->assertContains('foobar', BackendUserFactory::getPermissionFields());
    }

    public function testOverridesUserFieldsIfOnlyGroupsAreInherited(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $factory = new BackendUserFactory(
            $this->createStub(ContaoFramework::class),
            $this->createStub(Connection::class),
        );

        $user = $factory->create([
            'inherit' => BackendUserFactory::INHERIT_GROUP,
            'modules' => [1,2,3],
            'themes' => [1,2,3],
            'elements' => [1,2,3],
        ]);

        $this->assertSame([], $user->modules);
        $this->assertSame([], $user->themes);
        $this->assertSame([], $user->elements);
    }

    public function testDeserializesArrayFields(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $factory = new BackendUserFactory(
            $this->createStub(ContaoFramework::class),
            $this->createStub(Connection::class),
        );

        $user = $factory->create([
            'pagemounts' => serialize([42]),
            'alexf' => [42],
            'cud' => [42],
        ]);

        $this->assertSame([42], $user->pagemounts);
        $this->assertSame([42], $user->alexf);
        $this->assertSame([42], $user->cud);
    }

    public function testConvertsFilemountsToPaths(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT path FROM tl_files WHERE uuid IN (?)', [[42]], [ArrayParameterType::STRING])
            ->willReturn(['/file/foo'])
        ;

        $factory = new BackendUserFactory(
            $this->createStub(ContaoFramework::class),
            $connection,
        );

        $user = $factory->create([
            'filemounts' => serialize([42]),
        ]);

        $this->assertSame(['/file/foo'], $user->filemounts);
    }

    public function testRemovesTheAdminFieldFromAlexf(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $factory = new BackendUserFactory(
            $this->createStub(ContaoFramework::class),
            $this->createStub(Connection::class),
        );

        $user = $factory->create([
            'alexf' => serialize(['tl_foo::bar', 'tl_user::admin']),
        ]);

        $this->assertSame(['tl_foo::bar'], $user->alexf);
    }

    #[DataProvider('rolesProvider')]
    public function testRoles(array $data, array $roles): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $factory = new BackendUserFactory(
            $this->createStub(ContaoFramework::class),
            $this->createStub(Connection::class),
        );

        $user = $factory->create($data);

        $this->assertSame($roles, $user->getRoles());
    }

    public static function rolesProvider(): iterable
    {
        yield [
            ['admin' => true],
            ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH', 'ROLE_ALLOWED_TO_SWITCH_MEMBER'],
        ];

        yield [
            ['admin' => false, 'amg' => serialize([1,2,3])],
            ['ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH_MEMBER'],
        ];

        yield [
            ['admin' => false, 'amg' => serialize([])],
            ['ROLE_USER'],
        ];
    }

    public function testLoadsTheGroupPermissionsWithInheritGroup(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $dateTime = new \DateTimeImmutable();
        $time = $dateTime->getTimestamp();
        $clock = $this->createStub(ClockInterface::class);
        $clock
            ->method('now')
            ->willReturn($dateTime)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with(
                "SELECT * FROM tl_user_group WHERE id IN (?) AND disable=0 AND (start='' OR start<=$time) AND (stop='' OR stop>$time)",
                [[42]],
                [ArrayParameterType::INTEGER]
            )
            ->willReturn([[
                'pagemounts' => serialize([4,5,6]),
                'alexf' => serialize(['tl_bar::foo']),
            ]])
        ;

        $factory = new BackendUserFactory(
            $this->createStub(ContaoFramework::class),
            $connection,
            $clock,
        );

        $user = $factory->create([
            'inherit' => BackendUserFactory::INHERIT_GROUP,
            'pagemounts' => serialize([1,2,3]),
            'alexf' => ['tl_foo::bar'],
            'groups' => serialize([42]),
        ]);

        $this->assertSame([4,5,6], $user->pagemounts);
        $this->assertSame(['tl_bar::foo'], $user->alexf);
    }

    public function testLoadsTheGroupPermissionsWithInheritExtend(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $dateTime = new \DateTimeImmutable();
        $time = $dateTime->getTimestamp();
        $clock = $this->createStub(ClockInterface::class);
        $clock
            ->method('now')
            ->willReturn($dateTime)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with(
                "SELECT * FROM tl_user_group WHERE id IN (?) AND disable=0 AND (start='' OR start<=$time) AND (stop='' OR stop>$time)",
                [[42]],
                [ArrayParameterType::INTEGER]
            )
            ->willReturn([[
                'pagemounts' => serialize([4,5,6]),
                'alexf' => serialize(['tl_bar::foo']),
            ]])
        ;

        $factory = new BackendUserFactory(
            $this->createStub(ContaoFramework::class),
            $connection,
            $clock,
        );

        $user = $factory->create([
            'inherit' => BackendUserFactory::INHERIT_EXTEND,
            'pagemounts' => serialize([1,2,3]),
            'alexf' => ['tl_foo::bar'],
            'groups' => serialize([42]),
        ]);

        $this->assertSame([1,2,3,4,5,6], $user->pagemounts);
        $this->assertSame(['tl_foo::bar', 'tl_bar::foo'], $user->alexf);
    }
}
