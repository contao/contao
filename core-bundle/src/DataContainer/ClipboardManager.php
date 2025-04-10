<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
class ClipboardManager
{
    public const MODE_CREATE = 'create';

    public const MODE_CUT = 'cut';

    public const MODE_CUT_ALL = 'cutAll';

    public const MODE_COPY = 'copy';

    public const MODE_COPY_ALL = 'copyAll';

    private const SESSION_KEY = 'CLIPBOARD';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function get(string $table): array|null
    {
        $session = $this->requestStack->getSession();
        $clipboard = $session->get(self::SESSION_KEY);

        if (empty($clipboard[$table])) {
            return null;
        }

        return $clipboard[$table];
    }

    public function set(string $table, int|string|null $id, string|null $children, string $mode): void
    {
        $session = $this->requestStack->getSession();
        $clipboard = $session->get(self::SESSION_KEY);

        $clipboard[$table] = [
            'id' => $id,
            'childs' => $children, // backwards compatibility
            'children' => $children,
            'mode' => $mode,
        ];

        $session->set(self::SESSION_KEY, $clipboard);
    }

    public function setIds(string $table, array $ids, string $mode, bool $keep): void
    {
        $session = $this->requestStack->getSession();
        $clipboard = $session->get(self::SESSION_KEY);

        $clipboard[$table] = [
            'id' => $ids,
            'mode' => $mode,
            'keep' => $keep,
        ];

        $session->set(self::SESSION_KEY, $clipboard);
    }

    public function getIds(string $table): array
    {
        $session = $this->requestStack->getSession();
        $clipboard = $session->get(self::SESSION_KEY);

        if (!isset($clipboard[$table]) || !\is_array($clipboard[$table]['id'])) {
            return [];
        }

        return $clipboard[$table]['id'];
    }

    public function clearIfNotKeep(string $table): void
    {
        $session = $this->requestStack->getSession();
        $clipboard = $session->get(self::SESSION_KEY);

        if (!($clipboard[$table]['keep'] ?? false)) {
            $clipboard[$table] = [];
            $session->set(self::SESSION_KEY, $clipboard);
        }
    }

    public function clear(string $table): void
    {
        $session = $this->requestStack->getSession();
        $clipboard = $session->get(self::SESSION_KEY);
        $clipboard[$table] = [];
        $session->set(self::SESSION_KEY, $clipboard);
    }

    public function clearAll(): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_KEY, []);
    }

    public function isCutMode(string $table): bool
    {
        $clipboard = $this->get($table);

        return $clipboard && (self::MODE_CUT === $clipboard['mode'] || self::MODE_CUT_ALL === $clipboard['mode']);
    }

    public function isCircularReference(string $table, int|string $id, bool $loadChildRecords = false): bool
    {
        // Only tree mode can have circular references. In extended tree, nodes (e.g. articles) only have one level.
        if (($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? null) !== DataContainer::MODE_TREE) {
            return false;
        }

        $clipboard = $this->get($table);

        if (null === $clipboard) {
            return false;
        }

        if ($id == $clipboard['id'] || (\is_array($clipboard['id']) && \in_array($id, $clipboard['id'], false))) {
            return true;
        }

        if (!$loadChildRecords) {
            return false;
        }

        $db = $this->framework->createInstance(Database::class);
        $childrenOfClipboard = $db->getChildRecords($clipboard['id'], $table);

        return \in_array($id, $childrenOfClipboard, false);
    }

    public function canPasteAfterOrInto(string $table, int|string $id): bool
    {
        $clipboard = $this->get($table);

        if (null === $clipboard) {
            return false;
        }

        return !(self::MODE_CUT === $clipboard['mode'] && $id == $clipboard['id']) && !(\is_array($clipboard['id']) && self::MODE_CUT_ALL === $clipboard['mode'] && \in_array($id, $clipboard['id'], false));
    }
}
