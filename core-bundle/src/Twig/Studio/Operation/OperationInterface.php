<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
interface OperationInterface
{
    /**
     * Return true if the operation can be executed in the given context.
     */
    public function canExecute(TemplateContext $context): bool;

    /**
     * Execute the operation and return a response that will be sent to the browser or
     * null if a default (Turbo stream) response should be generated instead.
     */
    public function execute(Request $request, TemplateContext $context): Response|null;
}
