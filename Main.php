<?php

function entrypt($name, $echoOut)
{
    $f = fopen($name, "r") or die("File not found.");


    $f2 = fopen("LEXERFILE.txt", "w+");
    fflush($f2);

    $lineNo = 1;
    $t_arr_arr = null;

    while (($line = fgets($f)) !== false) {
        $lexer = new Lexer($line);
        $tokenized = $lexer->tokenize($lineNo);
        $t_arr_arr[] = $tokenized;
        $lineNo++;
    }

    $p = new Parser($t_arr_arr);
    $tt = print_r($p->parseTokens(),true);
    fwrite($f2, $tt);

    fclose($f2);
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

    public function getLineNo()
    {
        return $this->lineNo;
    }

    public function getPos()
    {
        return $this->pos;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getType()
    {
        return $this->type;
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


            // Check if its an include path
            if($this->next_slot_open($src, $pos) && ($tok == '<' || $tok == '"')) {

                if($tok == '<') $this->tokens[] = new Token(TokenType::OPEN_ANGLE_BRACKET, '<', $pos, $lineNo);
                elseif ($tok == '"') $this->tokens[] = new Token(TokenType::DOUBLE_QUOTATION_MARK, '"', $pos,$lineNo);

                $next = $pos+1;
                $include_path = null;

                while($this->next_slot_open($src,$pos) && ctype_space($src[$next])) $next++;
                while ($this->next_slot_open($src, $next) && ctype_alpha($src[$next])) {
                    $nexttok = $src[$next];
                    $include_path .= $nexttok;
                    $next++;
                }

                // Check if theres a dot
                if($this->next_slot_open($src,$next) && $src[$next] == '.') {
                    $next++; // skip the dot
                    $next++; // We can make the ridiculous assumption that all headers end in '.h' since this is C
                    $include_path .= '.h';
                    if ($this->next_slot_open($src,$next) && ($src[$next] == '>' || $src[$next] == '"')) {
                        $this->tokens[] = new Token(TokenType::INCLUDE_PATH, $include_path, $pos,$lineNo);
                        $pos = $next;
                        continue;
                    }
                }
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
                    $this->tokens[] = new Token(TokenType::STAR_OPERATOR, '*', $pos, $lineNo);
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
                default:
                    //fprintf(STDERR, "Unknown tok (" . $tok . ")\n");
                    $pos++;
                    break;
            }
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

    public function getPos()
    {
        return $this->pos;
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
        $tokens_arr = $ctx->getTokens();

        $inner_toks = [];
        $i=0;
        $arrsz = sizeof($tokens_arr);

        for(; $i < $arrsz;$i++)
        {
            $curr_tok_arr = $tokens_arr[$i];

            # Skip the blank lines
            if(sizeof($curr_tok_arr) == 0) {
                continue;
            }

            $inner_toks[] = $ctx->getTokens()[$i];
        }


        return $inner_toks; //?????
    }
}
