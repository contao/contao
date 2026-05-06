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
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
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
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UriSigner $uriSigner,
        private readonly ClockInterface $clock,
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

    #[AsHook('loadDataContainer')]
    public function allowUndo(string $table): void
    {
        if ('tl_preview_link' === $table && 'undo' === $this->requestStack->getCurrentRequest()?->query->get('do')) {
            $GLOBALS['TL_DCA']['tl_preview_link']['config']['notCreatable'] = false;
        }
    }

    /**
     * Only allow to create new records if a front end preview URL is given.
     */
    #[AsCallback(table: 'tl_preview_link', target: 'config.onload')]
    public function createFromUrl(DataContainer $dc): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $user = $this->security->getUser();
        $act = $request->query->get('act');
        $url = $request->query->getString('url');
        $now = $this->clock->now();

        if (!$act) {
            $message = $this->framework->getAdapter(Message::class);
            $message->addInfo($this->translator->trans('tl_preview_link.hintNew', [], 'contao_tl_preview_link'));
        } elseif ('create' === $act && str_contains($url, $this->previewScript)) {
            // Only allow creating new records from front end link with preview script in URL
            $GLOBALS['TL_DCA']['tl_preview_link']['config']['notCreatable'] = false;
        }

        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['url']['default'] = $url;
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['showUnpublished']['default'] = $request->query->getBoolean('showUnpublished');
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['createdAt']['default'] = $now->getTimestamp();
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['expiresAt']['default'] = strtotime('+1 day', $now->getTimestamp());
        $GLOBALS['TL_DCA']['tl_preview_link']['fields']['createdBy']['default'] = $user instanceof BackendUser ? (int) $user->id : 0;
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

        $row = $dc->getCurrentRecord();

        if ($row['expiresAt'] < $this->clock->now()->getTimestamp()) {
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
        if ($row['expiresAt'] < $this->clock->now()->getTimestamp()) {
            foreach ($args as &$arg) {
                $arg = \sprintf('<span class="tl_gray">%s</span>', $arg);
            }

            unset($arg);
        }

        return $args;
    }

    #[AsCallback(table: 'tl_preview_link', target: 'list.operations.share.button')]
    public function shareOperation(DataContainerOperation $operation): void
    {
        $row = $operation->getRecord();

        if ($row['expiresAt'] < $this->clock->now()->getTimestamp()) {
            $operation->disable();
        } else {
            $url = $this->generateUrl((int) $row['id']);

            $operation->setUrl($url);

            $operation['attributes']
                ->set('data-controller', 'contao--clipboard')
                ->set('data-contao--clipboard-content-value', $url)
                ->set('data-contao--clipboard-message-value', $this->translator->trans('MSC.clipboardCopy', [], 'contao_default'))
                ->set('data-action', 'contao--clipboard#write:prevent')
            ;
        }
    }

    private function generateUrl(int $id): string
    {
        $url = $this->urlGenerator->generate('contao_preview_link', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->uriSigner->sign($url);
    }

    private function generateClipboardLink(int $id): string
    {
        $url = $this->generateUrl($id);

        return \sprintf(
            '<a href="%s" target="_blank" title="%s" data-controller="contao--clipboard" data-contao--clipboard-content-value="%s" data-contao--clipboard-message-value="%s" data-action="contao--clipboard#write:prevent">%s</a> ',
            StringUtil::specialcharsUrl($url),
            StringUtil::specialchars($this->translator->trans('tl_preview_link.share.0', [], 'contao_tl_preview_link')),
            StringUtil::specialcharsUrl($url),
            $this->translator->trans('MSC.clipboardCopy', [], 'contao_default'),
            $url,
        );
    }
}
