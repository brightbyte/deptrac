<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\AstMap\ClassLike;

use Qossmic\Deptrac\Core\Ast\AstMap\AstInherit;
use Qossmic\Deptrac\Core\Ast\AstMap\DependencyToken;
use Qossmic\Deptrac\Core\Ast\AstMap\File\FileReference;
use Qossmic\Deptrac\Core\Ast\AstMap\TaggedTokenReference;

/**
 * @psalm-immutable
 */
class ClassLikeReference extends TaggedTokenReference
{
    public readonly ClassLikeType $type;

    /**
     * @param AstInherit[] $inherits
     * @param DependencyToken[] $dependencies
     * @param array<string,list<string>> $tags
     */
    public function __construct(
        private readonly ClassLikeToken $classLikeName,
        ClassLikeType $classLikeType = null,
        public readonly array $inherits = [],
        public readonly array $dependencies = [],
        public readonly array $tags = [],
        private readonly ?FileReference $fileReference = null
    ) {
        parent::__construct($tags);
        $this->type = $classLikeType ?? ClassLikeType::TYPE_CLASSLIKE;
    }

    public function withFileReference(FileReference $astFileReference): self
    {
        return new self(
            $this->classLikeName,
            $this->type,
            $this->inherits,
            $this->dependencies,
            $this->tags,
            $astFileReference
        );
    }

    public function getFilepath(): ?string
    {
        return $this->fileReference?->filepath;
    }

    public function getToken(): ClassLikeToken
    {
        return $this->classLikeName;
    }
}
