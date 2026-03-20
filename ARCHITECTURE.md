# Architecture: MtHaml

## Purpose

PHP implementation of the HAML template language. Compiles HAML source to PHP or Twig output, with pluggable filter and target support.

## Directory Structure

```
lib/MtHaml/
  Environment.php         Top-level API: configure options, compile HAML strings/files
  Parser.php              Tokenises HAML source lines into raw token stream
  Parser/Buffer.php       Line-by-line buffer with lookahead for the parser
  Tree_Builder.php        Converts token stream to AST (tree of Node objects)
  Node/                   AST node types: Tag, Comment, Doctype, Filter, Text, Run, etc.
  NodeVisitor/            Visitor passes that transform or render the AST
    Php_Renderer.php      Renders AST to PHP output
    Twig_Renderer.php     Renders AST to Twig output
    Escaping.php          Injects HTML-escaping into the AST
    Autoclose.php         Auto-closes void elements
    Merge_Attrs.php       Merges consecutive attribute hashes
    Midblock.php          Handles mid-block expressions (else, elseif, etc.)
  Filter/                 HAML :filter implementations (css, javascript, markdown, twig, etc.)
  Indentation/            Indentation tracker and validator
  Target/                 Target language wrappers (PHP, Twig)
  Runtime/                Runtime helpers for attribute list expansion
  Support/
    Php/Executor.php      Executes compiled PHP templates
    Twig/                 Twig integration (loader, lexer, extension)
  Autoloader.php          PSR-0 autoloader for non-Composer use
```

## Key Design Decisions

- **Parse → AST → Visitor pipeline**: The compiler is a classic three-stage pipeline. New output targets are implemented as NodeVisitor subclasses rather than modifying the parser or AST.
- **Pluggable filters**: HAML `:filter` blocks delegate to registered `FilterInterface` objects. Filters for markdown, LESS, SCSS, CoffeeScript, etc. are optional and detected at runtime.
- **Dual targets**: PHP and Twig targets are first-class; the same HAML source can compile to either without changing any parser code.
- **Escaping visitor**: HTML escaping is applied as a separate AST visitor pass, keeping escape logic out of the renderer.

## Extension Points

- Register custom filters via `Environment::addFilter(name, FilterInterface)`.
- Implement `NodeVisitor_Interface` and add it to the visitor chain for custom AST transformations.
- Implement `Target_Interface` to compile HAML to a new output language.

## Dependency Flow

```
HAML source string
  -> Parser -> token stream
  -> Tree_Builder -> AST (Node tree)
  -> NodeVisitor passes (Autoclose, MergeAttrs, Escaping, Midblock)
  -> Renderer visitor (PhpRenderer / TwigRenderer)
  -> compiled PHP/Twig string
```
