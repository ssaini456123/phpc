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
        print_r($function);
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
            $node = $this->parse_expression();

            if (!$ctx->expect(';')) {
                echo ("Return statement must end with semiclon!");
                return null;
            }

            return new ReturnNode($node);
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

                    $expr = $this->parse_expression();

                    $parameters[] = $expr;
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
                $expression = $this->parse_expression();


                if (!$ctx->expect(';')) {
                    echo ("ERR: Assignment must end with ';'.");
                    return null;
                }

                $exists = $this->symbol_table->lookup($var_name);
                if (!$exists) {
                    echo ("ERR: Variable must be declared before assignment.");
                    return null;
                } else {
                    $this->symbol_table->store($var_name, $expression);
                    return new VariableAssignment($var_name, $expression);
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

    public function parse_expression()
    {
        $ctx = $this->pCtx;
        $node = $this->parse_term();
        while (
            $ctx->current()->get_type() == TokenType::ADD
            || $ctx->current()->get_type() == TokenType::SUBTRACT
        ) {
            $op = $ctx->current()->get_text();
            $ctx->advance();
            $rhs = $this->parse_term();
            $node = new BinaryExpression($node, $rhs, $op);;
        }
        return $node;
    }

    public function parse_term()
    {
        $ctx = $this->pCtx;
        $curr = $ctx->current();
        $node = $this->parse_factor();
        while (
            $ctx->current()->get_type() == TokenType::MULTIPLY ||
            $ctx->current()->get_type() == TokenType::DIVIDE
        ) {
            $op = $ctx->current()->get_text();
            $ctx->advance();
            $rhs  = $this->parse_factor();
            $node = new BinaryExpression($node, $rhs, $op);
        }

        return $node;
    }

    public function parse_factor()
    {
        $ctx = $this->pCtx;
        $current = $ctx->current();

        if (
            $ctx->current()->get_type() == TokenType::NUMERICAL ||
            $ctx->current()->get_type() == TokenType::STRING_LIT
        ) {
            $txt = $ctx->current()->get_text();
            $ctx->advance();
            return new LiteralNode($txt);
        } elseif ($current->get_type() == TokenType::IDENT) {
            $txt = $ctx->current()->get_text();
            $ctx->advance();
            return new VariableNode($txt);
        } elseif ($current->get_type() == TokenType::OPEN_PAREN) {
            $ctx->advance();
            $node = $this->parse_expression();
            $exists = $ctx->expect(')');

            if ($exists) {
                return $node;
            } else {
                echo ("Expected ')'." . PHP_EOL);
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
            // map arguments
            $args = array_map(function ($arg) {
                return $arg['name'];
            }, $node->arguments ?? []);

            $bodyForms = [];
            // To collect let bindings
            $letBindings = [];

            foreach ($node->statements as $stmt) {
                if ($stmt instanceof VariableDeclarationNode) {
                    $letBindings[$stmt->variable_name] = 'nil';
                } elseif ($stmt instanceof VariableAssignment) {
                    // handle AST nodes correctly
                    $val = $this->value_to_lisp($stmt->value);
                    if (array_key_exists($stmt->variable_name, $letBindings)) {
                        $letBindings[$stmt->variable_name] = $val;
                    } else {
                        $bodyForms[] = "(setf {$stmt->variable_name} $val)";
                    }
                } else {
                    $bodyForms[] = $this->generate_lisp_code($stmt);
                }
            }

            $bodyStr = implode("\n  ", $bodyForms);

            if (!empty($letBindings)) {
                // "(name init)" strings
                $bindingPairs = array_map(
                    function ($name, $init) {
                        return "({$name} {$init})";
                    },
                    array_keys($letBindings),
                    array_values($letBindings)
                );
                $bindingsStr = implode(' ', $bindingPairs);
                $bodyStr      = "(let ({$bindingsStr})\n  {$bodyStr})";
            }

            $argsStr = implode(' ', $args);
            return "(defun {$node->func_name} ({$argsStr})\n  {$bodyStr}\n)";
        } elseif ($node instanceof FunctionCallNode) {
            $args = '';
            if (!empty($node->call_args)) {
                $lispArgs = array_map(function ($a) {
                    return $this->value_to_lisp($a);
                }, $node->call_args);
                $args = ' ' . implode(' ', $lispArgs);
            }
            return "({$node->func_name}{$args})";
        } elseif ($node instanceof PrintfNode) {
            $fmt      = $this->value_to_lisp($node->fmt[0]);
            $rest     = array_slice($node->fmt, 1);
            $lispArgs = array_map(function ($a) {
                return $this->value_to_lisp($a);
            }, $rest);
            $argsStr  = $lispArgs ? ' ' . implode(' ', $lispArgs) : '';
            return "(format t \"~A~%\" {$fmt})";
        } elseif ($node instanceof ReturnNode) {
            return $this->generate_lisp_code($node->retval);
        } elseif ($node instanceof IdentifierNode) {
            return $node->ident->get_text();
        } elseif ($node instanceof LiteralNode) {
            return $this->value_to_lisp($node);
        }

        return '';
    }

    private function value_to_lisp($value)
    {

        if ($value instanceof LiteralNode) {
            return $value->lit;
        }

        if ($value instanceof IdentifierNode) {
            $v = $value->ident->get_text();
            return $v;
        }

        if (
            $value instanceof FunctionCallNode ||
            $value instanceof PrintfNode      ||
            $value instanceof ReturnNode      ||
            $value instanceof BinaryExpression
        ) {
            return $this->generate_lisp_code($value);
        }

        // plain PHP values
        if (is_numeric($value)) {
            return $value;
        } elseif (is_string($value)) {
            return '"' . addslashes($value) . '"';
        }

        return (string)$value;
    }
}

class ParserContext
{
    private $pos;
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
