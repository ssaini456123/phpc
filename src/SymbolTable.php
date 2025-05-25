<?php

class SymbolTable
{
    private $table;

    public function __construct()
    {
        $this->table = [];
    }

    public function store($name, $value)
    {

        if (!$this->lookup($name)) {
            $this->table[$name] = $value;
            return true;
        }

        $new = [
            $name => $value
        ];

        $this->table[] = $new;
        return true;
    }

    public function lookup($name)
    {
        if (!array_key_exists($name, $this->table)) {
            return false;
        } else {
            return true;
        }
    }
}
