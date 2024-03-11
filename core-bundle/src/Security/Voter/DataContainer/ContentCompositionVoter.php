<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 */
class ContentCompositionVoter implements VoterInterface, CacheableVoterInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly PageRegistry $pageRegistry,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return ContaoCorePermissions::DC_PREFIX.'tl_article' === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, UpdateAction::class], true);
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        if ((!$subject instanceof CreateAction && !$subject instanceof UpdateAction) || !$subject->getNewPid()) {
            return self::ACCESS_ABSTAIN;
        }

        if (!array_filter($attributes, $this->supportsAttribute(...))) {
            return self::ACCESS_ABSTAIN;
        }

        if (!$this->supportsContentComposition((int) $subject->getNewPid())) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function supportsContentComposition(int $pageId): bool
    {
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $pageModel = $pageAdapter->findById($pageId);

        if (!$pageModel || !$this->pageRegistry->supportsContentComposition($pageModel)) {
            return false;
        }

        $pageModel->loadDetails();

        if (!$layout = $this->framework->getAdapter(LayoutModel::class)->findById($pageModel->layout)) {
            return false;
        }

        foreach (StringUtil::deserialize($layout->modules, true) as $config) {
            if (0 === (int) $config['mod']) {
                return true;
            }
        }

        return false;
    }
}
