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

use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDynamicPtableVoter;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AbstractDynamicPtableVoterTest extends TestCase
{
    public function testSupportsTheLegacyConnectionConstructorArgument(): void
    {
        $this->expectUserDeprecationMessage(
            'Since contao/core-bundle 6.1: Passing an instance of "Doctrine\DBAL\Connection" to "Contao\CoreBundle\Security\Voter\DataContainer\AbstractDynamicPtableVoter::__construct()" is deprecated and will no longer work in Contao 7. Pass an instance of "Contao\CoreBundle\DataContainer\DcaHierarchy" instead.',
        );

        $voter = new class($this->createStub(Connection::class)) extends AbstractDynamicPtableVoter {
            protected function getTable(): string
            {
                return 'tl_content';
            }

            protected function hasAccessToRecord(TokenInterface $token, string $table, int $id): bool
            {
                return true;
            }
        };

        $this->assertTrue($voter->supportsAttribute('contao_dc.tl_content'));
    }
}
