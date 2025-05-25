<?php

include("SymbolTable.php");


class Parser
{
    /**
     * @var ParserContext
     */
    private $pCtx;
    private $symbol_table;

    public function __construct($tok_arr)
    {
        $toks = $tok_arr;
        $this->pCtx = new ParserContext(0, $toks);
        $this->symbol_table = new SymbolTable();
    }

    public function get_parser_context()
    {
        return $this->pCtx;
    }

    public function parse_function()
    {

        $ctx = $this->pCtx;
        $is_func = false;

        $return_type = null;
        $func_name = null;
        $arguments = null;

        if (
            in_array($ctx->current()->get_text(), TokenType::NATIVE_C_TY)
        ) {
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
                        $ctx->advance();
                        continue;
                    } else if ($ctx->current()->get_type() == TokenType::CLOSE_PAREN) {
                        $ctx->advance();
                        break;
                    }
                    $argument_type = $ctx->current();
                    $txt = $argument_type->get_text();

                    if (!in_array($txt, TokenType::NATIVE_C_TY) && !in_array($txt, TokenType::NATIVE_C_TY_PTR)) {
                        return null;
                    }

                    $ctx->advance();

                    $argument_name = $ctx->current();

                    if ($argument_name->get_type() != TokenType::IDENT) {
                        echo ("ERR: Argument name must be a valid identifier." . PHP_EOL);
                        return;
                    }

                    $arguments[] = [
                        "type" => $argument_type->get_text(),
                        "name" => $argument_name->get_text(),
                    ];

                    $ctx->advance();
                }

