<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\MemberModel;
use Psr\Log\LoggerInterface;

class PurgeRegistrationsCron
{
    public function __construct(private ContaoFramework $framework, private LoggerInterface|null $logger)
    {
    }

    public function __invoke(): void
    {
        $this->framework->initialize();

        $members = $this->framework->getAdapter(MemberModel::class)->findExpiredRegistrations();

        if (null === $members) {
            return;
        }

        /** @var MemberModel $member */
        foreach ($members as $member) {
            $member->delete();
        }

        if (null !== $this->logger) {
            $this->logger->info('Purged the unactivated member registrations');
        }
    }
}
