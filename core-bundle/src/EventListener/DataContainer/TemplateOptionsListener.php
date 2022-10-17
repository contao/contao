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
    /**
     * @var array<string, string>
     */
    private array $defaultIdentifiersByType = [];

    public function __construct(
        private readonly FinderFactory $finderFactory,
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly string $legacyTemplatePrefix,
        private readonly string|null $legacyProxyClass = null,
    ) {
    }

    public function __invoke(DataContainer $dc): array
    {
        $overrideAll = $this->isOverrideAll();

        $type = $overrideAll ?
            $this->getCommonOverrideAllType($dc) :
            $dc->getCurrentRecord()['type'] ?? null
        ;

        if (null === $type) {
            // Add a blank option that allows to reset all custom templates to
            // the default one when in 'overrideAll' mode
            return $overrideAll ? ['' => '-'] : [];
        }

        $identifier = $this->defaultIdentifiersByType[$type] ?? null;
        $legacyDefaultIdentifier = $this->getLegacyDefaultIdentifier($type);

        // Handle legacy elements that aren't implemented as fragment
        // controllers or that still use the old template naming scheme
        if ($legacyDefaultIdentifier || null === $identifier || !str_contains($identifier, '/')) {
            $identifier = $legacyDefaultIdentifier ?? $this->legacyTemplatePrefix.$type;

            return [
                ...($overrideAll ? ['' => '-'] : []),
                ...$this->framework
                    ->getAdapter(Controller::class)
                    ->getTemplateGroup($identifier.'_', [], $identifier),
            ];
        }

        $templateOptions = $this->finderFactory
            ->create()
            ->identifier($identifier)
            ->extension('html.twig')
            ->withVariants()
            ->asTemplateOptions()
        ;

        // We will end up with no templates if the logic assumes a non-legacy
        // template but the user did not add any or uses the old prefix. For
        // example a 'foo' content element fragment controller (without an
        // explicit definition of a template in the service tag) used with a
        // 'ce_foo.html.twig' template - although this template will be
        // rendered for BC reasons, the template selection won't be possible.
        if (0 === \count($templateOptions)) {
            throw new \LogicException(sprintf('Tried to list template options for the modern fragment type "%s" but could not find any template. Did you forget to create the default template?', $identifier));
        }

        return $templateOptions;
    }

    /**
     * Called by the @see \Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass
     * for all fragment controllers.
     */
    public function setDefaultIdentifiersByType(array $defaultIdentifiersByType): void
    {
        $this->defaultIdentifiersByType = $defaultIdentifiersByType;
    }

    /**
     * Uses the reflection API to return the default template from a legacy class.
     */
    private function getLegacyDefaultIdentifier(string|null $type): string|null
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
    private function getCommonOverrideAllType(DataContainer $dc): string|null
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
