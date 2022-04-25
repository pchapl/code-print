<?php

declare(strict_types=1);

namespace Pchapl\CodePrint\Tests\Printer;

use Pchapl\CodeGen\Entity\Dto;
use Pchapl\CodePrint\Printer\PrettyPrinter;
use PhpParser\Comment;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\UnionType;
use PHPUnit\Framework\TestCase;

final class PrettyPrinterTest extends TestCase
{
    private PrettyPrinter $printer;

    protected function setUp(): void
    {
        $this->printer = new PrettyPrinter();
    }

    private const EXPECTED = <<<'PHP'
<?php

declare(strict_types=1);

class tc
{
}

PHP;

    public function testPrint(): void
    {
        $str = $this->printer->print(new Dto(new Class_('tc')));

        self::assertSame(self::EXPECTED, $str);
    }

    private const EXPECTED_WITH_STMTS = <<<'PHP'
<?php

declare(strict_types=1);

class tc
{
    function tm1(): void
    {
    }

    function tm2(): void
    {
        qwertyuiop();
        qwertyuiop();
        qwertyuiop();
    }
}

PHP;

    public function testPrintWithStmts(): void
    {
        $stmts = [
            new ClassMethod('tm1', ['returnType' => 'void']),
            new ClassMethod('tm2', [
                'returnType' => 'void',
                'stmts' => [
                    new Expression(new FuncCall(new Name('qwertyuiop'))),
                    new Expression(new FuncCall(new Name('qwertyuiop'))),
                    new Expression(new FuncCall(new Name('qwertyuiop'))),
                ]
            ]),
        ];

        $str = $this->printer->print(new Dto(new Class_('tc', ['stmts' => $stmts])));

        self::assertSame(self::EXPECTED_WITH_STMTS, $str);
    }

    private const EXPECTED_WITH_LONG_LIST = <<<'PHP'
<?php

declare(strict_types=1);

class tc
{
    /** @return void */
    function tm0(
        // comment
        $parameterWithLongName1,
        $parameterWithLongName2,
        $parameterWithLongName3,
    ): void {
    }

    function tm1($parameterWithLongName1, $parameterWithLongName2, $parameterWithLongName3): void
    {
    }

    function tm2(
        $parameterWithLongName1,
        $parameterWithLongName2,
        $parameterWithLongName3,
        $parameterWithExtraLongNameEvenMoreThanLong,
    ): void {
    }

    abstract function tm3($parameterWithLongName1, $parameterWithLongName2, $parameterWithLongName3): void;

    /** @return void */
    abstract function tm4(
        $parameterWithLongName1,
        $parameterWithLongName2,
        $parameterWithLongName3,
        $parameterWithExtraLongNameEvenMoreThanLong,
    ): void;
}

PHP;

    public function testPrintLongList(): void
    {
        $stmts = [
            new ClassMethod(
                'tm0',
                [
                    'returnType' => 'void',
                    'params' => [
                        new Param(
                            var:        new Variable('parameterWithLongName1'),
                            attributes: ['comments' => [new Comment('// comment')]],
                        ),
                        new Param(new Variable('parameterWithLongName2')),
                        new Param(new Variable('parameterWithLongName3')),
                    ],
                ],
                ['comments' => [new Comment('/** @return void */')]],
            ),
            new ClassMethod('tm1', [
                'returnType' => 'void',
                'params' => [
                    new Param(new Variable('parameterWithLongName1')),
                    new Param(new Variable('parameterWithLongName2')),
                    new Param(new Variable('parameterWithLongName3')),
                ],
            ]),
            new ClassMethod('tm2', [
                'returnType' => 'void',
                'params' => [
                    new Param(new Variable('parameterWithLongName1')),
                    new Param(new Variable('parameterWithLongName2')),
                    new Param(new Variable('parameterWithLongName3')),
                    new Param(new Variable('parameterWithExtraLongNameEvenMoreThanLong')),
                ],
            ]),
            new ClassMethod('tm3', [
                'returnType' => 'void',
                'params' => [
                    new Param(new Variable('parameterWithLongName1')),
                    new Param(new Variable('parameterWithLongName2')),
                    new Param(new Variable('parameterWithLongName3')),
                ],
                'stmts' => null,
                'flags' => Class_::MODIFIER_ABSTRACT,
            ]),
            new ClassMethod(
                'tm4',
                [
                    'returnType' => 'void',
                    'params' => [
                        new Param(new Variable('parameterWithLongName1')),
                        new Param(new Variable('parameterWithLongName2')),
                        new Param(new Variable('parameterWithLongName3')),
                        new Param(new Variable('parameterWithExtraLongNameEvenMoreThanLong')),
                    ],
                    'stmts' => null,
                    'flags' => Class_::MODIFIER_ABSTRACT,
                ],
                ['comments' => [new Comment('/** @return void */')]],
            ),
        ];

        $str = $this->printer->print(new Dto(new Class_('tc', ['stmts' => $stmts])));

        self::assertSame(self::EXPECTED_WITH_LONG_LIST, $str);
    }

