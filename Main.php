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
    const DOUBLE_QUOTATION_MARK = '"';
    const SINGLE_QUOTATION_MARK = '\'';
    const STAR_OPERATOR = '*';
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
                case '\'':
                    $this->tokens[] = new Token(TokenType::SINGLE_QUOTATION_MARK, '\'', $pos);
                    break;
                case '"':
                    $this->tokens[] = new Token(TokenType::DOUBLE_QUOTATION_MARK, '"', $pos);
                    break;
                case '*':
                    $this->tokens[] = new Token(TokenType::STAR_OPERATOR, '*', $pos);
                    break;
                default:
                    fprintf(STDERR, "Unknown tok (" . $tok . ")\n");
                    break;
            }
            $pos++;
        }
        return $this->tokens;
    }
}


function entrypt($name, $echoOut)
{
    $f = fopen($name, "r") or die("File not found.");
    $lineNo = 1;
    $t_arr_arr = null;

    while (($line = fgets($f)) !== false) {
        $lexer = new Lexer($line);
        $tokenized = $lexer->tokenize();
        $t_arr_arr[] = $tokenized;
        $lineNo++;
        echo ($line);
    }

    print_r($t_arr_arr);
    fclose($f);
}

entrypt("main.c", false);
