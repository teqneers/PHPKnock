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
 * Form Element Integer Class
 *
 * This class represents a single html form element of type integer.
 */
class Integer extends Element
{

    #######################################################################
    # Attributes
    #######################################################################
    /**
     * Defines the minimum valid value
     */
    protected ?int $_minValue = null;


    /**
     * Defines the maximum valid value
     */
    protected ?int $_maxValue = null;


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


    #######################################################################
    # data methods
    #######################################################################
    /**
     * This function will validate the element's value against defined validation rules
     *
     * @return boolean        TRUE if no errors occurred
     * @see    Element::validate()
     * @see    setNotNull()
     */
    public function validate(): bool
    {
        $this->setError(false);

        $this->setValue(trim($this->value()));

        if ($this->isEmpty()) {
            if ($this->notNull()) {
                $this->setError('EMPTY VALUE');
            }
            $this->setValue(null);

            return !$this->error();
        }

        // check chars
        $search = '(^[-+]?\d+$)';
        if (!preg_match($search, $this->value())) {
            $this->setError('INVALID FORMAT');
        }

        $floatValue = (float)$this->value();

        // check minimum limit
        if ($this->minimum() !== null && $floatValue < $this->minimum()) {
            $this->setError('MIN EXCEEDED');
        }

        // check maximum limit
        if ($this->maximum() !== null && $floatValue > $this->maximum()) {
            $this->setError('MAX EXCEEDED');
        }

        $this->_isValidated = true;
        return !$this->error();
    }


    /**
     * Returns true if element's value is empty
     */
    public function isEmpty(): bool
    {
        return ($this->value() === null || trim($this->value()) === '');
    }


    #######################################################################
    # accessor methods
    #######################################################################
    public function minimum(): ?int
    {
        return $this->_minValue;
    }


    /**
     * Defines the minimum valid value.
     *
     * @param ?int  $value  Inclusive minimum value
     * @see   minimum()
     * @see   validate()
     */
    public function setMinimum(?int $value): self
    {
        $this->_minValue = $value;

        return $this;
    }


    public function maximum(): ?int
    {
        return $this->_maxValue;
    }


    /**
     * Defines the maximum valid value.
     *
     * @param ?int  $value  Inclusive maximum value
     * @see   maximum()
     * @see   validate()
     */
    public function setMaximum(?int $value): self
    {
        $this->_maxValue = $value;

        return $this;
    }


}
