<?php

declare(strict_types=1);

namespace Pchapl\CodePrint\Printer;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

final class Extended extends Standard
{
    /** @phpstan-param array<Node> $nodes */
    protected function pMaybeMultiline(array $nodes, bool $trailingComma = false, ?bool &$enable = false): string
    {
        if ($enable === false) {
            return parent::pMaybeMultiline($nodes, $trailingComma);
        }

        if ($this->hasNodeWithComments($nodes)) {
            $enable = true;
            return $this->pCommaSeparatedMultiline($nodes, $trailingComma) . $this->nl;
        }

        $oneLine = $this->pCommaSeparated($nodes);

        if (strlen($oneLine) + $this->indentLevel > 120) {
            $enable = true;
            return $this->pCommaSeparatedMultiline($nodes, true) . $this->nl;
        }

        $enable = false;

        return $oneLine;
    }

    protected function pStmt_ClassMethod(Stmt\ClassMethod $node): string
    {
        $attrGroups = $this->pAttrGroups($node->attrGroups);
        $modifiers = $this->pModifiers($node->flags);
        $function = 'function ' . ($node->byRef ? '&' : '') . $node->name;
        $params = '(' . $this->pMaybeMultiline($node->params, true, $multiline) . ')';
        $returnType = null !== $node->returnType ? ': ' . $this->p($node->returnType) : '';

        $bodySeparator = $multiline ? ' ' : $this->nl;

        $body = null !== $node->stmts
            ? $bodySeparator . '{' . $this->pStmts($node->stmts) . $this->nl . '}'
            : ';';

        return $attrGroups . $modifiers . $function . $params . $returnType . $body;
    }

    /** @phpstan-param array<Node> $nodes */
    protected function pStmtsSplitted(array $nodes, bool $indent = true): string
    {
        if ($indent) {
            $this->indent();
        }

        $first = true;

        $result = '';
        foreach ($nodes as $node) {
            if ($first) {
                $first = false;
            } else {
                $result .= "\n";
            }
            $comments = $node->getComments();
            if ($comments) {
                $result .= $this->nl . $this->pComments($comments);
                if ($node instanceof Stmt\Nop) {
                    continue;
                }
            }

            $result .= $this->nl . $this->p($node);
        }

        if ($indent) {
            $this->outdent();
        }

        return $result;
    }

    /** @phpstan-param string $afterClassToken */
    protected function pClassCommon(Stmt\Class_ $node, mixed $afterClassToken): string
    {
        return $this->pAttrGroups($node->attrGroups, $node->name === null)
            . $this->pModifiers($node->flags)
            . 'class' . $afterClassToken
            . (null !== $node->extends ? ' extends ' . $this->p($node->extends) : '')
            . (!empty($node->implements) ? ' implements ' . $this->pCommaSeparated($node->implements) : '')
            . $this->nl . '{' . $this->pStmtsSplitted($node->stmts) . $this->nl . '}';
    }

    protected function pStmt_Class(Stmt\Class_ $node): string
    {
        return parent::pStmt_Class($node) . "\n";
    }
}