    private const EXPECTED_WITH_LONG_FIELDS = <<<'PHP'
<?php

declare(strict_types=1);

class tc
{
    public function __construct(
        protected readonly string|resource $parameterWithLongName1,
        protected readonly string|resource $parameterWithLongName2,
        protected readonly string|resource $parameterWithLongName3,
    ) {
    }
}

PHP;

    public function testPrintLongFields(): void
    {
        $readonlyParam = static fn (string $name): Param => new Param(
            var:   new Variable($name),
            type:  new UnionType([new Identifier('string'), new Identifier('resource')]),
            flags: Class_::MODIFIER_PROTECTED | Class_::MODIFIER_READONLY,
        );

        $stmts = [
            new ClassMethod(
                '__construct',
                [
                    'flags' => Class_::MODIFIER_PUBLIC,
                    'params' => array_map(
                        $readonlyParam,
                        [
                            'parameterWithLongName1',
                            'parameterWithLongName2',
                            'parameterWithLongName3',
                        ],
                    ),
                ],
            ),
        ];

        $str = $this->printer->print(new Dto(new Class_('tc', ['stmts' => $stmts])));

        self::assertSame(self::EXPECTED_WITH_LONG_FIELDS, $str);
    }

    private const EXPECTED_WITH_LONG_FIELDS_80 = <<<'PHP'
<?php

declare(strict_types=1);

class tc
{
    public function __construct(
        private string $id,
        private string $name,
        private int $index,
        private int $oneMoreField4294967296,
    ) {
    }

    #[TestAttr]
    public function foo(string $parameterWithLongName1, string $parameterWithLongName2, string $parameterWithLongName3_)
    {
    }

    #[TestAttr]
    public function bar(
        string $parameterWithLongName1,
        string $parameterWithLongName2,
        string $parameterWithLongName3__,
    ) {
    }
}

PHP;

    public function testPrintLongFields80(): void
    {
        $param = static fn (string $name, string $type): Param => new Param(
            var:   new Variable($name),
            type:  $type,
            flags: Class_::MODIFIER_PRIVATE,
        );

        $stmts = [
            new ClassMethod(
                '__construct',
                [
                    'flags' => Class_::MODIFIER_PUBLIC,
                    'params' => array_map(
                        $param,
                        ['id', 'name', 'index', 'oneMoreField4294967296'],
                        ['string', 'string', 'int', 'int'],
                    ),
                ],
            ),
            new ClassMethod(
                'foo',
                [
                    'flags' => Class_::MODIFIER_PUBLIC,
                    'params' => [
                        // line length = 120
                        new Param(new Variable('parameterWithLongName1'), type: 'string'),
                        new Param(new Variable('parameterWithLongName2'), type: 'string'),
                        new Param(new Variable('parameterWithLongName3_'), type: 'string'),
                    ],
                    'attrGroups' => [
                        new AttributeGroup(
                            [
                                new Attribute(new Name('TestAttr')),
                            ]
                        )
                    ],
                ],
            ),
            new ClassMethod(
                'bar',
                [
                    'flags' => Class_::MODIFIER_PUBLIC,
                    'params' => [
                        // line length = 121
                        new Param(new Variable('parameterWithLongName1'), type: 'string'),
                        new Param(new Variable('parameterWithLongName2'), type: 'string'),
                        new Param(new Variable('parameterWithLongName3__'), type: 'string'),
                    ],
                    'attrGroups' => [
                        new AttributeGroup(
                            [
                                new Attribute(new Name('TestAttr')),
                            ]
                        )
                    ],
                ],
            ),
        ];

        $entity = new Dto(new Class_('tc', ['stmts' => $stmts]));
        $str = $this->printer->print($entity);

        self::assertSame(self::EXPECTED_WITH_LONG_FIELDS_80, $str);
    }
}
