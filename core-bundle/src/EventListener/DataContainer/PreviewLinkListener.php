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
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class PreviewLinkListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UriSigner $uriSigner,
        private readonly string $previewScript = '',
    ) {
    }

    #[AsHook('initializeSystem')]
    public function unloadModuleWithoutPreviewScript(): void
    {
        if (!$this->previewScript) {
            unset($GLOBALS['BE_MOD']['system']['preview_link']);
        }
    }

    #[AsHook('loadDataContainer')]
    public function unloadTableWithoutPreviewScript(string $table): void
    {
        if ('tl_preview_link' === $table && !$this->previewScript) {
            unset($GLOBALS['TL_DCA'][$table]);
        }
    }

    /**
     * Only allow to create new records if a front end preview URL is given.
     */
    #[AsCallback(table: 'tl_preview_link', target: 'config.onload')]
    public function createFromUrl(DataContainer $dc): void
    {
        $input = $this->framework->getAdapter(Input::class);
        $message = $this->framework->getAdapter(Message::class);
        $user = $this->security->getUser();
        $userId = $user instanceof BackendUser ? (int) $user->id : 0;

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
                        [$userId],
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
                    $createdBy = (int) $this->connection->fetchOne('SELECT createdBy FROM tl_preview_link WHERE id=?', [$dc->id]);

                    if ($createdBy !== $userId) {
                        throw new AccessDeniedException(\sprintf('Preview link ID %s was not created by user ID %s', $dc->id, $userId));
                    }
                    break;
            }
        }

        if (!$input->get('act')) {
            $message->addInfo($this->translator->trans('tl_preview_link.hintNew', [], 'contao_tl_preview_link'));
        } elseif ('create' === $input->get('act') && str_contains((string) ($input->get('url') ?? ''), $this->previewScript)) {
            // Only allow creating new records from front end link with preview script in URL
            $GLOBALS['TL_DCA']['tl_preview_link']['config']['notCreatable'] = false;
        }

        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['url']['default'] = (string) $input->get('url');
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['showUnpublished']['default'] = (bool) $input->get('showUnpublished');
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['createdAt']['default'] = time();
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['expiresAt']['default'] = strtotime('+1 day');
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['createdBy']['default'] = $userId;
    }

    /**
     * Adds a hint and modifies the edit view buttons.
     */
    #[AsCallback(table: 'tl_preview_link', target: 'config.onload')]
    public function adjustEditView(DataContainer $dc): void
    {
        $input = $this->framework->getAdapter(Input::class);
        $message = $this->framework->getAdapter(Message::class);

        if ('edit' !== $input->get('act')) {
            return;
        }

        $row = $this->connection->fetchAssociative('SELECT * FROM tl_preview_link WHERE id=?', [$dc->id]);

        if ($row['expiresAt'] < time()) {
            $message->addError($this->translator->trans('tl_preview_link.hintExpired', [], 'contao_tl_preview_link'));
        } elseif (0 === (int) $row['tstamp']) {
            $message->addNew($this->translator->trans('tl_preview_link.hintSave', [], 'contao_tl_preview_link'));
        } else {
            $message->addInfo(\sprintf(
                '%s: %s',
                $this->translator->trans('tl_preview_link.hintEdit', [], 'contao_tl_preview_link'),
                $this->generateClipboardLink((int) $row['id']),
            ));
        }
    }

    #[AsCallback(table: 'tl_preview_link', target: 'list.label.label')]
    public function formatColumnView(array $row, string $label, DataContainer $dc, array $args): array
    {
        if ($row['expiresAt'] < time()) {
            foreach ($args as &$arg) {
                $arg = \sprintf('<span class="tl_gray">%s</span>', $arg);
            }

            unset($arg);
        }

        return $args;
    }

    #[AsCallback(table: 'tl_preview_link', target: 'list.operations.share.button')]
    public function shareOperation(array $row, string|null $href, string|null $label, string|null $title, string $icon): string
    {
        if ($row['expiresAt'] < time()) {
            return Image::getHtml(str_replace('.svg', '--disabled.svg', $icon), $label);
        }

        return $this->generateClipboardLink((int) $row['id'], Image::getHtml($icon, $label), $title);
    }

    private function generateClipboardLink(int $id, string|null $label = null, string|null $title = null): string
    {
        $url = $this->urlGenerator->generate('contao_preview_link', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);
        $url = $this->uriSigner->sign($url);

        $title ??= $this->translator->trans('tl_preview_link.share.0', [], 'contao_tl_preview_link');

        return \sprintf(
            '<a href="%s" target="_blank" title="%s" data-controller="contao--clipboard" data-contao--clipboard-content-value="%s" data-action="contao--clipboard#write:prevent">%s</a> ',
            StringUtil::specialcharsUrl($url),
            StringUtil::specialchars($title),
            StringUtil::specialcharsUrl($url),
            $label ?? $url,
        );
    }
}
