<?php

namespace App\Support\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 */
class RepositoryMustImplementInterfaceRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        $className = $classReflection->getName();

        // Only check classes in Repositories directories
        if (! str_contains($className, '\\Repositories\\')) {
            return [];
        }

        // Skip if class name doesn't end with 'Repository'
        if (! str_ends_with($className, 'Repository')) {
            return [];
        }

        // Skip abstract classes
        if ($classReflection->isAbstract()) {
            return [];
        }

        // Check if class implements any interface
        $interfaces = $classReflection->getInterfaces();

        if (empty($interfaces)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Repository class %s must implement an interface (e.g., %sInterface)',
                        $classReflection->getDisplayName(),
                        $classReflection->getDisplayName()
                    )
                )->build(),
            ];
        }

        // Check if implements a corresponding interface (e.g., FooRepository implements FooRepositoryInterface)
        $expectedInterfaceName = $className.'Interface';
        $hasCorrespondingInterface = false;

        foreach ($interfaces as $interface) {
            if ($interface->getName() === $expectedInterfaceName) {
                $hasCorrespondingInterface = true;
                break;
            }
        }

        if (! $hasCorrespondingInterface) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Repository class %s should implement %s',
                        $classReflection->getDisplayName(),
                        $expectedInterfaceName
                    )
                )->tip('Create the interface and make the repository implement it')->build(),
            ];
        }

        // Check method naming conventions
        $errors = [];
        foreach ($classReflection->getNativeMethods() as $method) {
            if ($method->isPrivate() || $method->isProtected()) {
                continue;
            }

            $methodName = $method->getName();

            // Skip magic methods and constructor
            if (str_starts_with($methodName, '__')) {
                continue;
            }

            // Check for common repository method patterns
            $validPrefixes = ['find', 'get', 'create', 'update', 'delete', 'save', 'paginate', 'count', 'exists', 'sum'];
            $hasValidPrefix = false;

            foreach ($validPrefixes as $prefix) {
                if (str_starts_with($methodName, $prefix)) {
                    $hasValidPrefix = true;
                    break;
                }
            }

            if (! $hasValidPrefix) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Repository method %s::%s() should follow naming convention (start with: %s)',
                        $classReflection->getDisplayName(),
                        $methodName,
                        implode(', ', $validPrefixes)
                    )
                )->tip('Repository methods should describe data operations')->build();
            }
        }

        return $errors;
    }
}
