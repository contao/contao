<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\CoreBundle\DataContainer\StartStopValidator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class StartStopValidatorTest extends TestCase
{
    /**
     * @param int|string $value
     *
     * @dataProvider getStartDates
     */
    public function testValidatesTheStartDate($value, string $dcValue, ?string $requestValue, ?string $stopValue, bool $exceptException): void
    {
        $request = new Request();

        if (null !== $requestValue) {
            $request->request->set('stop', $requestValue);
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->value = $dcValue;
        $dc->activeRecord = new \stdClass();

        if (null !== $stopValue) {
            $dc->activeRecord->stop = $stopValue;
        }

        $translator = $this->createMock(TranslatorInterface::class);

        if ($exceptException) {
            $translator
                ->expects($this->once())
                ->method('trans')
                ->with('ERR.startAfterStop', [], 'contao_default')
                ->willReturn('ERR.startAfterStop')
            ;
        }

        $validator = new StartStopValidator($requestStack, $translator);

        if ($exceptException) {
            $this->expectException('RuntimeException');
            $this->expectExceptionMessage('ERR.startAfterStop');
        }

        $this->assertSame($value, $validator->validateStartDate($value, $dc));
    }

    public function getStartDates(): \Generator
    {
        $yesterday = strtotime('yesterday');
        $today = strtotime('today');
        $tomorrow = strtotime('tomorrow');

        yield 'The field is empty' => [
            '',
            '',
            null,
            null,
            false,
        ];

        yield 'The field value equals the DC value' => [
            $today,
            (string) $today,
            null,
            null,
            false,
        ];

        yield 'The existing stop value is empty' => [
            $today,
            '',
            null,
            '',
            false,
        ];

        yield 'The new stop value is empty' => [
            $today,
            '',
            '',
            null,
            false,
        ];

        yield 'The existing stop time is after the start time' => [
            $today,
            '',
            null,
            (string) $tomorrow,
            false,
        ];

        yield 'The existing stop time is before the start time' => [
            $today,
            '',
            null,
            (string) $yesterday,
            true,
        ];

        yield 'The existing stop time equals the start time' => [
            $today,
            '',
            null,
            (string) $today,
            true,
        ];

        yield 'The new stop time is after the start time' => [
            $today,
            '',
            date('Y-m-d H:i', $tomorrow),
            null,
            false,
        ];

        yield 'The new stop time is before the start time' => [
            $today,
            '',
            date('Y-m-d H:i', $yesterday),
            null,
            true,
        ];

        yield 'The new stop time equals the start time' => [
            $today,
            '',
            date('Y-m-d H:i', $today),
            null,
            true,
        ];
    }

    public function testFailsToValidateTheStartDateIfThereIsNoRequest(): void
    {
        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->value = '';

        $translator = $this->createMock(TranslatorInterface::class);
        $validator = new StartStopValidator(new RequestStack(), $translator);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request stack did not contain a request');

        $validator->validateStartDate(strtotime('today'), $dc);
    }

    /**
     * @param int|string $value
     *
     * @dataProvider getStopDates
     */
    public function testValidatesTheStopDate($value, string $dcValue, ?string $requestValue, ?string $startValue, bool $exceptException): void
    {
        $request = new Request();

        if (null !== $requestValue) {
            $request->request->set('start', $requestValue);
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->value = $dcValue;
        $dc->activeRecord = new \stdClass();

        if (null !== $startValue) {
            $dc->activeRecord->start = $startValue;
        }

        $translator = $this->createMock(TranslatorInterface::class);

        if ($exceptException) {
            $translator
                ->expects($this->once())
                ->method('trans')
                ->with('ERR.stopBeforeStart', [], 'contao_default')
                ->willReturn('ERR.stopBeforeStart')
            ;
        }

        $validator = new StartStopValidator($requestStack, $translator);

        if ($exceptException) {
            $this->expectException('RuntimeException');
            $this->expectExceptionMessage('ERR.stopBeforeStart');
        }

        $this->assertSame($value, $validator->validateStopDate($value, $dc));
    }

    public function getStopDates(): \Generator
    {
        $yesterday = strtotime('yesterday');
        $today = strtotime('today');
        $tomorrow = strtotime('tomorrow');

        yield 'The field is empty' => [
            '',
            '',
            null,
            null,
            false,
        ];

        yield 'The field value equals the DC value' => [
            $today,
            (string) $today,
            null,
            null,
            false,
        ];

        yield 'The existing start value is empty' => [
            $today,
            '',
            null,
            '',
            false,
        ];

        yield 'The new start value is empty' => [
            $today,
            '',
            '',
            null,
            false,
        ];

        yield 'The existing start time is before the stop time' => [
            $today,
            '',
            null,
            (string) $yesterday,
            false,
        ];

        yield 'The existing start time is after the stop time' => [
            $today,
            '',
            null,
            (string) $tomorrow,
            true,
        ];

        yield 'The existing start time equals the stop time' => [
            $today,
            '',
            null,
            (string) $today,
            true,
        ];

        yield 'The new start time is before the stop time' => [
            $today,
            '',
            date('Y-m-d H:i', $yesterday),
            null,
            false,
        ];

        yield 'The new start time is after the stop time' => [
            $today,
            '',
            date('Y-m-d H:i', $tomorrow),
            null,
            true,
        ];

        yield 'The new start time equals the stop time' => [
            $today,
            '',
            date('Y-m-d H:i', $today),
            null,
            true,
        ];
    }

    public function testFailsToValidateTheStopDateIfThereIsNoRequest(): void
    {
        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->value = '';

        $translator = $this->createMock(TranslatorInterface::class);
        $validator = new StartStopValidator(new RequestStack(), $translator);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request stack did not contain a request');

        $validator->validateStopDate(strtotime('today'), $dc);
    }
}
