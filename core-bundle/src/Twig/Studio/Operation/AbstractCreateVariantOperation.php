<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
abstract class AbstractCreateVariantOperation extends AbstractOperation
{
    public function canExecute(OperationContext $context): bool
    {
        if ($context->isThemeContext()) {
            return false;
        }

        return 1 === preg_match('%^'.preg_quote($this->getPrefix(), '%').'/[^_/][^/]*$%', $context->getIdentifier());
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
                'suggested_identifier_fragment' => $this->suggestIdentifierFragmentName($context->getIdentifier(), $context->getExtension()),
                'allowed_identifier_fragment_pattern' => $this->buildAllowedIdentifierFragmentsPattern($context->getIdentifier()),
            ]);
        }

        $newIdentifier = "{$context->getIdentifier()}/$identifierFragment";
        $newStoragePath = "$newIdentifier.{$context->getExtension()}";

        if ($this->getUserTemplatesStorage()->fileExists($newStoragePath)) {
            return $this->error($context, 'message.create_variant.error');
        }

        // Create the variant template file with some default content
        $skeleton = $this->getTemplateSkeletonFactory()
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
     * Return the template identifier prefix this operation is targeting (e.g.
     * "content_element").
     */
    abstract protected function getPrefix(): string;

    private function buildAllowedIdentifierFragmentsPattern(string $identifier): string
    {
        $existingVariantNames = array_map(
            static fn (string $variantIdentifier): string => substr($variantIdentifier, \strlen($identifier) + 1),
            array_keys(
                iterator_to_array(
                    $this->getTwigFinder()
                        ->identifier($identifier)
                        ->withVariants(true),
                ),
            ),
        );

        // Disallow selecting a name of an existing variant
        return \sprintf(
            '^(?!(%s)$).*',
            implode('|', array_map(preg_quote(...), $existingVariantNames)),
        );
    }

    private function suggestIdentifierFragmentName(string $identifier, string $extension): string
    {
        $loader = $this->getContaoFilesystemLoader();

        $identifierFragmentBase = 'new_variant';
        $identifierFragment = $identifierFragmentBase;

        $index = 2;

        while ($loader->exists("@Contao/$identifier/$identifierFragment.$extension")) {
            $identifierFragment = "$identifierFragmentBase$index";
            ++$index;
        }

        return $identifierFragment;
    }
}
