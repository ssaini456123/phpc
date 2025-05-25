<?php

include("src/Parser.php");
include("src/AST.php");
include("src/Lexer.php");

const RETURNCODE_FAIL = 69;

function entrypt($name, $echoOut)
{
    $f = fopen($name, "r") or die("File not found.");
    $src = file_get_contents($name);

    echo ("Note: it is recommended you use an external preprocessor program to expand macros." . PHP_EOL);

    $lex = new Lexer($src);
    $tarr = $lex->tokenize();
    $p = new Parser($tarr);
    $p->parse_tokens();
}

entrypt("main.c", false);
