<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser;

use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Ast\TokenReferenceInterface;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Qossmic\Deptrac\Core\Ast\Parser\Cache\AstFileReferenceInMemoryCache;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\AnnotationReferenceExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\Extractors\FunctionLikeExtractor;
use Qossmic\Deptrac\Core\Ast\Parser\NikicPhpParser\NikicPhpParser;
use Qossmic\Deptrac\Core\Ast\Parser\TypeResolver;
use stdClass;
use TypeError;

final class NikicPhpParserTest extends TestCase
{
    private NikicPhpParser $parser;

    protected function setUp(): void
    {
        $this->parser = new NikicPhpParser(
            $this->createMock(Parser::class),
            new AstFileReferenceInMemoryCache(),
            $this->createMock(TypeResolver::class),
            [],
            []
        );
    }

    public function testParseWithInvalidData(): void
    {
        $this->expectException(TypeError::class);
        $this->parser->parseFile(new stdClass());
    }

    public function testParseDoesNotIgnoreUsesByDefault(): void
    {
        $parser = $this->createParser();

        $filePath = __DIR__.'/Fixtures/CountingUseStatements.php';
        self::assertCount(1, $parser->parseFile($filePath)->dependencies);
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testParseAttributes(): void
    {
        $parser = $this->createParser();

        $filePath = __DIR__.'/Fixtures/Attributes.php';
        $astFileReference = $parser->parseFile($filePath);
        $astClassReferences = $astFileReference->classLikeReferences;
        self::assertCount(7, $astClassReferences[0]->dependencies);
        self::assertCount(2, $astClassReferences[1]->dependencies);
        self::assertCount(1, $astClassReferences[2]->dependencies);
    }

    public function testParseTemplateTypes(): void
    {
        $typeResolver = new TypeResolver();
        $parser = new NikicPhpParser(
            (new ParserFactory())->create(
                ParserFactory::ONLY_PHP7,
                new Lexer()
            ),
            new AstFileReferenceInMemoryCache(),
            $typeResolver,
            [],
            [new AnnotationReferenceExtractor($typeResolver)]
        );

        $filePath = __DIR__.'/Fixtures/TemplateTypes.php';
        $astFileReference = $parser->parseFile($filePath);
        $astClassReferences = $astFileReference->classLikeReferences;
        self::assertCount(0, $astClassReferences[0]->dependencies);
    }

    public function testParseClassDocTags(): void
    {
        $parser = $this->createParser();
        $filePath = __DIR__.'/Fixtures/DocTags.php';
        $astFileReference = $parser->parseFile($filePath);

        self::assertCount(2, $astFileReference->classLikeReferences);
        $classesByName = $this->refsByName($astFileReference->classLikeReferences);

        $this->assertSame(
            [
                '@internal' => [''],
                '@note' => ['Note one', 'Note two'],
            ],
            $classesByName['TaggedThing']->tags
        );
        $this->assertSame([], $classesByName['UntaggedThing']->tags);
    }

    public function testMarkDeprecated(): void
    {
        $parser = $this->createParser(
            [
                new FunctionLikeExtractor(new TypeResolver()),
            ]
        );

        $filePath = __DIR__.'/Fixtures/Deprecations.php';
        $astFileReference = $parser->parseFile($filePath);

        $classesByName = $this->refsByName($astFileReference->classLikeReferences);
        self::assertCount(4, $classesByName);

        /**
         * @var ClassLikeReference $deprecatedClass
         * @var ClassLikeReference $undeprecatedClass
         */
        $deprecatedClass = $classesByName['DeprecatedClass'];
        $undeprecatedClass = $classesByName['UndeprecatedClass'];

        // All dependencies of a deprecated class should have a deprecation marker in their context
        foreach ($deprecatedClass->dependencies as $dep) {
            $this->assertTrue($dep->context->deprecated);
        }

        // A deprecated method should cause deprecation markers in the dependency context
        $countDeprecated = 0;
        foreach ($undeprecatedClass->dependencies as $dep) {
            if ('deprecatedMethod' === $dep->context->function) {
                $this->assertTrue($dep->context->deprecated);
                ++$countDeprecated;
            } else {
                $this->assertFalse($dep->context->deprecated);
            }
        }

        $this->assertSame(2, $countDeprecated);
    }

    public function testParseFunctionDocTags(): void
    {
        $parser = $this->createParser();
        $filePath = __DIR__.'/Fixtures/Functions.php';
        $astFileReference = $parser->parseFile($filePath);

        self::assertCount(2, $astFileReference->functionReferences);
        $functionsByName = $this->refsByName($astFileReference->functionReferences);

        $this->assertSame(
            ['@param' => ['string $foo', 'string $bar']],
            $functionsByName['taggedFunction()']->tags
        );
        $this->assertSame([], $functionsByName['untaggedFunction()']->tags);
    }

    /**
     * @param TokenReferenceInterface[] $refs
     *
     * @return TokenReferenceInterface[]
     */
    private function refsByName(array $refs): array
    {
        $refsByName = [];

        foreach ($refs as $ref) {
            $name = preg_replace('/^.*\\\\(\w+(\(\))?)$/', '$1', $ref->getToken()->toString());
            $refsByName[$name] = $ref;
        }

        return $refsByName;
    }

    private function createParser($extractors = []): NikicPhpParser
    {
        $typeResolver = new TypeResolver();
        $parser = new NikicPhpParser(
            (new ParserFactory())->create(
                ParserFactory::ONLY_PHP7,
                new Lexer()
            ),
            new AstFileReferenceInMemoryCache(),
            $typeResolver,
            $extractors
        );

        return $parser;
    }
}
