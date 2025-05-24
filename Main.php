<?php

const RETURNCODE_FAIL = 69;

function entrypt($name, $echoOut)
{
    $f = fopen($name, "r") or die("File not found.");
    $src = file_get_contents($name);

    //$pp = new Preprocessor($src);
    //$pp->preprocess();

    

    $lex = new Lexer($src);
    $tarr = $lex->tokenize();
    $p = new Parser($tarr);
    $p->parseTokens();

    
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
    const DOUBLE_PTR_TYPE = 'DOUBLE_PTR_TYPE';
    const VOID_PTR = 'VOID_PTR';
    const NATIVE_C_TY = ['bool','int','char','long','double','float'];

    // arithmetic
    const ADD = 'ADD';
    const SUBTRACT = 'SUBTRACT';
    const DIVIDE = 'DIVIDE';
    const MULTIPLY = 'MULTIPLY';
}

class PreprocessorDirectiveType extends TokenType
{
    const PP_TY_INCLUDE = 'PREPROCESSOR_INCLUDE';
    const PP_TY_IF = 'PREPROCESSOR_IF';
    const PP_TY_ENDIF = 'PREPROCESSOR_ENDIF';
    const PP_TY_DEFINE = 'PREPROCESSOR_DEFINE';
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

    public function get_type(): TokenType
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
        
        switch($ident) {
            case 'bool':
                $ty_str='bool';
                $tt=TokenType::BOOL_TYPE;
                break;
            case 'int':
                $ty_str='int';
                $tt=TokenType::INT_TYPE;
                break;
            case 'char':
                $ty_str='char';
                $tt=TokenType::CHAR_TYPE;
                break;
            case 'long':
                $ty_str='long';
                $tt=TokenType::LONG_TYPE;
                break;
            case 'double':
                $ty_str='double';
                $tt=TokenType::DOUBLE_TYPE;
                break;
            case 'float':
                $ty_str='float';
                $tt=TokenType::FLOAT_TYPE;
                break;
        }

        $r[0]=$native_c_ty;
        $r[1]=$ty_str;
        $r[2]=$tt;

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

            // Check if its an include path
            if($this->next_slot_open($src, $pos) && ($tok == '<' || $tok === '"')) {

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
                if($this->next_slot_open($src,$next) && $src[$next] === '.') {
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

            
            //string lit
            else if($tok === "\"")
            {
                $ident = '"';
                $next = $pos + 1;
                $closed = false;
                while ($this->next_slot_open($src, $next) && !$closed) {
                    if($src[$next] === '"')
                    {
                        $closed=true;
                    }
                    else if(!$closed){
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

                
                if($c_ty) {

                    $frwrd=$next + 1;
                    $ty_str = $ty_match[1];
                    
                    if($this->next_slot_open($src,$next))
                    {
                        $p_ptr = $src[$frwrd];

                        if($p_ptr === "*") {

                            $ty_str .= '*';
                            $pos=$next;

                            switch($ty_str) {
                                case 'bool*':
                                    $this->tokens[] = new Token(TokenType::BOOL_PTR_TYPE, $ty_str, $pos,$lineNo);
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
                                
                            }
                            $pos += 2;
                            continue;
                        }
                    }

                    $tt = $ty_match[2];
                    $this->tokens[] = new Token($tt, $ty_str,$pos,$lineNo);
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

            if($tok === "\n"){ // why does === even exist...
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

    public function get_header()
    {
        return $this->header;
    }
}


class Parser
{
    /**
     * @var ParserContext
     */
    private $pCtx;

    public function __construct($tok_arr)
    {
        $toks = $tok_arr;
        $this->pCtx = new ParserContext(0, $toks);
    }

    public function get_parser_context()
    {
        return $this->pCtx;
    }

    public function parseTokens()
    {
        // Loop through the lex'd tokens
        $ctx = $this->get_parser_context();
        $tokens_arr = $ctx->get_tokens();

        print_r($tokens_arr);
    }
}



class ParserContext
{
    private int $pos;
    private $tokens;

    public function __construct($pos, $tokens)
    {
        $this->pos = $pos;
        $this->tokens = $tokens;
    }

    public function get_tokens(): array
    {
        return $this->tokens;
    }

    public function get_pos()
    {
        return $this->pos;
    }

    public function advance()
    {
        $this->pos++;
    }

    public function expect($token)
    {
        $peek = $this->current();
        $p_ty = $peek->get_type();
        $tty = $token->get_type();
        $t_line_no = $peek->get_line_no();

        if($p_ty != $tty)
        {
            echo("!! ERR !! Unkown token " . $p_ty . " on line: " . strval($t_line_no));
            return false;
        } else {
            $this->advance();
            return true;
        }
    }

    public function current()
    {
        $p = $this->get_pos();
        $t = $this->get_tokens()[$p];
        return $t;
    }

    public function match($ty)
    {
        $peek = $this->current();
        $tty = $peek->get_type();

        if ($tty==$ty)
        {
            return true;
        } else {
           return false;
        }
    }

    public function parse_tokens_list()
    {
        $tokens = $this->get_tokens();
        print_r($tokens);
    }
}