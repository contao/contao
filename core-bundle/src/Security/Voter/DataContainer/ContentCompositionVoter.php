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
        return ContaoCorePermissions::DC_PREFIX.'tl_article' == $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, UpdateAction::class], true);
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if (
                (!$subject instanceof CreateAction && !$subject instanceof UpdateAction)
                || !$this->supportsAttribute($attribute)
                || !$subject->getNewPid()
            ) {
                continue;
            }

            if (!$this->supportsContentComposition((int) $subject->getNewPid())) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    private function supportsContentComposition(int $pageId): bool
    {
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $pageModel = $pageAdapter->findByPk($pageId);

        if (!$pageModel || !$this->pageRegistry->supportsContentComposition($pageModel)) {
            return false;
        }

        $pageModel->loadDetails();

        $layout = $pageModel->getRelated('layout');

        if (!$layout instanceof LayoutModel) {
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
