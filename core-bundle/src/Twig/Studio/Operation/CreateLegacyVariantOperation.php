<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForTemplateStudioElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[AsOperationForTemplateStudioElement]
class CreateLegacyVariantOperation extends AbstractOperation
{
    public function canExecute(OperationContext $context): bool
    {
        if ($context->isThemeContext()) {
            return false;
        }

        if (str_contains($context->getIdentifier(), '/') || !str_contains($context->getIdentifier(), '_')) {
            return false;
        }

        return !$this->userTemplateExists($context, true);
    }

    public function execute(Request $request, OperationContext $context): Response|null
    {
        [$identifier] = explode('_', $context->getIdentifier(), 2);

        // Show a confirmation dialog
        if (!$identifierFragment = $request->request->getString('identifier_fragment')) {
            return $this->render('@Contao/backend/template_studio/operation/create_or_rename_variant.stream.html.twig', [
                'operation' => $this->getName(),
                'operation_type' => 'create',
                'identifier' => $identifier,
                'extension' => $context->getExtension(),
                'separator' => '_',
                'suggested_identifier_fragment' => $this->suggestIdentifierFragmentName($identifier, $context->getExtension()),
                'allowed_identifier_fragment_pattern' => $this->buildAllowedIdentifierFragmentsPattern($identifier, $context->getThemeSlug()),
            ]);
        }

        // Do not allow creating subdirectories
        $newIdentifier = str_replace('/', '-', "{$identifier}_$identifierFragment");
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
