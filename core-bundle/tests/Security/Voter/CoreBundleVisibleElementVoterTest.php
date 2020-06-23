<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Voter;

use Contao\ArticleModel;
use Contao\BackendUser;
use Contao\ContentModel;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Voter\AbstractFrontendAccessVoter;
use Contao\CoreBundle\Security\Voter\CoreBundleVisibleElementVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\LayoutModel;
use Contao\ModuleModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CoreBundleVisibleElementVoterTest extends TestCase
{
    /**
     * @var CoreBundleVisibleElementVoter
     */
    private $voter;

    /**
     * @var ContaoFramework|MockObject
     */
    private $framework;

    /**
     * @var Adapter&MockObject
     */
    private $systemAdapter;

    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_HOOKS']);

        $this->systemAdapter = $this->mockAdapter(['importStatic']);

        $this->framework = $this->mockContaoFramework([System::class => $this->systemAdapter]);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with('contao.framework')
            ->willReturn($this->framework)
        ;

        $this->voter = new CoreBundleVisibleElementVoter();
        $this->voter->setContainer($container);
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testAbstainsIfTheAttributeIsNotAString(string $modelClass): void
    {
        $token = $this->mockToken();
        $subject = $this->mockSubject($modelClass);
        $attributes = [new Expression('!is_granted("ROLE_MEMBER")')];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $subject, $attributes));
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testAbstainsIfTheAttributeDoesNotMatch(string $modelClass): void
    {
        $token = $this->mockToken();
        $subject = $this->mockSubject($modelClass);
        $attributes = ['foo', 'bar', 'contao.', 'contao_user_name'];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $subject, $attributes));
    }

    public function testAbstainsIfTheSubjectDoesNotMatch(): void
    {
        $token = $this->mockToken();
        $subject = $this->mockClassWithProperties(LayoutModel::class);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $subject, $attributes));
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testGrantsAccessIfElementIsNotProtected(string $modelClass): void
    {
        $token = $this->mockToken();
        $subject = $this->mockSubject($modelClass, false);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, $attributes));
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testDeniesAccessIfElementIsForGuestsWithUser(string $modelClass): void
    {
        $user = $this->mockFrontendUser();
        $token = $this->mockToken($user);
        $subject = $this->mockSubject($modelClass, false, [], true);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, $attributes));
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testDeniesAccessIfElementIsProtectedWithoutUser(string $modelClass): void
    {
        $token = $this->mockToken();
        $subject = $this->mockSubject($modelClass, true);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, $attributes));
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testDeniesAccessIfTokenDoesNotHaveMemberRole(string $modelClass): void
    {
        $token = $this->mockToken($this->createMock(FrontendUser::class), ['ROLE_FOO']);
        $subject = $this->mockSubject($modelClass, true);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, $attributes));
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testDeniesAccessIfElementIsProtectedWithoutFrontendUser(string $modelClass): void
    {
        $token = $this->mockToken($this->createMock(BackendUser::class));
        $subject = $this->mockSubject($modelClass, true);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, $attributes));
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testGrantsAccessOnGroupMatch(string $modelClass): void
    {
        $user = $this->mockFrontendUser([1]);
        $token = $this->mockToken($user);
        $subject = $this->mockSubject($modelClass, true, [1]);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, $attributes));
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testDeniesAccessOnGroupMismatch(string $modelClass): void
    {
        $user = $this->mockFrontendUser([2]);
        $token = $this->mockToken($user);
        $subject = $this->mockSubject($modelClass, true, [1]);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, $attributes));
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testGrantsAccessOnGroupIntersect(string $modelClass): void
    {
        $user = $this->mockFrontendUser([1, 2, 3, 4, 5, 6, 7, 8]);
        $token = $this->mockToken($user);
        $subject = $this->mockSubject($modelClass, true, [5, 6, 7]);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, $attributes));
    }

    /**
     * @dataProvider modelClassProvider
     */
    public function testExecutesIsVisibleElementHook(string $modelClass)
    {
        $token = $this->mockToken();
        $subject = $this->mockSubject($modelClass);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $callback = $this->getMockBuilder(\stdClass::class)->addMethods(['onIsVisibleElement'])->getMock();
        $callback
            ->expects($this->once())
            ->method('onIsVisibleElement')
            ->with($subject, true)
            ->willReturn(false)
        ;

        $this->framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $this->systemAdapter
            ->expects($this->once())
            ->method('importStatic')
            ->with('FooBar')
            ->willReturn($callback)
        ;

        $GLOBALS['TL_HOOKS']['isVisibleElement'][] = ['FooBar', 'onIsVisibleElement'];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, $attributes));
    }


    public function modelClassProvider(): \Generator
    {
        yield [ContentModel::class];

        yield [ModuleModel::class];

        yield [ArticleModel::class];
    }

    public function testGetSubscribedServices(): void
    {
        $this->assertSame(
            ['contao.framework' => ContaoFramework::class],
            CoreBundleVisibleElementVoter::getSubscribedServices()
        );
    }

    private function mockSubject(string $modelClass, bool $protected = false, array $groups = [], bool $guests = false)
    {
        return $this->mockClassWithProperties(
            $modelClass,
            [
                'protected' => $protected,
                'groups' => $groups,
                'guests' => $guests,
            ]
        );
    }

    private function mockFrontendUser(array $groups = [])
    {
        return $this->mockClassWithProperties(FrontendUser::class, ['groups' => $groups]);
    }

    /**
     * @param null $user
     * @param array|string[] $roles
     * @return TokenInterface&MockObject
     */
    private function mockToken($user = null, array $roles = ['ROLE_MEMBER']): TokenInterface
    {
        $token = $this
            ->getMockBuilder(TokenInterface::class)
            ->addMethods(['getRoleNames'])
            ->getMockForAbstractClass()
        ;

        $token
            ->method('getRoleNames')
            ->willReturn($roles)
        ;

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        return $token;
    }
}
