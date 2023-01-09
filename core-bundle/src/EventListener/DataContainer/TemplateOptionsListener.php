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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

class TemplateOptionsListener
{
    private FinderFactory $finderFactory;
    private Connection $connection;
    private ContaoFramework $framework;
    private RequestStack $requestStack;
    private string $legacyTemplatePrefix;
    private ?string $legacyProxyClass;

    /**
     * @var array<string, string>
     */
    private array $defaultIdentifiersByType = [];

    public function __construct(FinderFactory $finderFactory, Connection $connection, ContaoFramework $framework, RequestStack $requestStack, string $legacyTemplatePrefix, string $legacyProxyClass = null)
    {
        $this->finderFactory = $finderFactory;
        $this->connection = $connection;
        $this->framework = $framework;
        $this->requestStack = $requestStack;
        $this->legacyTemplatePrefix = $legacyTemplatePrefix;
        $this->legacyProxyClass = $legacyProxyClass;
    }

    public function __invoke(DataContainer $dc): array
    {
        $overrideAll = $this->isOverrideAll();

        $type = $overrideAll
            ? $this->getCommonOverrideAllType($dc)
            : $dc->activeRecord->type ?? null;

        if (null === $type) {
            // Add a blank option that allows to reset all custom templates to
            // the default one when in "overrideAll" mode
            return $overrideAll ? ['' => '-'] : [];
        }

        $identifier = $this->defaultIdentifiersByType[$type] ?? null;

        if (null !== ($legacyTemplateOptions = $this->handleLegacyTemplates($type, $identifier, $overrideAll))) {
            return $legacyTemplateOptions;
        }

        $templateOptions = $this->finderFactory
            ->create()
            ->identifier($identifier)
            ->extension('html.twig')
            ->withVariants()
            ->asTemplateOptions()
        ;

        if (0 === \count($templateOptions)) {
            throw new \LogicException(sprintf('Tried to list template options for the modern fragment type "%s" but could not find any template. Did you forget to create the default template?', $identifier));
        }

        return $templateOptions;
    }

    /**
     * Called by the RegisterFragmentsPass for all fragment controllers.
     *
     * @see \Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass
     */
    public function setDefaultIdentifiersByType(array $defaultIdentifiersByType): void
    {
        $this->defaultIdentifiersByType = $defaultIdentifiersByType;
    }

    /**
     * Handles legacy elements that aren't implemented as fragment controllers
     * or that still use the old template naming scheme.
     */
    private function handleLegacyTemplates(string $type, ?string $identifier, bool $overrideAll): ?array
    {
        $isModernIdentifier = $identifier && str_contains($identifier, '/');
        $legacyDefaultIdentifier = $this->getLegacyDefaultIdentifier($type);

        // Do not use the legacy logic for modern templates
        if (null !== $identifier && $isModernIdentifier && !$legacyDefaultIdentifier) {
            return null;
        }

        if (null === $identifier || $isModernIdentifier) {
            $identifier = $legacyDefaultIdentifier ?? $this->legacyTemplatePrefix.$type;
        }

        return array_merge(
            $overrideAll ? ['' => '-'] : [],
            $this->framework
                ->getAdapter(Controller::class)
                ->getTemplateGroup($identifier.'_', [], $identifier),
        );
    }

    /**
     * Uses the reflection API to return the default template from a legacy class.
     */
    private function getLegacyDefaultIdentifier(?string $type): ?string
    {
        if (null === $type || null === $this->legacyProxyClass || !method_exists($this->legacyProxyClass, 'findClass')) {
            return null;
        }

        $class = $this->legacyProxyClass::findClass($type);

        if (empty($class) || $class === $this->legacyProxyClass) {
            return null;
        }

        $properties = (new \ReflectionClass($class))->getDefaultProperties();

        return $properties['strTemplate'] ?? null;
    }

    private function isOverrideAll(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->query->has('act')) {
            return false;
        }

        return 'overrideAll' === $request->query->get('act');
    }

    /**
     * Returns the type that all currently edited items are sharing or null if
     * there is no common type.
     */
    private function getCommonOverrideAllType(DataContainer $dc): ?string
    {
        $affectedIds = $this->requestStack->getSession()->all()['CURRENT']['IDS'] ?? [];
        $table = $this->connection->quoteIdentifier($dc->table);

        $result = $this->connection->executeQuery(
            "SELECT type FROM $table WHERE id IN (?) GROUP BY type LIMIT 2",
            [$affectedIds],
            [Connection::PARAM_INT_ARRAY]
        );

        if (1 !== $result->rowCount()) {
            return null;
        }

        return $result->fetchOne();
    }
}
