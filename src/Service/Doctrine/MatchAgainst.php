<?php

declare(strict_types=1);

namespace App\Service\Doctrine;

use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * "MATCH_AGAINST" "(" {StateFieldPathExpression ","}* InParameter {Literal}? ")".
 *
 * To add MATCH_AGAINST in DQL
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MatchAgainst extends FunctionNode
{
    protected array $columns = [];
    protected InputParameter $needle;
    protected Literal $mode;

    /**
     * Parse.
     *
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        do {
            $this->columns[] = $parser->StateFieldPathExpression();
            $parser->match(TokenType::T_COMMA);
        } while ($parser->getLexer()->isNextToken(TokenType::T_IDENTIFIER));

        $this->needle = $parser->InParameter();

        while ($parser->getLexer()->isNextToken(TokenType::T_STRING)) {
            $this->mode = $parser->Literal();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    /**
     * Get SQL.
     *
     * @throws ASTException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $haystack = null;

        $first = true;
        foreach ($this->columns as $column) {
            $first ? $first = false : $haystack .= ', ';
            $haystack .= $column->dispatch($sqlWalker);
        }

        $query = 'MATCH('.$haystack.
            ') AGAINST ('.$this->needle->dispatch($sqlWalker);

        if ($this->mode) {
            $query .= ' '.$this->mode->value.' )';
        } else {
            $query .= ' )';
        }

        return $query;
    }
}
