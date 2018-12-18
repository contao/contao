<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\OptIn;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\OptIn\OptInToken;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use Contao\Email;
use Contao\MemberModel;
use Contao\Model;
use Contao\OptInModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class OptInTokenTest extends ContaoTestCase
{
    public function testReturnsTheIdentifier(): void
    {
        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, ['token' => 'foobar']);
        $token = $this->getToken($model);

        $this->assertSame('foobar', $token->getIdentifier());
    }

    public function testConfirmsAToken(): void
    {
        $properties = [
            'createdOn' => time(),
            'confirmedOn' => 0,
        ];

        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, $properties);

        $token = $this->getToken($model);
        $token->confirm();

        $this->assertTrue($token->isConfirmed());
    }

    public function testDoesNotConfirmAConfirmedToken(): void
    {
        $properties = [
            'confirmedOn' => time(),
        ];

        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, $properties);
        $token = $this->getToken($model);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('The token has already been confirmed');

        $token->confirm();
    }

    public function testDoesNotConfirmAnExpiredToken(): void
    {
        $properties = [
            'createdOn' => strtotime('-1 day'),
            'confirmedOn' => 0,
        ];

        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, $properties);
        $token = $this->getToken($model);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('The token is no longer valid');

        $token->confirm();
    }

    public function testSendsATokenViaEmail(): void
    {
        $properties = [
            'createdOn' => time(),
            'confirmedOn' => 0,
            'emailSubject' => '',
            'emailText' => '',
        ];

        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, $properties);
        $model
            ->expects($this->once())
            ->method('save')
        ;

        $email = $this->createMock(Email::class);
        $email
            ->expects($this->once())
            ->method('sendTo')
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(Email::class)
            ->willReturn($email)
        ;

        $token = $this->getToken($model, $framework);
        $token->send('Subject', 'Text');

        $this->assertTrue($token->hasBeenSent());
    }

    public function testDoesNotSendAConfirmedTokenViaEmail(): void
    {
        $properties = [
            'confirmedOn' => time(),
        ];

        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, $properties);
        $token = $this->getToken($model);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('The token has already been confirmed');

        $token->send('Subject', 'Text');
    }

    public function testDoesNotSendAnExpiredTokenViaEmail(): void
    {
        $properties = [
            'createdOn' => strtotime('-1 day'),
            'confirmedOn' => 0,
        ];

        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, $properties);
        $token = $this->getToken($model);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('The token is no longer valid');

        $token->send('Subject', 'Text');
    }

    public function testRequiresSubjectAndTextToSendToken(): void
    {
        $properties = [
            'createdOn' => time(),
            'confirmedOn' => 0,
            'emailSubject' => '',
            'emailText' => '',
        ];

        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, $properties);
        $model
            ->expects($this->never())
            ->method('save')
        ;

        $token = $this->getToken($model);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Please provide subject and text to send the token');

        $token->send();
    }

    public function testDoesNotRequireSubjectAndTextToResendToken(): void
    {
        $properties = [
            'createdOn' => time(),
            'confirmedOn' => 0,
            'emailSubject' => 'Subject',
            'emailText' => 'Text',
        ];

        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, $properties);
        $model
            ->expects($this->never())
            ->method('save')
        ;

        $email = $this->createMock(Email::class);
        $email
            ->expects($this->once())
            ->method('sendTo')
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(Email::class)
            ->willReturn($email)
        ;

        $token = $this->getToken($model, $framework);
        $token->send('Subject', 'Text');

        $this->assertTrue($token->hasBeenSent());
    }

    public function testReturnsTheRelatedModel(): void
    {
        $properties = [
            'relatedTable' => 'tl_member',
            'relatedId' => 4,
        ];

        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, $properties);

        $adapter = $this->mockAdapter(['getClassFromTable']);
        $adapter
            ->expects($this->once())
            ->method('getClassFromTable')
            ->with('tl_member')
            ->willReturn(MemberModel::class)
        ;

        $member = $this->mockAdapter(['findByPk']);
        $member
            ->expects($this->once())
            ->method('findByPk')
            ->with(4)
            ->willReturn($model)
        ;

        $adapters = [
            Model::class => $adapter,
            MemberModel::class => $member,
        ];

        $token = $this->getToken($model, $this->mockContaoFramework($adapters));
        $related = $token->getRelatedModel();

        $this->assertSame($related, $model);
    }

    private function getToken(OptInModel $model, ContaoFrameworkInterface $framework = null): OptInTokenInterface
    {
        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        return new OptInToken($model, $framework);
    }
}
