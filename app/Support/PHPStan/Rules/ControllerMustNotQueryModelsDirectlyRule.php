<?php

namespace App\Support\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<StaticCall>
 */
class ControllerMustNotQueryModelsDirectlyRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Only check code in Controllers
        $namespace = $scope->getNamespace();
        if ($namespace === null || ! str_contains($namespace, '\\Controllers\\')) {
            return [];
        }

        // Check if this is a static call on a Model class
        if (! $node->class instanceof Node\Name) {
            return [];
        }

        $className = $scope->resolveName($node->class);

        // Check if the class is a Model (ends with common model patterns or in Models namespace)
        if (! $this->isModelClass($className)) {
            return [];
        }

        // Check if the method is a query method
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        $methodName = $node->name->toString();
        $queryMethods = [
            'query', 'where', 'find', 'findOrFail', 'first', 'firstOrFail',
            'get', 'all', 'create', 'update', 'delete', 'destroy',
            'insert', 'insertGetId', 'updateOrCreate', 'firstOrCreate',
            'count', 'sum', 'avg', 'min', 'max', 'exists',
        ];

        if (in_array($methodName, $queryMethods, true)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Controller should not query models directly. Use repository or service instead. Found: %s::%s()',
                        $this->getShortClassName($className),
                        $methodName
                    )
                )->tip('Move this query to a repository or service method')->build(),
            ];
        }

        return [];
    }

    private function isModelClass(string $className): bool
    {
        // Check if class is in Models namespace
        if (str_contains($className, '\\Models\\')) {
            return true;
        }

        // Check common model base classes
        $modelBaseClasses = [
            'Illuminate\\Database\\Eloquent\\Model',
            'Illuminate\\Foundation\\Auth\\User',
        ];

        foreach ($modelBaseClasses as $baseClass) {
            if (is_subclass_of($className, $baseClass)) {
                return true;
            }
        }

        return false;
    }

    private function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }
}
