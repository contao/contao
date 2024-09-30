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

use Contao\ContentProxy;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\ModuleProxy;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(table: 'tl_content', target: 'fields.customTpl.options')]
#[AsCallback(table: 'tl_form_field', target: 'fields.customTpl.options')]
#[AsCallback(table: 'tl_module', target: 'fields.customTpl.options')]
class TemplateOptionsListener
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $defaultIdentifiersByType = [];

    public function __construct(
        private readonly FinderFactory $finderFactory,
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly ContaoFilesystemLoader $filesystemLoader,
    ) {
    }

    public function __invoke(DC_Table $dc): array
    {
        $overrideAll = $this->isOverrideAll();

        $type = $overrideAll
            ? $this->getCommonOverrideAllType($dc)
            : $dc->getActiveRecord()['type'] ?? null;

        if (null === $type) {
            // Add a blank option that allows to reset all custom templates to the default
            // one when in "overrideAll" mode
            return $overrideAll ? ['' => '-'] : [];
        }

        $identifier = $this->defaultIdentifiersByType[$dc->table][$type] ?? null;
        $legacyPrefix = $this->getLegacyTemplatePrefix($dc);
        $legacyProxyClass = $this->getLegacyProxyClass($dc);

        if (null !== ($legacyTemplateOptions = $this->handleLegacyTemplates($type, $identifier, $overrideAll, $legacyPrefix, $legacyProxyClass))) {
            return $legacyTemplateOptions;
        }

        $templateOptions = $this->finderFactory
            ->create()
            ->identifier((string) $identifier)
            ->extension('html.twig')
            ->withVariants()
            ->asTemplateOptions()
        ;

        // We will end up with no templates if the logic assumes a non-legacy template
        // but the user did not add any or uses the old prefix. For example a "foo"
        // content element fragment controller (without an explicit definition of a
        // template in the service tag) used with a "ce_foo.html.twig" template -
        // although this template will be rendered for BC reasons, the template selection
        // won't be possible.
        if (!$templateOptions) {
            $guessedType = $legacyPrefix.$type;

            if (isset($this->filesystemLoader->getInheritanceChains()[$guessedType])) {
                $help = \sprintf('In case you wanted to use the legacy type "%s", define it explicitly in the "template" property of your controller\'s service tag/attribute.', $guessedType);
            } else {
                $help = 'Did you forget to create the default template?';
            }

            throw new \LogicException(\sprintf('Tried to list template options for the modern fragment type "%s" but could not find any template. %s', $identifier, $help));
        }

        return $templateOptions;
    }

    /**
     * Called by the RegisterFragmentsPass for all fragment controllers.
     *
     * @see RegisterFragmentsPass
     */
    public function setDefaultIdentifiersByType(string $dca, array $defaultIdentifiersByType): void
    {
        $this->defaultIdentifiersByType[$dca] = $defaultIdentifiersByType;
    }

    /**
     * Handles legacy elements that aren't implemented as fragment controllers or that
     * still use the old template naming scheme.
     */
    private function handleLegacyTemplates(string $type, string|null $identifier, bool $overrideAll, string $legacyPrefix, string|null $legacyProxyClass): array|null
    {
        $isModernIdentifier = $identifier && str_contains($identifier, '/');
        $legacyDefaultIdentifier = $this->getLegacyDefaultIdentifier($type, $legacyProxyClass);

        // Do not use the legacy logic for modern templates
        if (null !== $identifier && $isModernIdentifier && !$legacyDefaultIdentifier) {
            return null;
        }

        if (null === $identifier || $isModernIdentifier) {
            $identifier = $legacyDefaultIdentifier ?? $legacyPrefix.$type;
        }

        return [
            ...($overrideAll ? ['' => '-'] : []),
            ...$this->framework
                ->getAdapter(Controller::class)
                ->getTemplateGroup($identifier.'_', [], $identifier),
        ];
    }

    /**
     * Uses the reflection API to return the default template from a legacy class.
     */
    private function getLegacyDefaultIdentifier(string|null $type, string|null $legacyProxyClass): string|null
    {
        if (null === $type || null === $legacyProxyClass || !method_exists($legacyProxyClass, 'findClass')) {
            return null;
        }

        $class = $legacyProxyClass::findClass($type);

        if (empty($class) || $class === $legacyProxyClass) {
            return null;
        }

        $properties = (new \ReflectionClass($class))->getDefaultProperties();

        return $properties['strTemplate'] ?? null;
    }

    private function isOverrideAll(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request?->query->has('act')) {
            return false;
        }

        return 'overrideAll' === $request->query->get('act');
    }

    /**
     * Returns the type that all currently edited items are sharing or null if there
     * is no common type.
     */
    private function getCommonOverrideAllType(DataContainer $dc): string|null
    {
        $affectedIds = $this->requestStack->getSession()->all()['CURRENT']['IDS'] ?? [];
        $table = $this->connection->quoteIdentifier($dc->table);

        $result = $this->connection->executeQuery(
            "SELECT type FROM $table WHERE id IN (?) GROUP BY type LIMIT 2",
            [$affectedIds],
            [ArrayParameterType::STRING],
        );

        if (1 !== $result->rowCount()) {
            return null;
        }

        return $result->fetchOne();
    }

    private function getLegacyTemplatePrefix(DataContainer $dc): string
    {
        return match ($dc->table) {
            'tl_content' => 'ce_',
            'tl_module' => 'mod_',
            'tl_form_field' => 'form_',
            default => throw new \InvalidArgumentException(\sprintf('Not implemented for "%s".', $dc->table)),
        };
    }

    private function getLegacyProxyClass(DataContainer $dc): string|null
    {
        return match ($dc->table) {
            'tl_content' => ContentProxy::class,
            'tl_module' => ModuleProxy::class,
            default => null,
        };
    }
}
