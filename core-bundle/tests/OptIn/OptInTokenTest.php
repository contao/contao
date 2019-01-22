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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\OptIn\OptInToken;
use Contao\CoreBundle\OptIn\OptInTokenAlreadyConfirmedException;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use Contao\CoreBundle\OptIn\OptInTokenNoLongerValidException;
use Contao\Email;
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

    public function testReturnsTheEmailAddress(): void
    {
        /** @var OptInModel|MockObject $model */
        $model = $this->mockClassWithGetterSetter(OptInModel::class, ['email' => 'foo@bar.com']);
        $token = $this->getToken($model);

        $this->assertSame('foo@bar.com', $token->getEmail());
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

        $this->expectException(OptInTokenAlreadyConfirmedException::class);
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

        $this->expectException(OptInTokenNoLongerValidException::class);
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

        $this->expectException(OptInTokenAlreadyConfirmedException::class);
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

        $this->expectException(OptInTokenNoLongerValidException::class);
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

    private function getToken(OptInModel $model, ContaoFramework $framework = null): OptInTokenInterface
    {
        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        return new OptInToken($model, $framework);
    }
}
