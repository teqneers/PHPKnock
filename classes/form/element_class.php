<?php
/*
 * Copyright (C) 2012-2024 by TEQneers GmbH & Co. KG
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

/**
 * Form Element Class
 *
 * @author         Oliver G. Mueller <mueller@teqneers.de>
 * @package        PHPKnock
 * @subpackage     Classes
 * @copyright      Copyright (C) 2003-2024 TEQneers GmbH & Co. KG. All rights reserved
 */

/**
 * Form Element Class
 *
 * This class represents a single html form element.
 *
 * @package        PHPKnock
 * @subpackage     Classes
 */
class FormElement
{

    ##################################################
    # attributes
    ##################################################
    /**
     * Name of this form element
     */
    protected ?string $_name = null;


    /**
     * The name is used as a label
     */
    protected ?string $_label = null;


    /**
     * A mouse over hint which is put into a title tag around the label
     *
     * It will help the user to understand the meaning of the current element.
     */
    protected string $_hint = '';


    /**
     * Contains the value itself
     *
     * It is important to know that this value might
     * appear in different forms. This depends on the source (either DB or
     * http request). It should not be read directly to write it into a DB.
     * Use {@link dbValue()} instead.
     *
     * @var        string
     */
    protected $_value;


    /**
     * Defines a default value
     *
     * If set to anything not empty, this value will be used in case form data is empty.
     * The use of this value depends on the form element object. Some objects (e.g., FormElementText)
     * will display this value instead of an empty html form.
     *
     * IMPORTANT: This value will not be used in text mode.
     *
     * @var        string
     */
    protected $_default;


    /**
     * Defines whether {@link value()} can be empty/null/unselected
     *
     * Might trigger an {@link setError()}.
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
     * The data in the request has a higher priority than the global namespace data, which means that once data
     * is set in a request, the global name space data will be ignored.
     *
     * @see   fetchGlobal()
     * @see   fetchRequest()
     */
    public function fetch(): void
    {
        // find out if this value came out of the DB or from a form
        if (isset($_REQUEST['data'][$this->_name])) {
            $this->fetchRequest();
        } elseif (isset($GLOBALS['data'][$this->_name])) {
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
        $global = &$GLOBALS['data'][$this->_name];

        $this->setDbValue($global);
    }


    /**
     * This method is going to fetch the value from request
     */
    public function fetchRequest(): void
    {
        // The value was delivered by a request. Keep it like it is
        $global = &$_REQUEST['data'][$this->_name];

        $this->_isValidated = false;
        $this->setValue($global);
    }


    /**
     * This method will validate the element's value against defined validation rules (e.g., not null, ...)
     *
     * @return  bool        TRUE if no errors occurred
     * @see     setNotNull()
     */
    public function validate(): bool
    {

        $this->setError(false);

        // elements like uploads and images will return arrays for value()
        // so simply check, if the array is empty.
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
     * IMPORTANT: Changing this will influence all fetching methods.
     *            Already fetch values may reference to wrong request values.
     *
     * @param  string  $newValue  Name of element
     * @return FormElement        Return $this for fluent interface (method chaining)
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
     * @see    setDbColumnName()
     * @see    dbValueMatrix()
     * @see    returnDbValue()
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
     * @return FormElement       Return $this for fluent interface (method chaining)
     * @see    dbValue()
     * @see    _fetchGlobal()
     */
    public function setDbValue($newValue): self
    {
        if (!is_array($newValue)) {
            // DB delivered the value. Keep it like it is.
            $this->_value = $newValue;
        } elseif (isset($newValue[0][$this->name()])) {
            $this->_value = $newValue[0][$this->name()];
        } else {
            $this->_value = null;
        }

        return $this;
    }


    /**
     * Returns true if element's value is empty
     *
     * An empty value will usually not display in text mode.
     *
     * @return mixed
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
     * The text value is taken of {@link htmlText()}.
     *
     * @return string                    Encoded HTML value
     * @see    fetch()
     * @see    setValue()
     */
    public function htmlValue(): string
    {
        return htmlspecialchars((string)$this->value(), ENT_QUOTES, CHARSET);
    } // function


    /**
     * Generate HTML form output for element
     *
     * @return string            HTML output
     */
    public function htmlFormRow(): string
    {
        $attr = [];
//		$attr['class']	= $this->formClass();

        $labelAttr = Html::array2attributes($attr);

        $label = '<label ' . Html::array2attributes($attr) . '>';

        if ($this->hint() !== '') {
            // show help cursor when a hint is given and the mouse hovers above the label
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
//			'class'			=> $this->formClass(),
            'onkeypress' => 'if( event.keyCode==13 || event.which==13) this.form.submit();',
        ];

        $value = '<input ' . Html::array2attributes($attr) . ' />';

        return '
		<tr><td ' . $labelAttr . '>' . $label . '</td><td>' . $value . $this->htmlErrorMessage() . '</td></tr>';
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
			<br /><span style="color: red;">Invalid value.</span>';
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
     * A mouse over hint which is put into a title tag around the label.
     * It will help the user to understand the meaning of the current element.
     *
     * @param  string  $newValue  Mouse over help text
     * @return FormElement        Return $this for fluent interface (method chaining)
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
     * Defines whether {@link value()} can be empty/null/unselected.
     * Might trigger an {@link setError()}.
     *
     * @param  boolean  $newValue  TRUE forbids empty values, FALSE allows empty values
     * @return FormElement         Return $this for fluent interface (method chaining)
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
     * Flag which indicates a validation error.
     *
     * @param  mixed  $newValue  Boolean value or occurred error type
     * @return FormElement       Return $this for fluent interface (method chaining)
     * @see    error()
     */
    public function setError($newValue = true): self
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
     * It is important to know, that this value might appear in different forms and should be set using the
     * {@link fetch()} method. Different values depend on the data source (either DB or http request). The value should
     * not be read directly from this method in order to write it into a DB. Use method {@link dbValue()} instead.
     *
     * @param  mixed  $newValue  Input compatible value
     * @return FormElement       Return $this for fluent interface (method chaining)
     * @see    fetch()
     * @see    _fetchRequest()
     * @see    value()
     * @see    dbValue()
     */
    public function setValue($newValue): self
    {
        if (!is_array($newValue)) {
            // DB delivered the value. Keep it like it is.
            $this->_value = $newValue;
        } elseif (isset($newValue[0][$this->name()])) {
            $this->_value = $newValue[0][$this->name()];
        } else {
            $this->_value = null;
        } // if

        return $this;
    } // function


    /**
     * @return string        Default value
     * @see    setDefaultValue()
     */
    public function defaultValue()
    {
        return $this->_default;
    }


    /**
     * If set to anything not empty, this value will be used in case form data is empty
     *
     * The use of this value depends on the form element object. Some objects (e.g., FormElementText)
     * will display this value instead of an empty html form.
     *
     * IMPORTANT: This value will not be used in text mode, but only activated in form mode.
     *
     * @param  string  $newValue  Default value
     * @return FormElement        Return $this for fluent interface (method chaining)
     * @see    defaultValue()
     */
    public function setDefaultValue($newValue): self
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
     * @return FormElement        Return $this for fluent interface (method chaining)
     * @see    label()
     */
    public function setLabel(string $newValue): self
    {
        $this->_label = $newValue;

        return $this;
    }
}
