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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Contracts\Service\ResetInterface;

class BackendAccessVoter extends Voter implements ResetInterface
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

    public function __construct(private readonly ContaoFramework $framework)
    {
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

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return false;
        }

        $permission = explode('.', $attribute, 3);

        if ('contao_user' !== $permission[0] || !isset($permission[1])) {
            return false;
        }

        if ($user->isAdmin) {
            return true;
        }

        $field = $permission[1];

        if (!$subject && isset($permission[2])) {
            $subject = $permission[2];
        }

        if ('can_edit_fields' === $field) {
            return $this->canEditFieldsOf($subject, $user);
        }

        if (isset(self::PAGE_PERMISSIONS[$field])) {
            return $this->isAllowed($subject, self::PAGE_PERMISSIONS[$field], $user);
        }

        return $this->hasAccess($subject, $field, $user);
    }

    /**
     * Checks the user permissions against a field in tl_user(_group).
     */
    private function hasAccess(mixed $subject, string $field, BackendUser $user): bool
    {
        if (null === $subject) {
            return \is_array($user->$field) && [] !== $user->$field;
        }

        if (!\is_scalar($subject) && !\is_array($subject)) {
            return false;
        }

        if (!\is_array($subject)) {
            $subject = [$subject];
        }

        if (\is_array($user->$field) && array_intersect($subject, $user->$field)) {
            return true;
        }

        // Additionally check the subfolders of the mounted files
        if ('filemounts' === $field) {
            foreach ($user->filemounts as $folder) {
                if (preg_match('/^'.preg_quote($folder, '/').'(\/|$)/i', $subject[0])) {
                    return true;
                }
            }

            return false;
        }

        // Additionally check the child pages of the mounted pages
        if ('pagemounts' === $field) {
            if (!isset($this->pagemountsCache[$user->id])) {
                $database = $this->framework->createInstance(Database::class);
                $this->pagemountsCache[$user->id] = $database->getChildRecords($user->pagemounts, 'tl_page');
            }

            return !empty($this->pagemountsCache[$user->id]) && array_intersect($subject, $this->pagemountsCache[$user->id]);
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
            $page = $this->framework->getAdapter(PageModel::class)->findByPk($subject);

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
     * Checks if the user has access to any field of a table (against tl_user(_group).alexf).
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
            $pid = $row['pid'] ?? null;

            $row['chmod'] = false;
            $row['cuser'] = false;
            $row['cgroup'] = false;

            $parentPage = $this->framework->getAdapter(PageModel::class)->findById($pid);

            while ($parentPage && false === $row['chmod'] && $pid > 0) {
                $cacheIds[] = $parentPage->id;
                $pid = $parentPage->pid;

                $row['chmod'] = $parentPage->includeChmod ? $parentPage->chmod : false;
                $row['cuser'] = $parentPage->includeChmod ? $parentPage->cuser : false;
                $row['cgroup'] = $parentPage->includeChmod ? $parentPage->cgroup : false;

                $parentPage = $this->framework->getAdapter(PageModel::class)->findById($pid);
            }

            // Set default values
            if (false === $row['chmod']) {
                $config = $this->framework->getAdapter(Config::class);

                $row['chmod'] = $config->get('defaultChmod');
                $row['cuser'] = (int) $config->get('defaultUser');
                $row['cgroup'] = (int) $config->get('defaultGroup');
            }
        }

        $result = [(int) ($row['cuser'] ?? null), (int) ($row['cgroup'] ?? null), StringUtil::deserialize($row['chmod'] ?? null, true)];

        foreach ($cacheIds as $cacheId) {
            $this->pagePermissionsCache[$cacheId] = $result;
        }

        return $result;
    }
}
