<?php

namespace im\search\components\query;

/**
 * Term query.
 *
 * @package im\search\components\query\parser\entry
 */
class Term extends Query
{
    /**
     * @var string
     */
    private $_field;

    /**
     * @var string
     */
    private $_term;

    /**
     * Creates term.
     *
     * @param string $field
     * @param string $term
     */
    function __construct($field, $term)
    {
        $this->_field = $field;
        $this->_term = $term;
    }

    /**
     * Returns query field.
     *
     * @return string
     */
    public function getField()
    {
        return $this->_field;
    }

    /**
     * Sets query field.
     *
     * @param string $field
     */
    public function setField($field)
    {
        $this->_field = $field;
    }

    /**
     * Returns query term.
     *
     * @return string
     */
    public function getTerm()
    {
        return $this->_term;
    }

    /**
     * Sets query term.
     *
     * @param string $term
     */
    public function setTerm($term)
    {
        $this->_term = $term;
    }
}