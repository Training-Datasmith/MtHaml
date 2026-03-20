<?php

declare (strict_types=1);
namespace Mt_Haml\Indentation;

interface Indentation_Interface
{
    /**
     * Transitions to new indentation level
     *
     * @return IndentationInterface
     */
    public function new_level($indent);
    /**
     * Returns the indentation char
     *
     * @return string|null
     */
    public function get_char();
    /**
     * Returns the indentation width
     *
     * @return int|null
     */
    public function get_width();
    /**
     * Returns the indentation level
     *
     * @return int
     */
    public function get_level();
    /**
     * Returns the indentation string for the current line
     *
     * Returns the string that should be used for indentation in regard to the
     * current indentation state.
     *
     * @param  int    $levelOffset Identation level offset
     * @param  string $fallback    Fallback indent string. If there is
     *                             currently no indentation level and
     *                             fallback is not null, the first char of
     *                             $fallback is returned instead
     * @return string A string of zero or more spaces or tabs
     */
    public function get_string($level_offset = 0, $fallback = null);
}