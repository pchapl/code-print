<?php

declare(strict_types=1);

namespace Pchapl\CodePrint\Printer;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\PrettyPrinter\Standard;

final class Extended extends Standard
{
    private const MAX_LINE_LENGTH = 120;

    /**
     * @param Node\Param[] $params
     */
    private function maybeMultilineParams(array $params, string $stringBeforeParams): string
    {
        $lengthBeforeParams = strlen(substr(strrchr($stringBeforeParams, "\n") ?: " $stringBeforeParams", 1));

        $maybeMultiline = $this->pMaybeMultiline($params, true);

        $parentheses = 2;
        $length = $lengthBeforeParams + $parentheses + strlen($maybeMultiline);

        if ($length <= self::MAX_LINE_LENGTH || str_contains($maybeMultiline, "\n")) {
            return $maybeMultiline;
        }

        return $this->pCommaSeparatedMultiline($params, true) . $this->nl;
    }

    protected function pStmt_ClassMethod(ClassMethod $node): string
    {
        $attrGroups = $this->pAttrGroups($node->attrGroups);
        $modifiers = $this->pModifiers($node->flags);
        $function = 'function ' . ($node->byRef ? '&' : '') . $node->name;

        $params = '(' . $this->maybeMultilineParams($node->params, "$attrGroups$modifiers$function") . ')';

        $returnType = null !== $node->returnType ? ': ' . $this->p($node->returnType) : '';

        $bodySeparator = str_contains($params, "\n") ? ' ' : $this->nl;

        $body = null !== $node->stmts
            ? $bodySeparator . '{' . $this->pStmts($node->stmts) . $this->nl . '}'
            : ';';

        return $attrGroups . $modifiers . $function . $params . $returnType . $body;
    }

    /** @phpstan-param array<Node> $nodes */
    private function pStmtsSplit(array $nodes): string
    {
        $this->indent();

        $stmts = array_map(
            fn (Node $node): string => (
                $node->getComments()
                    ? $this->nl . $this->pComments($node->getComments())
                    : ''
                )
                . $this->nl
                . $this->p($node),
            $nodes,
        );

        $this->outdent();

        return implode("\n", $stmts);
    }

    private function isNodeIsClassSubNode(Node $node): bool
    {
        $classSubNodes = [TraitUse::class, ClassConst::class, Property::class, ClassMethod::class];
        foreach ($classSubNodes as $subNode) {
            if ($node instanceof $subNode) {
                return true;
            }
        }

        return false;
    }

    protected function pStmts(array $nodes, bool $indent = true): string
    {
        foreach ($nodes as $node) {
            if ($this->isNodeIsClassSubNode($node)) {
                return $this->pStmtsSplit($nodes);
            }
        }

        return parent::pStmts($nodes, $indent);
    }

    protected function pStmt_Class(Stmt\Class_ $node): string
    {
        return parent::pStmt_Class($node) . "\n";
    }
}