                $is_func = true;
            } else {
                // probably not
                return;
            }
        }

        if (!$is_func) return;

        $statements = [];
        if ($ctx->current()->get_type() == TokenType::OPEN_CURLY_BRACE) {
            $ctx->advance();
            while ($ctx->current()->get_type() != TokenType::CLOSE_CURLY_BRACE) {
                $statement = $this->parse_block();

                if ($statement) {
                    $statements[] = $statement;
                } else {
                    $ctx->advance();
                }
            }
        }

        $function = new FunctionNode($func_name, $arguments, $statements, $return_type);
        return $function;
    }

    public function parse_block()
    {
        $ctx = $this->pCtx;
        $parameters = [];

        $tok = $ctx->current();
        $curr = $tok->get_type();

        if ($ctx->current()->get_text() == 'return') {

            $ctx->advance();
            $tok = $ctx->current();
            $ctx->advance();

            $semi = $ctx->expect(';');
            if ($semi) {
                // determine if retval is a literla or a symbolic
                if ($tok->get_type() == TokenType::NUMERICAL || $tok->get_type() == TokenType::STRING_LIT) {
                    $literalNode = new LiteralNode($tok->get_text());
                    $retval = new ReturnNode($literalNode);
                    return $retval;
                } else {
                    $identifierNode = new IdentifierNode($tok);
                    $retval = new ReturnNode($identifierNode);
                    return $retval;
                }
            } else {
                echo ("Return statement must end with a semi colon ';'.");
                return null;
            }
        }

        if ($curr == TokenType::IDENT) {
            $possible_func_name = $ctx->current()->get_text();

            $ctx->advance();
            $curr = $ctx->current()->get_type();


            //func(x,y,z);
            //    ^ we are here
            if ($curr == TokenType::OPEN_PAREN) {
                $ctx->advance();

                while ($ctx->current()->get_type() != TokenType::CLOSE_PAREN) {

                    if ($ctx->current()->get_type() == TokenType::COMMA) {
                        $ctx->advance();
                        continue;
                    }

                    $parameters[] = $ctx->current()->get_text();
                    $ctx->advance();
                }
                $ctx->advance();
                if (!($ctx->expect(';'))) {
                    echo ("ERR: call must end with a semicolon ';'." . PHP_EOL);
                    return null;
                }
                $func_call_statement = null;
                if ($possible_func_name === 'printf') {
                    $func_call_statement = new PrintFNode("printf", $parameters);
                } else {
                    $func_call_statement = new FunctionCallNode($possible_func_name, $parameters);
                }
                return $func_call_statement;
            }


            $var_name = $possible_func_name;
            if ($curr == TokenType::EQUAL) {
                $ctx->advance();
                $value = $ctx->current()->get_text();
                $exists = $this->symbol_table->lookup($var_name);
                if (!$exists) {
                    echo ("ERR: Variable must be declared before assignment.");
                    return null;
                } else {
                    $this->symbol_table->store($var_name, $value);
                    $var_assignment_node = new VariableAssignment($var_name, $value);
                    return $var_assignment_node;
                }
            }
        } else if (in_array($ctx->current()->get_text(), TokenType::NATIVE_C_TY)) {
            $type = $ctx->current()->get_text();
            $ctx->advance();
            $var_name = $ctx->current()->get_text();
            $ctx->advance();
            if ($ctx->expect(';')) {
                $this->symbol_table->store($var_name, null);
                return new VariableDeclarationNode($type, $var_name);
            } else {
                echo ("ERR: Improper variable declaration");
                return null;
            }
        }
    }

    public function parse_tokens()
    {
        // Loop through the lex'd tokens
        $ctx = $this->get_parser_context();
        $tokens_arr = $ctx->get_tokens();
        $node_arr = array();
        while ($ctx->current()) {
            $node = $this->parse_function();
            if ($node) {
                $node_arr[] = $node;
            } else {
                $ctx->advance();
            }
        }

        $lisp_output = null;

        foreach ($node_arr as $fn_node) {
            $lisp_output .= $this->generate_lisp_code($fn_node) . "\n\n";
        }


        file_put_contents('output.lisp', $lisp_output);
    }

    public function generate_lisp_code($node)
    {
        if ($node instanceof FunctionNode) {
            $args = array_map(fn($arg) => $arg['name'], $node->arguments ?? []);
            $body = [];

            // To collect let bindings
            $let_bindings = [];

            foreach ($node->statements as $stmt) {
                if ($stmt instanceof VariableDeclarationNode) {
                    $let_bindings[] = [$stmt->variable_name, 'nil'];
                } elseif ($stmt instanceof VariableAssignment) {
                    // Check if it's already declared in let
                    $already_declared = false;
                    foreach ($let_bindings as &$bind) {
                        if ($bind[0] === $stmt->variable_name) {
                            $bind[1] = self::value_to_lisp($stmt->value);
                            $already_declared = true;
                            break;
                        }
                    }
                    if (!$already_declared) {
                        $body[] = "(setf {$stmt->variable_name} " . self::value_to_lisp($stmt->value) . ")";
                    }
                } else {
                    $body[] = self::generate_lisp_code($stmt);
                }
            }


            $body_str = implode("\n  ", $body);
            if (!empty($let_bindings)) {
                $bindings_str = implode(' ', array_map(fn($b) => "({$b[0]} {$b[1]})", $let_bindings));
                $body_str = "(let ($bindings_str)\n  $body_str)";
            }

            $s = "(defun {$node->func_name} (" . implode(' ', $args) . ")\n  $body_str\n)";
            if (strlen($bindings_str) > 0) {

                return $s;
            } else {
                return $s;
            }
        } elseif ($node instanceof FunctionCallNode) {
            $args_str = implode(' ', array_map(fn($a) => self::value_to_lisp($a), $node->call_args ?? []));
            return "({$node->func_name} $args_str)";
        } elseif ($node instanceof PrintfNode) {
            $fmt = self::value_to_lisp($node->fmt[0]);
            $args = array_slice($node->fmt, 1);
            $args_str = implode(' ', array_map(fn($a) => self::value_to_lisp($a), $args));
            return "(format t $fmt $args_str)";
        } elseif ($node instanceof ReturnNode) {
            return self::generate_lisp_code($node->retval);
        } elseif ($node instanceof IdentifierNode) {
            return $node->ident->get_text();
        } elseif ($node instanceof LiteralNode) {
            return self::value_to_lisp($node->lit);
        }

        return "";
    }

    private static function value_to_lisp($value)
    {
        if (is_numeric($value)) {
            return $value;
        } elseif (is_string($value)) {
            return '"' . addslashes($value) . '"';
        } else {
            return (string)$value;
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
            return false;
        }

        if ($p_ty != $tty) {
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
