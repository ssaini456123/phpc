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
    const SEMICOLON = 'SEMICOLON';
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
                $this->tokens[] = new Token(TokenType::IDENT, $ident, $pos);
            }

            if (ctype_alnum($tok)) {
                $numerical .= $tok;
                $next = $pos + 1;

                while ($this->next_slot_open($src, $next) && ctype_alnum($src[$next])) {
                    $nexttok = $src[$next];
                    $numerical .= $nexttok;
                    $next++;
                }


                $pos = $next;
                $this->tokens[] = new Token(TokenType::NUMERICAL, $numerical, $pos);
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
                case ';':
                    $this->tokens[] = new Token(TokenType::SEMICOLON, ';', $pos);
                    break;
                default:
                    break;
            }
            $pos++;
        }
        return $this->tokens;
    }
}

$lex = new Lexer("12 1221");
$t = $lex->tokenize();
print_r($t);
