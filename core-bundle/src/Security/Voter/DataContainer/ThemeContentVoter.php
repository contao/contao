<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class ThemeContentVoter extends AbstractDynamicPtableVoter
{
    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
    ) {
        parent::__construct($connection);
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccessToRecord(TokenInterface $token, string $table, int $id): bool
    {
        if ('tl_theme' !== $table) {
            return true;
        }

        return $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'themes')
            && $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_THEME_ELEMENTS]);
    }
}
