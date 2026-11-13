<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cron;

use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Cron\CronJob;
use Contao\CoreBundle\Fixtures\Cron\TestCronJob;
use Contao\CoreBundle\Tests\TestCase;

class CronJobTest extends TestCase
{
    public function testThrowsExceptionIfNoMethodIsGivenAndServiceIsNotInvokable(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CronJob(new TestCronJob(), '@hourly');
    }

    public function testUnexpectedReturnValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid return value from "Contao\CoreBundle\Fixtures\Cron\TestCronJob::wrongReturnValue": expected null or PromiseInterface, got int');

        $cronJob = new CronJob(new TestCronJob(), '@daily', 'wrongReturnValue');
        $cronJob(Cron::SCOPE_CLI);
    }

    public function testGetName(): void
    {
        $job = new CronJob(static fn () => null, '@daily', name: 'Example::job_%123');

        $this->assertSame('Example::job_%123', $job->getName());
    }

    public function testNormalizesNonAsciiNames(): void
    {
        $this->expectUserDeprecationMessage('Since contao/core-bundle 5.3: Using non-ASCII characters in cron job names is deprecated and will no longer work in Contao 7.');
        $job = new CronJob(static fn () => null, '@daily', name: 'täglich_😀');

        $this->assertSame('tc3a4glich_f09f9880', $job->getName());
    }

    public function testNormalizesDerivedNames(): void
    {
        $this->expectUserDeprecationMessage('Since contao/core-bundle 5.3: Using non-ASCII characters in cron job names is deprecated and will no longer work in Contao 7.');
        $service = new class {
            public function täglich(): void
            {
            }
        };
        $job = new CronJob($service, '@daily', 'täglich');

        $this->assertSame($service::class.'::tc3a4glich', $job->getName());
    }
}
