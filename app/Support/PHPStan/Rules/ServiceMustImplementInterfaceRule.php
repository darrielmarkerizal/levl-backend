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
class ServiceMustImplementInterfaceRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        $className = $classReflection->getName();

        // Only check classes in Services directories
        if (! str_contains($className, '\\Services\\')) {
            return [];
        }

        // Skip if class name doesn't end with 'Service'
        if (! str_ends_with($className, 'Service')) {
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
                        'Service class %s must implement an interface (e.g., %sInterface)',
                        $classReflection->getDisplayName(),
                        $classReflection->getDisplayName()
                    )
                )->build(),
            ];
        }

        // Check if implements a corresponding interface (e.g., FooService implements FooServiceInterface)
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
                        'Service class %s should implement %s',
                        $classReflection->getDisplayName(),
                        $expectedInterfaceName
                    )
                )->tip('Create the interface and make the service implement it')->build(),
            ];
        }

        return [];
    }
}
