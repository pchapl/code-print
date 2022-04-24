<?php

declare(strict_types=1);

namespace Pchapl\CodePrint\Tests\Printer;

use Pchapl\CodeGen\Entity\Dto;
use Pchapl\CodePrint\Printer\PrettyPrinter;
use PhpParser\Comment;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
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
}
