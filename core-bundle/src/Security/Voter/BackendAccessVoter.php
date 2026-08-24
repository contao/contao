<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\Doctrine\DBAL\ParentTraversalOptions;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Contracts\Service\ResetInterface;

class BackendAccessVoter extends AbstractBackendAccessVoter implements ResetInterface
{
    private const PAGE_PERMISSIONS = [
        'can_edit_page' => 1,
        'can_edit_page_hierarchy' => 2,
        'can_delete_page' => 3,
        'can_edit_articles' => 4,
        'can_edit_article_hierarchy' => 5,
        'can_delete_articles' => 6,
    ];

    private array $pagePermissionsCache = [];

    private array $pagemountsCache = [];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly DcaHierarchy $hierarchy,
    ) {
    }

    public function reset(): void
    {
        $this->pagePermissionsCache = [];
        $this->pagemountsCache = [];
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, 'contao_user.');
    }

    protected function checkAccess(mixed $subject, string $field, BackendUser $user): bool
    {
        if ('can_edit_fields' === $field) {
            return $this->canEditFieldsOf($subject, $user);
        }

        if (isset(self::PAGE_PERMISSIONS[$field])) {
            return $this->isAllowed($subject, self::PAGE_PERMISSIONS[$field], $user);
        }

        return parent::checkAccess($subject, $field, $user);
    }

    /**
     * Checks the user permissions against a field in tl_user(_group).
     */
    protected function hasAccess(array|null $subject, string $field, BackendUser $user): bool
    {
        if (null === $subject) {
            return \is_array($user->$field) && [] !== $user->$field;
        }

        if (\is_array($user->$field) && array_intersect($subject, $user->$field)) {
            return true;
        }

        // Additionally check the subfolders of the mounted files
        if ('filemounts' === $field) {
            return array_any($user->filemounts, static fn ($folder) => preg_match('/^'.preg_quote($folder, '/').'(\/|$)/i', $subject[0]));
        }

        // Additionally check the child pages of the mounted pages
        if ('pagemounts' === $field) {
            if (!isset($this->pagemountsCache[$user->id]) || (!empty($this->pagemountsCache[$user->id]) && !array_intersect($subject, $this->pagemountsCache[$user->id]))) {
                $this->pagemountsCache[$user->id] = $this->hierarchy->getChildIds($user->pagemounts, 'tl_page');
            }

            return !empty($this->pagemountsCache[$user->id]) && array_intersect($subject, $this->pagemountsCache[$user->id]);
        }

        // Additionally check the "disablePermissionChecks" flag for modules
        if ('modules' === $field) {
            foreach ($subject as $module) {
                foreach ($GLOBALS['BE_MOD'] as $modules) {
                    if ($modules[$module]['disablePermissionChecks'] ?? false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks if the user has access to a given page (tl_page.includeChmod et al.).
     */
    private function isAllowed(mixed $subject, int $flag, BackendUser $user): bool
    {
        if ($subject instanceof PageModel) {
            $subject = $subject->row();
        }

        if (!\is_array($subject)) {
            $page = $this->framework->getAdapter(PageModel::class)->findById($subject);

            if (!$page instanceof PageModel) {
                return false;
            }

            $subject = $page->row();
        }

        [$cuser, $cgroup, $chmod] = $this->getPagePermissions($subject);

        $permission = ['w'.$flag];

        if (\in_array($cgroup, $user->groups, false)) {
            $permission[] = 'g'.$flag;
        }

        if ($cuser === $user->id) {
            $permission[] = 'u'.$flag;
        }

        return [] !== array_intersect($permission, $chmod);
    }

    /**
     * Checks if the user has access to any field of a table (against
     * tl_user(_group).alexf).
     */
    private function canEditFieldsOf(mixed $table, BackendUser $user): bool
    {
        if (!\is_string($table)) {
            return false;
        }

        return \count(preg_grep('/^'.preg_quote($table, '/').'::/', $user->alexf)) > 0;
    }

    private function getPagePermissions(array $row): array
    {
        if (isset($row['id'], $this->pagePermissionsCache[$row['id']])) {
            return $this->pagePermissionsCache[$row['id']];
        }

        $cacheIds = [];

        if (isset($row['id'])) {
            $cacheIds[] = (int) $row['id'];
        }

        if (!($row['includeChmod'] ?? false)) {
            [$permissions, $parentIds] = $this->getInheritedPagePermissions((int) ($row['pid'] ?? 0));
            $row = [...$row, ...$permissions];
            $cacheIds = [...$cacheIds, ...$parentIds];
        }

        $result = [(int) ($row['cuser'] ?? null), (int) ($row['cgroup'] ?? null), StringUtil::deserialize($row['chmod'] ?? null, true)];

        foreach ($cacheIds as $cacheId) {
            $this->pagePermissionsCache[$cacheId] = $result;
        }

        return $result;
    }

    /**
     * @return array{array{chmod: mixed, cuser: mixed, cgroup: mixed}, list<int>}
     */
    private function getInheritedPagePermissions(int $pid): array
    {
        $options = new ParentTraversalOptions()->withColumns('includeChmod', 'chmod', 'cuser', 'cgroup');
        $parentIds = [];

        foreach ($this->hierarchy->getParentRows($pid, 'tl_page', $options) as $parentPage) {
            $parentIds[] = (int) $parentPage['id'];

            if ($parentPage['includeChmod']) {
                return [array_intersect_key($parentPage, array_flip(['chmod', 'cuser', 'cgroup'])), $parentIds];
            }
        }

        // Set default values
        $config = $this->framework->getAdapter(Config::class);

        return [[
            'chmod' => $config->get('defaultChmod'),
            'cuser' => (int) $config->get('defaultUser'),
            'cgroup' => (int) $config->get('defaultGroup'),
        ], $parentIds];
    }
}
