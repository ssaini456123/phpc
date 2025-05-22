<?php

function entrypt($name, $echoOut)
{
    $f = fopen($name, "r") or die("File not found.");
    $lineNo = 1;
    $t_arr_arr = null;

    while (($line = fgets($f)) !== false) {
        $lexer = new Lexer($line);
        $tokenized = $lexer->tokenize($lineNo);
        $t_arr_arr[] = $tokenized;
        $lineNo++;
    }

    $p = new Parser($t_arr_arr);
    $p->parseTokens();

    fclose($f);
}

entrypt("main.c", false);

class TokenType
{
    const OPEN_PAREN = 'OPEN_PAREN';
    const CLOSE_PAREN = 'CLOSE_PAREN';
    const POUND = 'POUND';
    const IDENT = 'IDENT';
    const NUMERICAL = 'NUMERICAL';
    const OPEN_ANGLE_BRACKET = 'OPEN_ANGLE_BRACKET';
    const CLOSE_ANGLE_BRACKET = 'CLOSE_ANGLE_BRACKET';
    const DOT = 'DOT';
    const SEMICOLON = 'SEMICOLON';
    const DOUBLE_QUOTATION_MARK = '"';
    const SINGLE_QUOTATION_MARK = '\'';
    const STAR_OPERATOR = '*';
}

class Token
{
    public $type;
    public $text;
    public $pos;
    public $lineNo;

    public function __construct($type, $text, $pos, $lineNo)
    {
        $this->type = $type;
        $this->text = $text;
        $this->pos = $pos;
        $this->lineNo = $lineNo;
    }
}

class Lexer
{
    private $pos = 0;
    private $source;
    private $tokens;

    public function __construct($source)
    {
        $this->tokens = array_unique([]);
        $this->source = $source;
    }

    public function next_slot_open($src, $pos)
    {
        $next = $pos + 1;
        $len = strlen($src);
        if ($next > $len) {
            return false;
        } else {
            return true;
        }
    }


    public function tokenize($lineNo)
    {
        $pos = $this->pos;
        $src = $this->source;
        $srcLen = strlen($src);
        $tok = null;

        while ($pos < $srcLen) {

            $tok = $src[$pos];
            $ident = "";
            $numerical = "";

            if (ctype_alpha($tok)) {
                $ident .= $tok;
                $next = $pos + 1;

                while ($this->next_slot_open($src, $pos) && ctype_space($src[$next])) $next++;

                while ($this->next_slot_open($src, $next) && ctype_alpha($src[$next])) {
                    $nexttok = $src[$next];
                    $ident .= $nexttok;
                    $next++;
                }

                $pos = $next;
                $this->tokens[] = new Token(TokenType::IDENT, $ident, $pos, $lineNo);
            } else if (ctype_alnum($tok)) {
                $numerical .= $tok;
                $next = $pos + 1;

                while ($this->next_slot_open($src, $pos) && ctype_space($src[$next])) $next++;

                while ($this->next_slot_open($src, $next) && ctype_alnum($src[$next])) {
                    $nexttok = $src[$next];
                    $numerical .= $nexttok;
                    $next++;
                }
                $pos = $next;
                $this->tokens[] = new Token(TokenType::NUMERICAL, $numerical, $pos, $lineNo);
            }

            switch ($tok) {
                case '(':
                    $this->tokens[] = new Token(TokenType::OPEN_PAREN, '(', $pos, $lineNo);
                    break;
                case ')':
                    $this->tokens[] = new Token(TokenType::CLOSE_PAREN, ')', $pos, $lineNo);
                    break;
                case '#':
                    $this->tokens[] = new Token(TokenType::POUND, '#', $pos, $lineNo);
                    break;
                case '>':
                    $this->tokens[] = new Token(TokenType::CLOSE_ANGLE_BRACKET, '>', $pos, $lineNo);
                    break;
                case '<':
                    $this->tokens[] = new Token(TokenType::OPEN_ANGLE_BRACKET, '<', $pos, $lineNo);
                    break;
                case '.':
                    $this->tokens[] = new Token(TokenType::DOT, '.', $pos, $lineNo);
                    break;
                case ';':
                    $this->tokens[] = new Token(TokenType::SEMICOLON, ';', $pos, $lineNo);
                    break;
                case '\'':
                    $this->tokens[] = new Token(TokenType::SINGLE_QUOTATION_MARK, '\'', $pos, $lineNo);
                    break;
                case '"':
                    $this->tokens[] = new Token(TokenType::DOUBLE_QUOTATION_MARK, '"', $pos, $lineNo);
                    break;
                case '*':
                    $this->tokens[] = new Token(TokenType::STAR_OPERATOR, '*', $pos, $lineNo);
                    break;
                default:
                    //fprintf(STDERR, "Unknown tok (" . $tok . ")\n");
                    break;
            }
            $pos++;
        }

        return $this->tokens;
    }
}


abstract class AstNode {};

class IncludeDirective extends AstNode
{
    public $header;

    public function getHeader()
    {
        return $this->header;
    }
}

class ParserContext
{
    public $pos;
    public $tokens;

    public function __construct($pos, $tokens)
    {
        $this->pos = $pos;
        $this->tokens = $tokens;
    }

    public function getTokens()
    {
        return $this->tokens;
    }
}


class Parser
{
    private $pCtx;

    public function __construct($tok_arr)
    {
        $toks = $tok_arr;
        $this->pCtx = new ParserContext(0, $toks);
    }

    public function getParserContext()
    {
        return $this->pCtx;
    }

    public function parseTokens()
    {
        // Loop through the lex'd tokens
        $ctx = $this->getParserContext();
        $inner_toks = [];
        foreach ($ctx->getTokens() as $token_list) {
            $arrsz = sizeof($token_list);
            if ($arrsz == 0) {
                continue;
            }
            $inner_toks[][] = $token_list;
        }

        print_r($inner_toks);
    }

    public function parsePreprocessorDirective()
    {
        $toks = $this->getParserContext()->getTokens();

        var_dump($toks);
    }
}
