<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\AstMap\Function;

use Qossmic\Deptrac\Core\Ast\AstMap\ReferenceBuilder;

class FunctionReferenceBuilder extends ReferenceBuilder
{
    /**
     * @param list<string> $tokenTemplates
     * @param array<string,string[]> $tags
     */
    private function __construct(
        array $tokenTemplates,
        string $filepath,
        private readonly string $functionName,
        private readonly array $tags
    ) {
        parent::__construct(
            $tokenTemplates,
            $filepath
        );
    }

    /**
     * @param list<string> $functionTemplates
     * @param array<string,string[]> $tags
     */
    public static function create(string $filepath, string $functionName, array $functionTemplates, array $tags): self
    {
        return new self($functionTemplates, $filepath, $functionName, $tags);
    }

    /** @internal */
    public function build(): FunctionReference
    {
        return new FunctionReference(
            FunctionToken::fromFQCN($this->functionName),
            $this->dependencies,
            $this->tags
        );
    }
}
