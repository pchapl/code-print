<?php

declare(strict_types=1);

namespace Pchapl\CodePrint\Tests\Printer;

use Pchapl\CodePrint\Printer\Extended;
use PhpParser\Comment;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\TraitUse;
use PHPUnit\Framework\TestCase;

final class ExtendedTest extends TestCase
{
    private Extended $printer;

    protected function setUp(): void
    {
        $this->printer = new Extended();
    }

    private const EXPECTED = <<<'PHP'
class tc
{
    function tm1(): void
    {
        $qwe;
        $asd;
        zxc();
    }

    function tm2(): void
    {
        // qwe
        $qwe;
        // asd
        $asd;
        // z
        // x
        // c
        zxc();
    }

    function &methodByRef()
    {
    }
}

PHP;

    public function testStmtsSplit(): void
    {
        $stmts = [
            new ClassMethod(
                'tm1',
                [
                    'returnType' => 'void',
                    'stmts' => [
                        new Expression(new Variable('qwe')),
                        new Expression(new Variable('asd')),
                        new Expression(new FuncCall(new Name('zxc'))),
                    ],
                ]
            ),
            new ClassMethod(
                'tm2',
                [
                    'returnType' => 'void',
                    'stmts' => [
                        new Expression(new Variable('qwe'), ['comments' => [new Comment('// qwe')]]),
                        new Expression(new Variable('asd'), ['comments' => [new Comment('// asd')]]),
                        new Nop(['comments' => [new Comment('// z')]]),
                        new Nop(['comments' => [new Comment('// x'), new Comment('// c')]]),
                        new Expression(new FuncCall(new Name('zxc'))),
                    ],
                ],
            ),
            new ClassMethod('methodByRef', ['byRef' => true]),
        ];

        $str = $this->printer->prettyPrint([new Class_('tc', ['stmts' => $stmts])]);

        self::assertSame(self::EXPECTED, $str);
    }

    public function testPStmts(): void
    {
        $classStmts = [
            'TraitUse' => new TraitUse([new Name('TraitName')]),
            'ClassConst' => new ClassConst([new Const_('CONST_NAME', new String_('val'))]),
            'Property' => new Property(Class_::MODIFIER_PUBLIC, [new PropertyProperty('prop')]),
            'ClassMethod' => new ClassMethod('tMethod'),
        ];

        foreach ($classStmts as $key => $stmt) {
            $str = $this->printer->prettyPrint([new Class_('tc', ['stmts' => [$stmt, $stmt]])]);

            self::assertStringContainsString("\n\n", $str, $key);
        }
        $nonClassStmts = [
            'Expression' => new Expression(new Variable('asd')),
            'Nop' => new Nop(['comments' => [new Comment('// z')]]),
            'Function' => new Function_('fun'),
            'For' => new For_(),
        ];

        foreach ($nonClassStmts as $key => $stmt) {
            $str = $this->printer->prettyPrint([new Class_('tc', ['stmts' => [$stmt, $stmt]])]);

            self::assertStringNotContainsString("\n\n", $str, $key);
        }
    }
}
