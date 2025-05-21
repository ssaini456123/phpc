<?php

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
}

class Token
{
    public $type;
    public $text;
    public $pos;

    public function __construct($type, $text, $pos)
    {
        $this->type = $type;
        $this->text = $text;
        $this->pos = $pos;
    }
}

class Lexer
{
    private $pos = 0;
    private $source;
    private $tokens;

    public function __construct($source)
    {
        $this->tokens = [];
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


    public function tokenize()
    {
        $pos = $this->pos;
        $src = $this->source;
        $srcLen = strlen($src);
        $tok = null;

        while ($pos < $srcLen) {
            $tok = $src[$pos];
            $ident = null;
            $numerical = null;

            if (ctype_alpha($tok)) {
                $next = $pos + 1;

                while (
                    $this->next_slot_open($src, $pos)
                    && ctype_alpha($src[$next])
                ) {
                    $ident .= $src[$next];
                    $next++;
                }

                $pos = $next;
                continue;
            }

            if (is_numeric($tok)) {
                $next = $pos + 1;
                // alr cuh maybe its a numerical constant
                //wefwefwefwef
                // dude fuck phpefw efefwe7 holy FUCK
                //array_key_firwst'[swe;fw
                //fwfef[wefswefefwefw
                while (
                    $this->next_slot_open($src, $pos)
                    && ctype_alnum($next)
                ) {
                    $numerical .= $src[$next];
                    $next++;
                }

                $pos = $next;
                continue;
            }



            if ($ident != null) {
                $this->tokens[] = new Token(TokenType::IDENT, $ident, $pos);
                $pos = $next;
            } elseif ($numerical != null) {
                $this->tokens[] = new Token(TokenType::IDENT, $ident, $pos);
                $pos = $next;
            }

            switch ($tok) {
                case '(':
                    $this->tokens[] = new Token(TokenType::OPEN_PAREN, '(', $pos);
                    break;
                case ')':
                    $this->tokens[] = new Token(TokenType::CLOSE_PAREN, ')', $pos);
                    break;
                case '#':
                    $this->tokens[] = new Token(TokenType::POUND, '#', $pos);
                    break;
                case '>':
                    $this->tokens[] = new Token(TokenType::CLOSE_ANGLE_BRACKET, '>', $pos);
                    break;
                case '<':
                    $this->tokens[] = new Token(TokenType::OPEN_ANGLE_BRACKET, '<', $pos);
                    break;
                case '.':
                    $this->tokens[] = new Token(TokenType::DOT, '.', $pos);
                    break;
                default:
                    break;
            }

            $pos++;
        }

        return $this->tokens;
    }
}

$lex = new Lexer("#include <stdio.h>");
$t = $lex->tokenize();
print_r($t);
