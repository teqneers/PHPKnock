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

namespace PHPKnock\Form;

use PHPKnock\Html;

/**
 * Form Element Class
 *
 * This class represents a single html form element.
 */
class Element
{

    ##################################################
    # attributes
    ##################################################
    /**
     * Name of this form element
     */
    protected string $_name;


    /**
     * The name is used as a label
     */
    protected string $_label = '';


    /**
     * A mouse over hint which is put into a title tag around the label
     *
     * It will help the user to understand the meaning of the current element.
     */
    protected string $_hint = '';


    /**
     * Contains the value itself
     */
    protected mixed $_value = null;


    /**
     * Defines a default value
     */
    protected mixed $_default = null;


    /**
     * Defines whether {@link value()} can be empty/null/unselected
     */
    protected bool $_notNull = false;


    /**
     * Flag which indicates a validation error
     */
    protected bool $_error = false;


    /**
     * Flag to indicate if the form element has been validated
     */
    protected bool $_isValidated = true;


    ##################################################
    # methods
    ##################################################
    /**
     * @param  string  $name  Name of form
     */
    public function __construct(string $name)
    {
        $this->_name = $name;
    }


    #######################################################################
    # data methods
    #######################################################################
    /**
     * This method is going to automatically fetch the correct value from the request or global namespace
     *
     * @see   fetchGlobal()
     * @see   fetchRequest()
     */
    public function fetch(): void
    {
        // find out if this value came out of the DB or from a form
        $requestData = $_REQUEST['data'] ?? null;
        $globalData = $GLOBALS['data'] ?? null;

        if (is_array($requestData) && array_key_exists($this->_name, $requestData)) {
            $this->fetchRequest();
        } elseif (is_array($globalData) && array_key_exists($this->_name, $globalData)) {
            $this->fetchGlobal();
        } else {
            // no value has been set.
            $this->_value = null;
        }
    }


    /**
     * This method is going to fetch the value from global namespace
     */
    public function fetchGlobal(): void
    {
        $data = $GLOBALS['data'] ?? null;
        if (is_array($data) && array_key_exists($this->_name, $data)) {
            $this->setDbValue($data[$this->_name]);
        }
    }


    /**
     * This method is going to fetch the value from request
     */
    public function fetchRequest(): void
    {
        // The value was delivered by a request. Keep it like it is
        $data = $_REQUEST['data'] ?? null;
        $this->_isValidated = false;
        if (is_array($data) && array_key_exists($this->_name, $data)) {
            $this->setValue($data[$this->_name]);
        }
    }


    /**
     * This method will validate the element's value against defined validation rules
     *
     * @return  bool        TRUE if no errors occurred
     * @see     setNotNull()
     */
    public function validate(): bool
    {

        $this->setError(false);

        if ($this->notNull() && $this->isEmpty()) {
            $this->setError('EMPTY VALUE');
        }

        $this->_isValidated = true;
        return !$this->error();
    }


    /**
     * Internal name of the element
     *
     * @return string
     * @see    setName()
     */
    public function name(): string
    {
        return $this->_name;
    }


    /**
     * Internal name of the element
     *
     * @param  string  $newValue  Name of element
     * @return Element            Return $this for fluent interface (method chaining)
     * @see    name()
     */
    public function setName(string $newValue): self
    {
        $this->_name = trim($newValue);

        return $this;
    }


    /**
     * Returns value in a DB compatible form
     *
     * @return mixed
     * @see    setDbValue()
     */
    public function dbValue()
    {
        return $this->_value;
    }


    /**
     * Sets value from a DB compatible format
     *
     * @param  mixed  $newValue  DB compatible value
     * @return Element           Return $this for fluent interface (method chaining)
     * @see    dbValue()
     */
    public function setDbValue($newValue): self
    {
        if (!is_array($newValue)) {
            $this->_value = $newValue;
        } else {
            $first = $newValue[0] ?? null;
            if (is_array($first) && isset($first[$this->name()])) {
                $this->_value = $first[$this->name()];
            } else {
                $this->_value = null;
            }
        }

        return $this;
    }


