<?php

declare(strict_types=1);

/**
 * MtHaml — compile HAML to PHP example.
 *
 * Demonstrates: compiling HAML source to PHP output, rendering with variables.
 *
 * Run:
 *   php examples/compile_haml.php
 */

require __DIR__ . '/../vendor/autoload.php';

use MtHaml\Environment;
use MtHaml\Support\Php\Executor;

// Create environment targeting PHP output
$env = new Environment('php');

$haml = <<<'HAML'
!!!
%html
  %head
    %title= $title
  %body
    %h1 Welcome
    %ul
      - foreach ($users as $user)
        %li= $user
HAML;

// Compile HAML to PHP
$compiled = $env->compileString($haml, __FILE__);

echo '=== Compiled PHP ===' . PHP_EOL;
echo $compiled . PHP_EOL;

// Execute the compiled PHP with variables
$executor = new Executor($env);

$output = $executor->execString($haml, [
    'title' => 'My App',
    'users' => ['Alice', 'Bob', 'Charlie'],
]);

echo '=== Rendered HTML ===' . PHP_EOL;
echo $output . PHP_EOL;
