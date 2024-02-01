<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\Parser\Extractors;

use PhpParser\Node;
use Qossmic\Deptrac\Contract\Ast\DependencyContext;
use Qossmic\Deptrac\Core\Ast\AstMap\ReferenceBuilder;
use Qossmic\Deptrac\Core\Ast\Parser\TypeResolver;
use Qossmic\Deptrac\Core\Ast\Parser\TypeScope;

class FunctionLikeExtractor implements ReferenceExtractorInterface
{
    public function __construct(private readonly TypeResolver $typeResolver) {}

    public function processNode(Node $node, ReferenceBuilder $referenceBuilder, TypeScope $typeScope): void
    {
        if (!$node instanceof Node\FunctionLike) {
            return;
        }

        $oldContext = $referenceBuilder->getContext();

        if (!$oldContext->deprecated && $node->getAttribute('deprecated', false)) {
            $name = isset( $node->name ) ? (string)$node->name: null;
            $newContext = new DependencyContext( $name, true );
            $referenceBuilder->setContext($newContext);
        }

        try {
            foreach ($node->getAttrGroups() as $attrGroup) {
                foreach ($attrGroup->attrs as $attribute) {
                    foreach ($this->typeResolver->resolvePHPParserTypes($typeScope, $attribute->name) as $classLikeName) {
                        $referenceBuilder->attribute($classLikeName, $attribute->getLine());
                    }
                }
            }
            foreach ($node->getParams() as $param) {
                if (null !== $param->type) {
                    foreach ($this->typeResolver->resolvePHPParserTypes($typeScope, $param->type) as $classLikeName) {
                        $referenceBuilder->parameter($classLikeName, $param->type->getLine());
                    }
                }
            }
            $returnType = $node->getReturnType();
            if (null !== $returnType) {
                foreach ($this->typeResolver->resolvePHPParserTypes($typeScope, $returnType) as $classLikeName) {
                    $referenceBuilder->returnType($classLikeName, $returnType->getLine());
                }
            }
        } finally {
            $referenceBuilder->setContext($oldContext);
        }
    }
}
