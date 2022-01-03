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
use Contao\BackendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\UserModel;
use Contao\Versions;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class PreviewLinkListener
{
    private ContaoFramework $framework;
    private Connection $connection;
    private Security $security;
    private UrlGeneratorInterface $urlGenerator;
    private UriSigner $uriSigner;
    private TranslatorInterface $translator;
    private string $previewScript;

    public function __construct(ContaoFramework $framework, Connection $connection, Security $security, UrlGeneratorInterface $urlGenerator, UriSigner $uriSigner, TranslatorInterface $translator, string $previewScript = '')
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
        $this->uriSigner = $uriSigner;
        $this->translator = $translator;
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
        $userId = ($user = $this->security->getUser()) instanceof BackendUser ? $user->id : 0;

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $GLOBALS['TL_DCA']['tl_preview_link']['list']['sorting']['filter'][] = ['createdBy=?', $userId];

            // TODO: more permission checks
        }

        // Only allow creating new records from front end link with preview script in URL
        if ('create' === $input->get('act') && false !== strpos($input->get('url'), $this->previewScript)) {
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
                    $args[$i] = UserModel::findByPk($row[$field])->name;
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
            '<a href="%s" target="_blank" title="%s" onclick="navigator.clipboard.writeText(this.href) && alert(\'%s\');return false">%s</a> ',
            $this->uriSigner->sign($url),
            StringUtil::specialchars($title),
            StringUtil::specialchars($this->translator->trans('tl_preview_link.clipboard', [], 'contao_tl_preview_link')),
            Image::getHtml($icon, $label)
        );
    }

    /**
     * @Callback(table="tl_preview_link", target="list.operations.toggle.button")
     */
    public function togglePublished(array $row, ?string $href, ?string $label, ?string $title, string $icon, ?string $attributes): string
    {
        if (Input::get('tid')) {
            $this->toggleVisibility((int) Input::get('tid'), '1' === (string) Input::get('state'), (\func_num_args() <= 12 ? null : func_get_arg(12)));

            throw new RedirectResponseException(System::getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_preview_link::published')) {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.$row['published'];

        if (!$row['published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.Backend::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="'.($row['published'] ? 1 : 0).'"').'</a> ';
    }

    private function toggleVisibility(int $intId, bool $blnPublished, DataContainer $dc = null): void
    {
        // Set the ID and action
        Input::setGet('id', $intId);
        Input::setGet('act', 'toggle');

        if ($dc) {
            $dc->id = $intId; // see #8043
        }

        // Trigger the onload_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_preview_link']['config']['onload_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA']['tl_preview_link']['config']['onload_callback'] as $callback) {
                if (\is_array($callback)) {
                    System::importStatic($callback[0])->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        // Check the field access
        if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_preview_link::published')) {
            throw new AccessDeniedException('Not enough permissions to publish/unpublish preview link ID '.$intId.'.');
        }

        $row = $this->connection->fetchAssociative('SELECT * FROM tl_preview_link WHERE id=?', [$intId]);

        if (false === $row) {
            throw new AccessDeniedException('Invalid preview link ID '.$intId.'.');
        }

        // Set the current record
        if ($dc) {
            $dc->activeRecord = (object) $row;
        }

        $objVersions = new Versions('tl_preview_link', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_preview_link']['fields']['published']['save_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA']['tl_preview_link']['fields']['published']['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $blnPublished = System::importStatic($callback[0])->{$callback[1]}($blnPublished, $dc);
                } elseif (\is_callable($callback)) {
                    $blnPublished = $callback($blnPublished, $dc);
                }
            }
        }

        $time = time();

        // Update the database
        $this->connection->executeStatement(
            'UPDATE tl_preview_link SET tstamp=?, published=? WHERE id=?',
            [$time, ($blnPublished ? '1' : ''), $intId]
        );

        if ($dc) {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnPublished ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_preview_link']['config']['onsubmit_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA']['tl_preview_link']['config']['onsubmit_callback'] as $callback) {
                if (\is_array($callback)) {
                    System::importStatic($callback[0])->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        $objVersions->create();

        if ($dc) {
            $dc->invalidateCacheTags();
        }
    }
}
