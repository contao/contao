<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Image;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Service\ResetInterface;

class CteAliasListener implements ResetInterface
{
    private static ?array $cteAliasCache = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly Connection $db,
    ) {
    }

    /**
     * Prevent deleting referenced elements (see #4898).
     */
    #[AsCallback('tl_content', 'config.onload')]
    public function preserveReferenced(): void
    {
        $aliasRefs = $this->getAliasReferences();
        $request = $this->requestStack->getCurrentRequest();
        $id = $request->query->get('id');

        if ($id && 'delete' === $request->query->get('act') && isset($aliasRefs[$id])) {
            throw new InternalServerErrorException('Content element ID '.$id.' is used in an alias element and can therefore not be deleted.');
        }

        if ('deleteAll' === $request->query->get('act')) {
            $session = $request->getSession();
            $sessionData = $session->all();
            $sessionData['CURRENT']['IDS'] = array_diff($sessionData['CURRENT']['IDS'], array_map('intval', array_keys($aliasRefs)));
            $session->replace($sessionData);
        }
    }

    /**
     * Return the delete content element button.
     */
    #[AsCallback('tl_content', 'list.operations.delete.button')]
    public function deleteElement(array $row, ?string $href, string $label, string $title, ?string $icon, string $attributes): string
    {
        $permission = ContaoCorePermissions::USER_CAN_ACCESS_ELEMENT_TYPE;

        // Disable the button if the element type is not allowed or the element is referenced
        if (!$this->security->isGranted($permission, $row['type']) || isset($this->getAliasReferences()[(int) $row['id']])) {
            return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
        }

        return '<a href="'.Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    public function reset(): void
    {
        self::$cteAliasCache = null;
    }

    private function getAliasReferences(): array
    {
        if (null === self::$cteAliasCache) {
            self::$cteAliasCache = $this->db->fetchAllKeyValue("SELECT cteAlias, TRUE FROM tl_content WHERE type='alias' GROUP BY cteAlias");
        }

        return self::$cteAliasCache;
    }
}
