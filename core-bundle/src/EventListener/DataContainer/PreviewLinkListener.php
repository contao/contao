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

use Contao\BackendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\UserModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @internal
 */
class PreviewLinkListener
{
    private ContaoFramework $framework;
    private Connection $connection;
    private Security $security;
    private RequestStack $requestStack;
    private UrlGeneratorInterface $urlGenerator;
    private UriSigner $uriSigner;
    private string $previewScript;

    public function __construct(ContaoFramework $framework, Connection $connection, Security $security, RequestStack $requestStack, UrlGeneratorInterface $urlGenerator, UriSigner $uriSigner, string $previewScript = '')
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->uriSigner = $uriSigner;
        $this->previewScript = $previewScript;
    }

    /**
     * @Hook("initializeSystem")
     */
    public function unloadModuleWithoutPreviewScript(): void
    {
        if (empty($this->previewScript)) {
            unset($GLOBALS['BE_MOD']['system']['preview_link']);
        }
    }

    /**
     * @Hook("loadDataContainer")
     */
    public function unloadTableWithoutPreviewScript(string $table): void
    {
        if ('tl_preview_link' === $table && empty($this->previewScript)) {
            unset($GLOBALS['TL_DCA'][$table]);
        }
    }

    /**
     * Only allow to create new records if a front end preview URL is given.
     *
     * @Callback(table="tl_preview_link", target="config.onload")
     */
    public function createFromUrl(DataContainer $dc): void
    {
        $input = $this->framework->getAdapter(Input::class);
        $user = $this->security->getUser();
        $userId = $user instanceof BackendUser ? $user->id : 0;

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $GLOBALS['TL_DCA']['tl_preview_link']['list']['sorting']['filter'][] = ['createdBy=?', $userId];

            // Check the current action
            switch ((string) $input->get('act')) {
                case '': // empty
                case 'paste':
                case 'create':
                case 'select':
                    break;

                case 'editAll':
                case 'deleteAll':
                case 'overrideAll':
                    $allowedIds = $this->connection->fetchFirstColumn(
                        'SELECT id FROM tl_preview_link WHERE createdBy=?',
                        [$userId]
                    );

                    $session = $this->requestStack->getSession();
                    $sessionData = $session->all();
                    $sessionData['CURRENT']['IDS'] = array_intersect((array) $sessionData['CURRENT']['IDS'], $allowedIds);
                    $session->replace($sessionData);
                    break;

                case 'edit':
                case 'toggle':
                case 'delete':
                default:
                    if ($dc->activeRecord->createdBy !== $userId) {
                        throw new AccessDeniedException(sprintf('Preview link ID %s was not created by user ID %s', $dc->id, $userId));
                    }
                    break;
            }
        }

        // Only allow creating new records from front end link with preview script in URL
        if ('create' === $input->get('act') && false !== strpos($input->get('url') ?? '', $this->previewScript)) {
            $GLOBALS['TL_DCA']['tl_preview_link']['config']['notCreatable'] = false;
        }

        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['url']['default'] = (string) $input->get('url');
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['showUnpublished']['default'] = $input->get('showUnpublished') ? '1' : '';
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['createdAt']['default'] = time();
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['expiresAt']['default'] = strtotime('+1 day');
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['createdBy']['default'] = $userId;
    }

    /**
     * Updates tl_preview_link.expiresAt based on expiresInDays selection.
     *
     * @Callback(table="tl_preview_link", target="config.onsubmit")
     */
    public function updateExpiresAt(DataContainer $dc): void
    {
        $this->connection->executeStatement(
            'UPDATE tl_preview_link SET expiresAt=UNIX_TIMESTAMP(DATE_ADD(FROM_UNIXTIME(createdAt), INTERVAL expiresInDays DAY)) WHERE id=?',
            [$dc->id]
        );
    }

    /**
     * @Callback(table="tl_preview_link", target="list.label.label")
     */
    public function formatColumnView(array $row, string $label, DataContainer $dc, array $args): array
    {
        foreach ($GLOBALS['TL_DCA'][$dc->table]['list']['label']['fields'] as $i => $field) {
            switch ($field) {
                case 'url':
                    $args[$i] = $row['expiresAt'] < time() ? sprintf('<span style="text-decoration:line-through">%s</span>', $args[$i]) : $args[$i];
                    break;

                case 'expiresAt':
                    $args[$i] = $row['expiresAt'] < time() ? sprintf('<span style="color:#f00">%s</span>', $args[$i]) : $args[$i];
                    break;

                case 'createdBy':
                    $user = $this->framework->getAdapter(UserModel::class);
                    $args[$i] = $user->findByPk($row[$field])->name ?? $args[$i];
                    break;
            }
        }

        return $args;
    }

    /**
     * @Callback(table="tl_preview_link", target="list.operations.share.button")
     */
    public function shareOperation(array $row, ?string $href, ?string $label, ?string $title, string $icon): string
    {
        $url = $this->urlGenerator->generate('contao_preview_link', ['id' => $row['id']], UrlGeneratorInterface::ABSOLUTE_URL);

        return sprintf(
            '<a href="%s" target="_blank" title="%s" onclick="navigator.clipboard.writeText(this.href);return false">%s</a> ',
            $this->uriSigner->sign($url),
            StringUtil::specialchars($title),
            Image::getHtml($icon, $label)
        );
    }
}
