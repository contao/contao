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
 * Checks whether an implicit admin grant is added and denies saving if the user
 * is not admin and the user or user group did not previously have access.
 */
#[AsCallback('tl_user', 'fields.modules.save')]
#[AsCallback('tl_user', 'fields.themes.save')]
#[AsCallback('tl_user', 'fields.elements.save')]
#[AsCallback('tl_user', 'fields.frontendModules.save')]
#[AsCallback('tl_user_group', 'fields.modules.save')]
#[AsCallback('tl_user_group', 'fields.themes.save')]
#[AsCallback('tl_user_group', 'fields.elements.save')]
#[AsCallback('tl_user_group', 'fields.frontendModules.save')]
#[AsCallback('tl_user_group', 'fields.alexf.save')]
class CheckImplicitAdminSaveListener
{
    private const IMPLICIT_ADMIN_VALUES = [
        'modules' => ['tpl_editor'],
        'themes' => ['theme_import', 'layout'],
        'elements' => ['unfiltered_html'],
        'frontendModules' => ['unfiltered_html', 'listing'],
        'alexf' => [
            'tl_module::list_table',
            'tl_module::list_fields',
            'tl_module::list_where',
            'tl_module::list_search',
            'tl_module::list_sort',
            'tl_module::list_info',
            'tl_module::list_info_where',
            'tl_content::unfilteredHtml',
            'tl_module::unfilteredHtml',
        ],
    ];

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

        $newValue = StringUtil::deserialize($value);

        // Do nothing if access is not tried to be granted
        if (!\is_array($newValue) || !array_intersect(self::IMPLICIT_ADMIN_VALUES[$dc->field], $newValue)) {
            return $value;
        }

        $currentValue = StringUtil::deserialize($dc->getCurrentRecord()[$dc->field] ?? null);

        foreach (array_intersect(self::IMPLICIT_ADMIN_VALUES[$dc->field], $newValue) as $newAdminValue) {
            // Allow changing if access was already granted
            if (\is_array($currentValue) && \in_array($newAdminValue, $currentValue, true)) {
                continue;
            }

            if ('elements' === $dc->field) {
                $label = $this->translator->trans("CTE.$newAdminValue.0", [], 'contao_default');
            } elseif ('frontendModules' === $dc->field) {
                $label = $this->translator->trans("FMD.$newAdminValue.0", [], 'contao_default');
            } elseif ('alexf' === $dc->field) {
                [$table, $field] = explode('::', $newAdminValue, 2) + ['', ''];
                $label = $this->translator->trans("$table.$field.0", [], "contao_$table")." [$table.$field]";
            } elseif ('themes' === $dc->field) {
                $label = $this->translator->trans("MOD.$newAdminValue", [], 'contao_default');
            } else {
                $label = $this->translator->trans("MOD.$newAdminValue.0", [], 'contao_default');
            }

            throw new AccessDeniedException($this->translator->trans('ERR.grantAdminOnly', [$label], 'contao_default'));
        }

        return $value;
    }
}
