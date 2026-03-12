<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
abstract class AbstractCreateLegacyVariantOperation extends AbstractOperation
{
    public function canExecute(OperationContext $context): bool
    {
        if ($context->isThemeContext()) {
            return false;
        }

        if ($this->disallowCustomName()) {
            return $context->getIdentifier() === $this->getPrefix();
        }

        if (1 !== preg_match('%^'.preg_quote($this->getPrefix(), '%').'_[^/]+$%', $context->getIdentifier())) {
            return false;
        }

        return !$this->userTemplateExists($context, true);
    }

    public function execute(Request $request, OperationContext $context): Response|null
    {
        // Show a confirmation dialog
        if (!$identifierFragment = $request->request->getString('identifier_fragment')) {
            return $this->render('@Contao/backend/template_studio/operation/create_or_rename_variant.stream.html.twig', [
                'operation' => $this->getName(),
                'operation_type' => 'create',
                'identifier' => $context->getIdentifier(),
                'extension' => $context->getExtension(),
                'separator' => '_',
                'suggested_identifier_fragment' => $this->suggestIdentifierFragmentName($context->getIdentifier(), $context->getExtension()),
                'allowed_identifier_fragment_pattern' => $this->buildAllowedIdentifierFragmentsPattern($context->getIdentifier(), $context->getThemeSlug()),
            ]);
        }

        // Do not allow creating subdirectories
        $newIdentifier = str_replace('/', '-', "{$context->getIdentifier()}_$identifierFragment");
        $newStoragePath = "$newIdentifier.{$context->getExtension()}";

        if ($this->getUserTemplatesStorage()->fileExists($newStoragePath)) {
            return $this->error($context, 'template_studio.message.create_variant.error');
        }

        // Create the variant template file with some default content
        $skeleton = $this
            ->getTemplateSkeletonFactory()
            ->create()
            ->getContent($context->getManagedNamespaceName())
        ;

        $this->getUserTemplatesStorage()->write($newStoragePath, $skeleton);

        $this->refreshTemplateHierarchy();

        return $this->render('@Contao/backend/template_studio/operation/create_variant_result.stream.html.twig', [
            'identifier' => $newIdentifier,
        ]);
    }

    /**
     * Return the template identifier prefix this operation is targeting (e.g. "ce").
     */
    abstract protected function getPrefix(): string;

    /**
     * Return true if no name is allowed after the prefix.
     */
    protected function disallowCustomName(): bool
    {
        return false;
    }

    private function buildAllowedIdentifierFragmentsPattern(string $identifier, string|null $themeSlug): string
    {
        $existingVariantNames = array_map(
            static fn ($candidate): string => substr($candidate, \strlen($identifier) + 1),
            array_filter(
                array_keys($this->getContaoFilesystemLoader()->getInheritanceChains($themeSlug)),
                static fn (string $candidate) => str_starts_with($candidate, "{$identifier}_"),
            ),
        );

        // Disallow selecting a name of an existing variant
        return \sprintf(
            '^(?!(%s)$).*',
            implode('|', $existingVariantNames),
        );
    }

    private function suggestIdentifierFragmentName(string $identifier, string $extension): string
    {
        $loader = $this->getContaoFilesystemLoader();

        $identifierFragmentBase = 'new-variant';
        $identifierFragment = $identifierFragmentBase;

        $index = 2;

        while ($loader->exists("@Contao/{$identifier}_$identifierFragment.$extension")) {
            $identifierFragment = "$identifierFragmentBase$index";
            ++$index;
        }

        return $identifierFragment;
    }
}
