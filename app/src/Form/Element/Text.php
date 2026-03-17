<?php
/*
 * Copyright (C) 2012-2026 by TEQneers GmbH & Co. KG
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace PHPKnock\Form\Element;

use PHPKnock\Form\Element;

/**
 * Form Element Text Class
 *
 * This class represents a single html form element of a type text.
 */
class Text extends Element
{

    #######################################################################
    # attributes
    #######################################################################
    /**
     * Validate value against this regular expression
     */
    protected string $_validRegExp = '';


    #######################################################################
    # methods
    #######################################################################
    /**
     * @param  string  $name   Name of form
     * @param  string  $label  Label content
     */
    public function __construct(string $name, string $label)
    {
        parent::__construct($name);
        $this->_label = $label;
    }


    /**
     * This function will validate the element's value against defined validation rules
     *
     * @return boolean        TRUE if no errors occurred
     * @see    Element::validate()
     */
    public function validate(): bool
    {
        parent::validate();

        $value = trim($this->value());

        // only use regexp validation if value is not empty.
        if (!$this->isEmpty() && $this->validRegExp() !== '' && !preg_match($this->validRegExp(), $value)) {
            $this->setError('REGEXP MISMATCH');
        }

        $this->_isValidated = true;
        return !$this->error();
    }


    #######################################################################
    # accessor methods
    #######################################################################
    /**
     * @return string        Valid PCRE compatible regular expression
     * @see    validate()
     * @see    setValidRegExp()
     */
    public function validRegExp(): string
    {
        return $this->_validRegExp;
    }


    /**
     * @param  string  $regExp  Valid regular expression (PCRE)
     * @return Text             Return $this for fluent interface (method chaining)
     * @see    validRegExp()
     */
    public function setValidRegExp(string $regExp): self
    {
        $this->_validRegExp = $regExp;

        return $this;
    }


}
