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
    const COMMA = 'COMMA';
    const SEMICOLON = 'SEMICOLON';
    const DOUBLE_QUOTATION_MARK = 'DOUBLE_QUOTATION_MARK';
    const SINGLE_QUOTATION_MARK = 'SINGLE_QUOTATION_MARK';
    const STAR_OPERATOR = 'STAR_OPERATOR';
    const OPEN_CURLY_BRACE = 'OPEN_CURLY_BRACE';
    const CLOSE_CURLY_BRACE = 'CLOSE_CURLY_BRACE';
    const OPEN_RECTANGULAR_BRACE = 'OPEN_RECTANGULAR_BRACE';
    const CLOSE_RECTANGULAR_BRACE = 'CLOSE_RECTANGULAR_BRACE';
    const INCLUDE_PATH = 'INCLUDE_PATH';
    const STRING_LIT = 'STRING_LIT';

    //sigh
    const BOOL_TYPE = 'BOOL_TYPE';
    const CHAR_TYPE = 'CHAR_TYPE';
    const INT_TYPE = 'INT_TYPE';
    const LONG_TYPE = 'LONG_TYPE';
    const DOUBLE_TYPE = 'DOUBLE_TYPE';
    const FLOAT_TYPE = 'FLOAT_TYPE';
    const BOOL_PTR_TYPE = 'BOOL_PTR_TYPE';
    const CHAR_PTR_TYPE = 'CHAR_PTR_TYPE';
    const INT_PTR_TYPE = 'INT_PTR_TYPE';
    const LONG_PTR_TYPE = 'LONG_PTR_TYPE';
    const FLOAT_PTR_TYPE = 'FLOAT_PTR_TYPE';
    const VOID_TYPE = 'VOID_TYPE';
    const DOUBLE_PTR_TYPE = 'DOUBLE_PTR_TYPE';
    const VOID_PTR = 'VOID_PTR';

    const NATIVE_C_TY = ['bool', 'int', 'char', 'long', 'double', 'float', 'void'];
    const NATIVE_C_TY_PTR = ['bool*', 'int*', 'char*', 'long*', 'double*', 'float*', 'void*'];

    // arithmetic
    const ADD = 'ADD';
    const SUBTRACT = 'SUBTRACT';
    const DIVIDE = 'DIVIDE';
    const MULTIPLY = 'MULTIPLY';
}

class Token
{
    private $type;
    private $text;
    private $pos;
    private $lineNo;

    public function __construct($type, $text, $pos, $lineNo)
    {
        $this->type = $type;
        $this->text = $text;
        $this->pos = $pos;
        $this->lineNo = $lineNo;
    }

    public function get_line_no()
    {
        return $this->lineNo;
    }

    public function get_pos()
    {
        return $this->pos;
    }

    public function get_text()
    {
        return $this->text;
    }

    public function get_type()
    {
        return $this->type;
    }
}

class Lexer
{
    private $pos = 0;
    private $lineNo = 1;

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

    public function match_c_ty($ident)
    {
        // format: bool, str|null, TokenType|null
        $r = [];


        $native_c_ty = in_array($ident, TokenType::NATIVE_C_TY);
        $ty_str = null;
        $tt = null;

        switch ($ident) {
            case 'void':
                $ty_str = 'void';
                $tt = TokenType::VOID_TYPE;
                break;
            case 'bool':
                $ty_str = 'bool';
                $tt = TokenType::BOOL_TYPE;
                break;
            case 'int':
                $ty_str = 'int';
                $tt = TokenType::INT_TYPE;
                break;
            case 'char':
                $ty_str = 'char';
                $tt = TokenType::CHAR_TYPE;
                break;
            case 'long':
                $ty_str = 'long';
                $tt = TokenType::LONG_TYPE;
                break;
            case 'double':
                $ty_str = 'double';
                $tt = TokenType::DOUBLE_TYPE;
                break;
            case 'float':
                $ty_str = 'float';
                $tt = TokenType::FLOAT_TYPE;
                break;
        }

        $r[0] = $native_c_ty;
        $r[1] = $ty_str;
        $r[2] = $tt;

        return $r;
    }

