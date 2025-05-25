<?php

abstract class AstNode {};

class FunctionNode extends AstNode
{
    public $func_name;
    public $block;
    public $retTy;

    public function __construct($name, $stmts, $returnTy)
    {
        $this->func_name = $name;
        $this->block = $stmts;
        $this->retTy = $returnTy;
    }
}

class ReturnNode extends AstNode
{
    public $ret;

    public function __construct($ret)
    {
        $this->ret = $ret;
    }
}

class FunctionCallNode extends AstNode
{
    public $name;
    public $args;

    public function __construct($name, $args)
    {
        $this->name = $name;
        $this->args = $args;
    }
}

class BlockNode extends AstNode
{
    public $statements;

    public function __construct($stmt)
    {
        $this->statements = $stmt;
    }
}
