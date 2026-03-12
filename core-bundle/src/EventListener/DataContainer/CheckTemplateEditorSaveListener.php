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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks whether "tpl_editor" is added to the allowed back end modules and denies
 * saving if the user is not admin and the user or user group did not previously
 * have access to the template editor.
 */
#[AsCallback('tl_user', 'fields.modules.save')]
#[AsCallback('tl_user_group', 'fields.modules.save')]
class CheckTemplateEditorSaveListener
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(mixed $value, DataContainer $dc): mixed
    {
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $value;
        }

        $newModules = StringUtil::deserialize($value);

        // Do nothing if access to the template editor is not tried to be granted
        if (!\is_array($newModules) || !\in_array('tpl_editor', $newModules, true)) {
            return $value;
        }

        $currentModules = StringUtil::deserialize($dc->getCurrentRecord()['modules'] ?? null);

        // Allow changing if access to template editor was already granted
        if (\is_array($currentModules) && \in_array('tpl_editor', $currentModules, true)) {
            return $value;
        }

        throw new AccessDeniedException($this->translator->trans('ERR.grantTemplateEditor', [], 'contao_default'));
    }
}
