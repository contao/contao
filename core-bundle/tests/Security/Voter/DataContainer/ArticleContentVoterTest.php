<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Voter\DataContainer;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\ArticleContentVoter;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class ArticleContentVoterTest extends TestCase
{
    public function testSupportsAttributesAndTypes(): void
    {
        $voter = new ArticleContentVoter($this->createMock(AccessDecisionManagerInterface::class), $this->createMock(Connection::class));

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_content'));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsAttribute('foobar'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_page'));
    }

    /**
     * @dataProvider checksElementAccessPermissionProvider
     */
    public function testChecksElementAccessPermission(CreateAction|DeleteAction|ReadAction|UpdateAction $action, array $pageIds): void
    {
        $token = $this->createMock(TokenInterface::class);

        $accessDecisionMatcher = $this->exactly(\count($pageIds));
        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($accessDecisionMatcher)
            ->method('decide')
            //->with($token, [ContaoCorePermissions::USER_CAN_ACCESS_ELEMENT_TYPE], $this->callback(function (string $type) use ($accessDecisionMatcher, $types) {
            //    return $types[$accessDecisionMatcher->getInvocationCount() - 1] === $type;
            //}))
            ->willReturn(true)
        ;

        $articleIds = array_keys($pageIds);
        $connectionMatcher = $this->exactly(\count($pageIds));
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($connectionMatcher)
            ->method('fetchOne')
            ->with('SELECT pid FROM tl_article WHERE id=?', $this->callback(static fn (int $articleId) => $articleIds[$connectionMatcher->getInvocationCount()] === $articleId))
            ->willReturnCallback(static fn (string $query, int $articleId) => $pageIds[$articleId])
        ;

        $voter = new ArticleContentVoter($accessDecisionManager, $connection);
        $voter->vote($token, $action, [ContaoCorePermissions::DC_PREFIX.'tl_content']);
    }

    public static function checksElementAccessPermissionProvider(): iterable
    {
        yield [
            new ReadAction('tl_content', []),
            []
        ];

        //yield [
        //    new CreateAction('tl_content', ['type' => 'foo']),
        //    ['foo']
        //];
        //
        //yield [
        //    new UpdateAction('tl_content', ['type' => 'foo']),
        //    ['foo']
        //];
        //
        //yield [
        //    new UpdateAction('tl_content', ['type' => 'foo'], ['type' => 'bar']),
        //    ['foo', 'bar']
        //];
        //
        //yield [
        //    new DeleteAction('tl_content', ['type' => 'bar']),
        //    ['bar']
        //];
    }
}
