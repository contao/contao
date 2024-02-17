<?php

declare(strict_types=1);

namespace Contao\PhpStan;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class TriggerDeprecationRule implements Rule
{
    private string|null $branchName = null;

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $targetBranch = $_SERVER['GITHUB_BASE_REF'] ?? null;

        if (null === $targetBranch) {
            return [];
        }

        if (!($node->name instanceof Name)) {
            return [];
        }

        $args = $node->getArgs();
        if (\count($args) < 3) {
            return [];
        }

        $functionName = strtolower($node->name->toString());
        if ('trigger_deprecation' !== $functionName) {
            return [];
        }

        $errors = [];
        $bundle = $args[0]->value->value;
        $version = $args[1]->value->value;
        $message = $args[2]->value->value;

        $versionParser = new VersionParser();

        try {
            $normalizedBranchVersion = $versionParser->normalize(str_replace('x', '0', $targetBranch));
            if (!Semver::satisfies($version, new Constraint('=', $normalizedBranchVersion))) {
                $errors[] = sprintf('Deprecation needs to be removed in bundle "%s": %s',
                    $bundle,
                    $message,
                );
            }
        } catch (\Exception $e) {
            $errors[] = 'Something with your trigger_deprecation() format has gone wrong: '.$e->getMessage();
        }

        return $errors;
    }
}
