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
use PHPKnock\Html;

/**
 * Form Element Dropdown Class
 *
 * This class represents a single html form element for dropdown.
 */
class Dropdown extends Element
{

    #######################################################################
    # attributes
    #######################################################################
    /**
     * This array contains selectable options
     */
    protected array $_options = [];


    /**
     * Defines the ability to select multiple options
     */
    protected bool $_isMultiple = false;


    /**
     * Defines the display size of a dropdown
     */
    protected int $_size = 1;


    /**
     * Defines the maximum displayed size of a dynamic growing dropdown
     */
    protected ?int $_maximumSize = null;


    #######################################################################
    # methods
    #######################################################################
    /**
     * @param  string  $name     Database column name
     * @param  string  $label    Label content
     * @param  array   $options  Array containing values available for selection
     */
    public function __construct(string $name, string $label, array $options = [])
    {
        parent::__construct($name);

        $this->setLabel($label);
        $this->setOptions($options);
    }


    /**
     * Sets value from a DB compatible format
     *
     * @param  mixed  $newValue  DB compatible value
     * @return Dropdown          Return $this for fluent interface (method chaining)
     * @see    dbValue()
     */
    public function setDbValue($newValue): self
    {
        if (!is_array($newValue)) {
            $this->_value = $newValue;
        } elseif (is_array(reset($newValue))) {
            $this->_value = [];
            if (isset($newValue[0][$this->name()])) {
                foreach ($newValue as $row) {
                    $this->_value[] = $row[$this->name()];
                }
            }
        } else {
            $this->_value = array_values($newValue);
        }

        return $this;
    }


    /**
     * Returns true if element's value is empty
     */
    public function isEmpty(): bool
    {
        return (is_array($this->_value) && count($this->_value) === 0) || (!is_array($this->_value) && strlen(
                    (string)$this->_value
                ) === 0);
    }


    /**
     * Will define element value.
     *
     * @param  mixed  $newValue  Input compatible value
     * @return Dropdown          Return $this for fluent interface (method chaining)
     * @see    fetch()
     * @see    value()
     * @see    dbValue()
     */
    public function setValue($newValue): self
    {
        if (!is_array($newValue) || !is_array(reset($newValue))) {
            $this->_value = $newValue;
        } elseif (isset($newValue[0][$this->name()])) {
            $this->_value = $newValue[0][$this->name()];
        } else {
            $this->_value = null;
        }

        // ensure that all preselected values are of type STRING.
        if ($this->_value !== null) {

            if (!is_array($this->_value)) {
                $this->_value = (string)$this->_value;
            } else {
                $this->_value = array_map('strval', $this->_value);
            }

        }

        return $this;
    }


    /**
     * Returns the current available options to choose from.
     *
     * @param  mixed  $value   Optional option value
     * @return array|mixed     Complete option array or related value name
     * @see    setOptions()
     */
    public function options($value = null)
    {
        if ($value === null) {
            return $this->_options;
        }

        return $this->_options[$value] ?? null;
    }


    /**
     * Define the current available options to choose from.
     *
     * @param  array  $newValue  Key will be returned, value will be shown to the user
     * @return Dropdown          Return $this for fluent interface (method chaining)
     * @see    options()
     */
    public function setOptions(array $newValue): self
    {
        $this->_options = $newValue;

        return $this;
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

        // remove empty value which might have been set by setValueWhenEmpty
        if (is_array($this->_value)) {
            $key = array_search('', $this->_value, true);
            if ($key !== false) {
                unset($this->_value[$key]);
            }
        } elseif ($this->_value === '') {
            $this->_value = null;
        }

        if ($this->notNull() && $this->isEmpty()) {
            $this->setError('EMPTY VALUE');
        }

        $this->_isValidated = true;
        return !$this->error();
    }


    #######################################################################
    # output methods
    #######################################################################
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


        if (!$this->isMultiple() && ($this->isEmpty()) && $this->defaultValue() !== null) {
            $this->setDbValue($this->defaultValue());
        }

        // Evaluate dynamic size.
        if ($this->_maximumSize !== null) {
            $size = min($this->_maximumSize, count($this->options()));
        } else {
            $size = $this->_size;
        }

        $value = $this->value();


        // define HTML attributes for select tag
        $attr = [
            'name'       => 'data[' . $this->name() . ']',
            'size'       => $size,
            'multiple'   => null,
            'onkeypress' => 'if( event.keyCode==13 || event.which==13) this.form.submit();',
        ];

        if ($this->isMultiple()) {
            $attr['multiple'] = 'multiple';
            $attr['name']     .= '[]';
        }

        $output = '<select ' . Html::array2attributes($attr) . '>';

        if (is_array($this->options())) {
            foreach ($this->options() as $key => $option) {
                $tmp        = count((array)$value) && in_array((string)$key, (array)$value, true) ? 'selected' : null;
                $optionAttr = [
                    'value'    => $key,
                    'selected' => $tmp,
                ];

                $output .= '
						<option ' . Html::array2attributes($optionAttr) . '>' . htmlspecialchars(
                        $option,
                        ENT_QUOTES,
                        CHARSET
                    ) . '</option>';

            }
        }
        $output .= '
		</select>';

        return '
		<tr><td ' . $labelAttr . '>' . $label . '</td><td>' . $output . $this->htmlErrorMessage() . '</td></tr>';
    }


    #######################################################################
    # accessor methods
    #######################################################################
    /**
     * @return boolean        TRUE on multiple selection, FALSE on single selection
     */
    public function isMultiple(): bool
    {
        return $this->_isMultiple;
    }


    /**
     * Defines the ability to select multiple options.
     *
     * @param  boolean  $newValue  TRUE on multiple selection, FALSE on single selection
     */
    public function setIsMultiple(bool $newValue = true): self
    {
        $this->_isMultiple = $newValue;

        if ($newValue && $this->_size === 1) {
            $this->setMaximumSize(5);
        }

        return $this;
    }


    /**
     * @return integer     Size of dropdown
     */
    public function size(): int
    {
        return $this->_size;
    }

    /**
     * Defines number of dropdown rows.
     *
     * @param  integer  $newValue  Size of dropdown
     * @return Dropdown            Return $this for fluent interface (method chaining)
     * @see    size()
     * @see    setMaximumSize()
     */
    public function setSize(int $newValue): self
    {
        $this->_size = max(1, abs($newValue));

        if ($this->_size === 1) {
            $this->setIsMultiple(false);
        }

        // reset dynamic size
        $this->_maximumSize = null;

        return $this;
    }

    /**
     * @return integer|null        Maximum size of dropdown
     * @see    size()
     * @see    setMaximumSize()
     */
    public function maximumSize(): ?int
    {
        return $this->_maximumSize;
    }

    /**
     * Defines number of maximum dropdown rows.
     *
     * @param  integer  $newValue  Maximum size of dropdown
     * @return Dropdown            Return $this for fluent interface (method chaining)
     * @see    maximumSize()
     * @see    setSize()
     */
    public function setMaximumSize(int $newValue): self
    {
        if ($newValue > 1) {
            $this->_maximumSize = $newValue;
            $this->_size = $newValue;
        } else {
            $this->_maximumSize = null;
        }

        return $this;
    }
}