    /**
     * Returns true if element's value is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->_value);
    }


    #######################################################################
    # output methods
    #######################################################################
    /**
     * Will return HTML compatible value used in form mode
     *
     * @return string                    Encoded HTML value
     * @see    fetch()
     * @see    setValue()
     */
    public function htmlValue(): string
    {
        $value = $this->value();
        return htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES, CHARSET);
    }


    /**
     * Generate HTML form output for element
     *
     * @return string            HTML output
     */
    public function htmlFormRow(): string
    {
        $attr = [];

        $labelAttr = Html::array2attributes($attr);

        $label = '<label ' . Html::array2attributes($attr) . '>';

        if ($this->hint() !== '') {
            $label .= '<span style="cursor:help;" title="' . htmlentities(
                    $this->hint(),
                    ENT_QUOTES,
                    CHARSET
                ) . '">' . $this->label() . '</span>';
        } else {
            $label .= $this->label();
        }
        $label .= '</label>';


        if ($this->isEmpty() && $this->defaultValue() !== null) {
            $this->setDbValue($this->defaultValue());
        }

        // define HTML attributes for input field
        $attr = [
            'type'       => 'text',
            'name'       => 'data[' . $this->name() . ']',
            'value'      => $this->htmlValue(),
            'onkeypress' => 'if( event.keyCode==13 || event.which==13) this.form.submit();',
        ];

        $value = '<input ' . Html::array2attributes($attr) . ' />';

        return '
		<div class="form-group">' . $label . '<div class="form-input">' . $value . $this->htmlErrorMessage() . '</div></div>';
    }

    /**
     * Return error message if error occurred
     *
     * @return null|string            Error message
     */
    public function htmlErrorMessage(): ?string
    {
        if ($this->error()) {
            return '
			<br /><span class="form-error">Invalid value.</span>';
        }

        return null;
    }


    #######################################################################
    # accessor methods
    #######################################################################
    /**
     * @return string        Mouse over help text
     * @see    setHint()
     */
    public function hint(): string
    {
        return $this->_hint;
    }


    /**
     * @param  string  $newValue  Mouse over help text
     * @return Element            Return $this for fluent interface (method chaining)
     * @see    hint()
     */
    public function setHint(string $newValue): self
    {
        $this->_hint = $newValue;

        return $this;
    }


    /**
     * @return boolean            TRUE forbids empty values, FALSE allows empty values
     * @see    validate()
     * @see    setNotNull()
     */
    public function notNull(): bool
    {
        return $this->_notNull;
    }


    /**
     * @param  boolean  $newValue  TRUE forbids empty values, FALSE allows empty values
     * @return Element             Return $this for fluent interface (method chaining)
     * @see    notNull()
     * @see    validate()
     */
    public function setNotNull(bool $newValue = true): self
    {
        $this->_notNull = $newValue;

        return $this;
    }


    /**
     * @return    bool
     * @see        setError()
     */
    public function error(): bool
    {
        return $this->_error;
    }

    /**
     * @param  bool|string  $newValue  Boolean value or occurred error type
     * @return Element                 Return $this for fluent interface (method chaining)
     * @see    error()
     */
    public function setError(bool|string $newValue = true): self
    {
        $this->_error = (bool)$newValue;

        return $this;
    }


    /**
     * @return mixed        Current value
     * @see    dbValue()
     * @see    fetch()
     * @see    setValue()
     */
    public function value()
    {
        return $this->_value;
    }


    /**
     * Will define element value
     *
     * @param  mixed  $newValue  Input compatible value
     * @return Element           Return $this for fluent interface (method chaining)
     * @see    fetch()
     * @see    value()
     * @see    dbValue()
     */
    public function setValue($newValue): self
    {
        if (!is_array($newValue)) {
            $this->_value = $newValue;
        } else {
            $first = $newValue[0] ?? null;
            if (is_array($first) && isset($first[$this->name()])) {
                $this->_value = $first[$this->name()];
            } else {
                $this->_value = null;
            }
        }

        return $this;
    }


    /**
     * @return mixed        Default value
     * @see    setDefaultValue()
     */
    public function defaultValue()
    {
        return $this->_default;
    }


    /**
     * @param  mixed  $newValue  Default value
     * @return Element           Return $this for fluent interface (method chaining)
     * @see    defaultValue()
     */
    public function setDefaultValue(mixed $newValue): self
    {
        $this->_default = $newValue;

        return $this;
    }


    /**
     * Will return element label
     *
     * @return string        Element label
     * @see    setLabel()
     */
    public function label(): string
    {
        return $this->_label;
    }


    /**
     * Will define element label
     *
     * @param  string  $newValue  Element label
     * @return Element            Return $this for fluent interface (method chaining)
     * @see    label()
     */
    public function setLabel(string $newValue): self
    {
        $this->_label = $newValue;

        return $this;
    }
}
