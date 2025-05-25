<?php


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

    public function parse_function()
    {

        $ctx = $this->pCtx;
        $is_func = false;

        //print_r($ctx->current());
        //echo(PHP_EOL);

        $return_type = null;
        $func_name = null;
        $arguments = [];

        if (in_array($ctx->current()->get_text(), TokenType::NATIVE_C_TY)) {
            $return_type = $ctx->current();
            $ctx->advance();

            if ($ctx->current()->get_type() == TokenType::IDENT) {
                $func_name = $ctx->current()->get_text();
            }

            if ($func_name === '') {
                return;
            }

            $ctx->advance();

            if ($ctx->current()->get_type() == TokenType::OPEN_PAREN) {
                // it is a function, parse the arguments
                $ctx->advance();

                while ($ctx->current()->get_type()) {
                    if ($ctx->current()->get_type() == TokenType::COMMA) {
                        print_r($ctx->current()->get_type());
                        $ctx->advance();
                        continue;
                    } else if ($ctx->current()->get_type() == TokenType::CLOSE_PAREN) {
                        $ctx->advance();
                        break;
                    }
                    $argument_type = $ctx->current();
                    $txt = $argument_type->get_text();
                    //echo(in_array($txt,TokenType::NATIVE_C_TY).PHP_EOL);
                    if (!in_array($txt, TokenType::NATIVE_C_TY)) {
                        //echo("ERR: Argument type must be native C type.".PHP_EOL);
                        return;
                    }

                    $ctx->advance();

                    $argument_name = $ctx->current();

                    if (!$argument_name->get_type() == TokenType::IDENT) {
                        echo ("ERR: Argument name must be a valid identifier." . PHP_EOL);
                        return;
                    }

                    $arguments[] = [
                        "type" => $argument_type->get_text(),
                        "name" => $argument_name->get_text(),
                    ];

                    $ctx->advance();
                }

                print_r(' Function: ' . $func_name . '  --- Has ret->' . $return_type->get_text() . "\t" . 'Has argument properties: ');
                var_dump($arguments);
                echo (PHP_EOL);
                $is_func = true;
            } else {
                // probably not
                return;
            }
        }

        if (!$is_func) return;

        //TODO: parse body
        //TODO: parse ret stmt
    }

    public function parse_tokens()
    {
        // Loop through the lex'd tokens
        $ctx = $this->get_parser_context();
        $tokens_arr = $ctx->get_tokens();
        $node_arr = array();
        //print_r($ctx->get_tokens());
        while ($ctx->current()) {
            $node = $this->parse_function();
            if ($node) {
                $node_arr[] = $node;
            } else {
                $ctx->advance();
            }
        }
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
        $p_ty = $peek->get_text();
        $tty = $token; //STRING VALUE
        $t_line_no = $peek->get_line_no();

        if (!$peek) {
            //echo ("!! ERR !! Unkown token " . $p_ty . " on line: " . strval($t_line_no) . PHP_EOL);
            return false;
        }

        if ($p_ty != $tty) {
            //echo("!! ERR !! Expected " . $token . ' but got: '. $tty . ' instead.' . PHP_EOL);
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
        $tty = $peek->get_text();

        if ($tty == $ty) {
            return true;
        } else {
            return false;
        }
    }
}
