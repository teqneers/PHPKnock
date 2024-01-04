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
 * Form Element Password Class
 *
 * @author         Oliver G. Mueller <mueller@teqneers.de>
 * @package        PHPKnock
 * @subpackage     Classes
 * @copyright      Copyright (C) 2003-2024 TEQneers GmbH & Co. KG. All rights reserved
 */

/**
 * Form Element Password Class
 *
 * This class represents a single html form element of type password.
 *
 * @package        PHPKnock
 * @subpackage     Classes
 */
class FormElementPassword extends FormElement
{

    #######################################################################
    # attributes
    #######################################################################
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


    #######################################################################
    # data methods
    #######################################################################


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
            'type'       => 'password',
            'name'       => 'data[' . $this->name() . ']',
            'value'      => null,
//			'class'			=> $this->formClass(),
            'onkeypress' => 'if( event.keyCode==13 || event.which==13) this.form.submit();',
        ];

        $value = '<input ' . Html::array2attributes($attr) . ' />';
        return '
		<tr><td ' . $labelAttr . '>' . $label . '</td><td>' . $value . $this->htmlErrorMessage() . '</td></tr>';
    }




    #######################################################################
    # accessor methods
    #######################################################################
    /**
     * Accessor
     *
     * @return string        Valid PCRE compatible regular expression
     * @see    validate()
     * @see    setValidRegExp()
     */
    public function validRegExp(): string
    {
        return $this->_validRegExp;
    }


    /**
     * Accessor
     *
     * If this value is set to anything not empty, the value will be validated
     * against this regular expression (PCRE compatible, not POSIX!).
     * This value might produce an {@link setError()} if the regular expression
     * is not found in {@link value()}.
     *
     * Example:
     * An element which will only except numbers as a valid input:
     * <code>
     *   $element->setValidRegExp( '/^[0-9]+$/' );
     * </code>
     *
     * @param  string  $regExp  Valid regular expression (PCRE)
     * @see    validRegExp()
     */
    public function setValidRegExp(string $regExp): self
    {
        $this->_validRegExp = $regExp;

        return $this;
    }


}
