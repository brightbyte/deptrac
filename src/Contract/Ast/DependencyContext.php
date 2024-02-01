<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Ast;

/**
 * @psalm-immutable
 *
 * Where in the file has the dependency occurred.
 */
final class DependencyContext
{
    /**
     * @param ?string $function the name of the function-like construct that
     *                          caused the dependency, if any
     * @param bool $deprecated whether the source of the dependency is deprecated
     */
    public function __construct(
        public readonly ?string $function,
        public readonly bool $deprecated = false,
    ) {}
}
