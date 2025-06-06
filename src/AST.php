<?php

abstract class AstNode {};
abstract class ExpressionNode extends AstNode {}

class FunctionNode extends AstNode
{
    public $func_name;
    public $arguments;
    public $statements;
    public $retTy;

    public function __construct($name, $arguments, $statements, $returnTy)
    {
        $this->arguments = $arguments;
        $this->func_name = $name;
        $this->statements = $statements;
        $this->retTy = $returnTy;
    }

    public function __toString()
    {
        $str_ret = "";
        $str_ret .= "Function name: " . $this->func_name . "  :: Arguments(";

        if ($this->arguments) {
            foreach ($this->arguments as $arg) {
                $str_ret .= $arg;
            }
        } else {
            $str_ret .= "NULL";
        }

        $str_ret .= ") :: Return type: " . $this->retTy->get_text() . PHP_EOL;

        return $str_ret;
    }
}

class BlockNode extends AstNode
{
    public $statements = [];

    public function __construct($statements)
    {
        $this->statements = $statements;
    }
}

class FunctionCallNode extends ExpressionNode
{
    public $func_name;
    public $call_args;

    public function __construct($f_name, $call_args)
    {
        $this->func_name = $f_name;
        $this->call_args = $call_args;
    }

    public function __toString()
    {
        $str = 'FunctionCall(func=' . $this->func_name . ', arguments=';
        foreach ($this->call_args as $individual_arg) {
            $str .= $individual_arg;
        }

        $str .= ')' . PHP_EOL;

        return $str;
    }
}

class ReturnNode extends AstNode
{
    public $retval;

    public function __construct($retval)
    {
        $this->retval = $retval;
    }

    public function __toString()
    {
        $str = 'ReturnNode(' . 'return_value=' . $this->retval . ')' . PHP_EOL;
        return $str;
    }
}

class VariableDeclarationNode extends AstNode
{
    public $variable_name;
    public $type;

    public function __construct($type, $varname)
    {
        $this->variable_name = $varname;
        $this->type = $type;
    }

    public function __toString()
    {
        $str = "";
        $str .= 'Var(name=' . $this->variable_name . ', type=' . $this->type . ')' . PHP_EOL;
        return $str;
    }
}

class VariableAssignment extends AstNode
{
    public $variable_name;
    public $value;

    public function __construct($varname, $value)
    {
        $this->variable_name = $varname;
        $this->value = $value;
    }

    public function __toString()
    {
        $str = "";
        $str .= 'Reassignment(name=' . $this->variable_name . ', value=' . $this->value . ')' . PHP_EOL;
        return $str;
    }
}


class LiteralNode extends ExpressionNode
{
    public $lit;

    public function __construct($literal)
    {
        $this->lit = $literal;
    }

    public function __toString()
    {
        $str = 'Literal(' . $this->lit . ')' . PHP_EOL;
        return $str;
    }
}

class BinaryExpression extends ExpressionNode
{
    public $left;
    public $right;
    public $operation;

    public function __construct($left, $right, $operation)
    {
        $this->left = $left;
        $this->right = $right;
        $this->operation = $operation;
    }

    public function __toString()
    {
        $str = 'BinaryExpression (left=' . $this->left . ', right=' . $this->right . ', operation=' . $this->operation . ')' . PHP_EOL;
        return $str;
    }
}


class BooleanExpression extends ExpressionNode
{
    public $left;
    public $right;
    public $operator;

    public function __construct($left, $right, $operator)
    {
        $this->left = $left;
        $this->right = $right;
        $this->operator = $operator;
    }

    public function __toString()
    {
        return 'BooleanExpression(left=' . $this->left . ', right=' . $this->right . ', op=' . $this->operator . ')' . PHP_EOL;
    }
}

class VariableNode extends ExpressionNode
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function __toString()
    {
        return $this->name;
    }
}

class IdentifierNode extends ExpressionNode
{
    public $ident;

    public function __construct($ident)
    {
        $this->ident = $ident;
    }

    public function __toString()
    {
        $str = 'Identifier(' . $this->ident . ')' . PHP_EOL;
        return $str;
    }
}

class PrintfNode extends AstNode
{
    public $str;
    public $fmt;

    public function __construct($str, $fmt)
    {
        $this->str = $str;
        $this->fmt = $fmt;
    }
}

class UnaryExpression extends ExpressionNode
{
    public $operation;
    public $operand;

    public function __construct($op, $operand)
    {
        $this->operation = $op;
        $this->operand = $operand;
    }
}
