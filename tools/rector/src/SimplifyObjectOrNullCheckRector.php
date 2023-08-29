<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Tools\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use Rector\Core\Rector\AbstractRector;
use Rector\TypeDeclaration\TypeAnalyzer\NullableTypeAnalyzer;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class SimplifyObjectOrNullCheckRector extends AbstractRector
{
    public function __construct(private readonly NullableTypeAnalyzer $nullableTypeAnalyzer)
    {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Do not use null compare to check for an object or null', [
            new CodeSample(
                <<<'CODE_SAMPLE'
                    function process(DateTime|null $dateTime)
                    {
                        if (null === $dateTime) {
                            return;
                        }
                    }
                    CODE_SAMPLE,
                <<<'CODE_SAMPLE'
                    function process(DateTime|null $dateTime)
                    {
                        if (!$dateTime) {
                            return;
                        }
                    }
                    CODE_SAMPLE,
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Identical::class, NotIdentical::class];
    }

    /**
     * @param Identical|NotIdentical $node
     */
    public function refactor(Node $node): Node|null
    {
        $expr = $this->matchNullComparedExpr($node);

        if (!$expr) {
            return null;
        }

        $nullableObjectType = $this->nullableTypeAnalyzer->resolveNullableObjectType($expr);

        if (!$nullableObjectType) {
            return null;
        }

        // Allow null compare in boolean return statements
        if ($node->getAttribute(ReturnTypeVisitor::ATTRIBUTE_NAME)) {
            return null;
        }

        if ($node instanceof NotIdentical) {
            return $expr;
        }

        return new BooleanNot($expr);
    }

    private function matchNullComparedExpr(Identical|NotIdentical $binaryOp): Expr|null
    {
        if ($this->valueResolver->isNull($binaryOp->left)) {
            return $binaryOp->right;
        }

        if ($this->valueResolver->isNull($binaryOp->right)) {
            return $binaryOp->left;
        }

        return null;
    }
}