    public function tokenize()
    {
        $lineNo = $this->lineNo;
        $pos = $this->pos;
        $src = $this->source;
        $srcLen = strlen($src);
        $tok = null;
        $last_token = null;


        while ($pos < $srcLen) {

            $tok = $src[$pos];
            $ident = "";
            $numerical = "";

            //string lit
            if ($tok === "\"") {
                $ident = '"';
                $next = $pos + 1;
                $closed = false;
                while ($this->next_slot_open($src, $next) && !$closed) {
                    if ($src[$next] === '"') {
                        $closed = true;
                    } else if (!$closed) {
                        $nexttok = $src[$next];
                        $ident .= $nexttok;
                    }

                    $next++;
                }

                $ident .= '"';
                $pos = $next;


                $this->tokens[] = new Token(TokenType::STRING_LIT, $ident, $pos, $lineNo);
                continue;
            }

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

                // Is the ident a native c ty?
                $ty_match = $this->match_c_ty($ident);
                $c_ty = $ty_match[0];


                if ($c_ty) {

                    $frwrd = $next + 1;
                    $ty_str = $ty_match[1];
                    $before = $next;

                    if ($this->next_slot_open($src, $next)) {
                        $back = $src[$before];
                        $p_ptr = $src[$frwrd];

                        if ($p_ptr === "*" || $back === "*") {
                            $ty_str .= '*';
                            $pos = $next;

                            switch ($ty_str) {
                                case 'bool*':
                                    $this->tokens[] = new Token(TokenType::BOOL_PTR_TYPE, $ty_str, $pos, $lineNo);
                                    break;

                                case 'int*':
                                    $this->tokens[] = new Token(TokenType::INT_PTR_TYPE, $ty_str, $pos, $lineNo);
                                    break;

                                case 'char*':
                                    $this->tokens[] = new Token(TokenType::CHAR_PTR_TYPE, $ty_str, $pos, $lineNo);
                                    break;

                                case 'long*':
                                    $this->tokens[] = new Token(TokenType::LONG_PTR_TYPE, $ty_str, $pos, $lineNo);
                                    break;

                                case 'double*':
                                    $this->tokens[] = new Token(TokenType::DOUBLE_PTR_TYPE, $ty_str, $pos, $lineNo);
                                    break;

                                case 'float*':
                                    $this->tokens[] = new Token(TokenType::FLOAT_PTR_TYPE, $ty_str, $pos, $lineNo);
                                    break;
                                case 'void*':
                                    $this->tokens[] = new Token(TokenType::VOID_PTR, $ty_str, $pos, $lineNo);
                                    break;
                            }
                            $pos += 2;
                            continue;
                        }
                    }

                    $tt = $ty_match[2];
                    $this->tokens[] = new Token($tt, $ty_str, $pos, $lineNo);
                    //echo("(lex)Added Type: " . $ty_str . PHP_EOL);
                    continue;
                }

                $this->tokens[] = new Token(TokenType::IDENT, $ident, $pos, $lineNo);
                continue;
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
                continue;
            }

            if ($tok === "\n") { // why does === even exist...
                $lineNo++;
                $pos++;
                continue;
            }


            switch ($tok) {
                case '(':
                    $this->tokens[] = new Token(TokenType::OPEN_PAREN, '(', $pos, $lineNo);
                    $pos++;
                    break;
                case ')':
                    $this->tokens[] = new Token(TokenType::CLOSE_PAREN, ')', $pos, $lineNo);
                    $pos++;
                    break;
                case '#':
                    $this->tokens[] = new Token(TokenType::POUND, '#', $pos, $lineNo);
                    $pos++;
                    break;
                case '>':
                    $this->tokens[] = new Token(TokenType::CLOSE_ANGLE_BRACKET, '>', $pos, $lineNo);
                    $pos++;
                    break;
                case '<':
                    $this->tokens[] = new Token(TokenType::OPEN_ANGLE_BRACKET, '<', $pos, $lineNo);
                    $pos++;
                    break;
                case '.':
                    $this->tokens[] = new Token(TokenType::DOT, '.', $pos, $lineNo);
                    $pos++;
                    break;
                case ';':
                    $this->tokens[] = new Token(TokenType::SEMICOLON, ';', $pos, $lineNo);
                    $pos++;
                    break;
                case '\'':
                    $this->tokens[] = new Token(TokenType::SINGLE_QUOTATION_MARK, '\'', $pos, $lineNo);
                    $pos++;
                    break;
                case '*':
                    $this->tokens[] = new Token(TokenType::MULTIPLY, '*', $pos, $lineNo);
                    $pos++;
                    break;
                case '{':
                    $this->tokens[] = new Token(TokenType::OPEN_CURLY_BRACE, '{', $pos, $lineNo);
                    $pos++;
                    break;
                case '}':
                    $this->tokens[] = new Token(TokenType::CLOSE_CURLY_BRACE, '}', $pos, $lineNo);
                    $pos++;
                    break;
                case ',':
                    $this->tokens[] = new Token(TokenType::COMMA, ',', $pos, $lineNo);
                    $pos++;
                    break;
                default:
                    //fprintf(STDERR, "Unknown tok (" . $tok . ")\n");
                    $pos++;
                    break;
            }
        }

        return $this->tokens;
    }
}
