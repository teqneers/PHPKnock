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
 * Form Element Hidden Class
 *
 * @author       Oliver G. Mueller <mueller@teqneers.de>
 * @package      PHPKnock
 * @subpackage   Classes
 * @copyright    Copyright (C) 2003-2024 TEQneers GmbH & Co. KG. All rights reserved
 */

/**
 * Form Element Hidden Class
 *
 * This class represents a single html form element of the type hidden.
 *
 * @package       PHPKnock
 * @subpackage    Classes
 */
class FormElementHidden extends FormElement
{

    #######################################################################
    # attributes
    #######################################################################


    #######################################################################
    # methods
    #######################################################################


    #######################################################################
    # data methods
    #######################################################################


    #######################################################################
    # output methods
    #######################################################################
    public function htmlFormRow(): string
    {
        if ($this->isEmpty() && $this->defaultValue() !== null) {
            $this->setDbValue($this->defaultValue());
        }

        // define HTML attributes for input field
        $attr = array(
            'type' => 'hidden',
            'name' => 'data[' . $this->name() . ']',
            'value' => $this->htmlValue()
        );

        return '
        <input ' . Html::array2attributes($attr) . ' />';
    }


    #######################################################################
    # accessor methods
    #######################################################################

}
