<?php
/*
 * Copyright (C) 2012 by TEQneers GmbH & Co. KG
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
 * Form Class
 *
 * @author         Oliver G. Mueller <mueller@teqneers.de>
 * @package        PHPKnock
 * @subpackage     Classes
 * @copyright      Copyright (C) 2003-2024 TEQneers GmbH & Co. KG. All rights reserved
 */

/**
 * Form Class
 *
 * This class represents an HTML form.
 *
 * @package        PHPKnock
 * @subpackage     Classes
 */
class Form
{

    ##################################################
    # attributes
    ##################################################
    /**
     * Name of form
     */
    protected string $_name;

    /**
     * List of form elements
     */
    protected array $_elementList = [];

    /**
     * This contains the form tag attribute list
     */
    protected array $_attributeList = [];

    /**
     * List of errors, which occured during validation
     */
    protected array $_errorList = [];


    ##################################################
    # methods
    ##################################################
    /**
     * @param  string  $name  Name of form
     */
    public function __construct(string $name)
    {
        $this->_name = $name;

        // define default form attributes
        $this->setAttribute('name', $name);
        $this->setAttribute('action', $_SERVER['SCRIPT_NAME']);
        $this->setAttribute('method', 'post');
    }


    /**
     * Return an element of the given type
     *
     * It is also possible to add more parameters. These will be transferred to
     * the element's constructor.
     *
     * The created element is going to be added to this form as well.
     *
     * IMPORTANT: certain elements will need more than one argument in their
     * constructor. These args have to be added as well. The function keeps
     * them in the same order.
     *
     * HINT: to use autoloading, it might be necessary to be case-sensitive with $elementType.
     *
     * @param  string  $elementType  Type of element, e.g. 'Text', 'DropdownDb', etc
     * @param  string  $elementName  Name of element
     * @throws ReflectionException
     */
    public function factory(string $elementType, string $elementName): FormElement
    {
        // arguments you wish to pass to constructor of the new object
        $args = func_get_args();
        array_shift($args);

        // class name of the new object
        $className = 'FormElement' . $elementType;
        if (!class_exists($className)) {
            trigger_error(get_class($this) . ': Element of type "' . $className . '" does not exists.', E_USER_ERROR);
        }

        // make a reflection object
        $reflectionObj = new ReflectionClass($className);

        // use Reflection to create a new instance, using the $args
        $obj = $reflectionObj->newInstanceArgs($args);

        $this->_elementList[$obj->name()] = $obj;
        return $obj;
    }


    /**
     * Accessor
     *
     * Return a single element with given name. NULL will be
     * returned if the element does not exist.
     *
     * @param  string  $name  Name of element
     */
    public function element(string $name): ?FormElement
    {
        return $this->_elementList[$name] ?? null; // if
    }


    /**
     * Will fetch data
     *
     * @see        fetchGlobal()
     * @see        fetchRequest()
     * @see        FormElement::fetch()
     */
    public function fetch(): void
    {
        foreach ($this->_elementList as $elementItem) {
            $elementItem->fetch();
        } // foreach

    } // function


    /**
     * Will fetch data again
     *
     * @see        FormElement::fetchGlobal()
     */
    public function fetchGlobal(): void
    {
        foreach ($this->_elementList as $elementItem) {
            $elementItem->fetchGlobal();
        } // foreach

    } // function


    /**
     * Validates all elements
     *
     * @return boolean                    TRUE indicates correct data, FALSE indicates an error
     * @see    FormElement::validate()
     */
    public function validate(): bool
    {
        $valid = true;

        // reset error list
        $this->_errorList = [];

        // check form integrity
        // check each element
        foreach ($this->_elementList as $elementItem) {
            if (!$elementItem->validate()) {
                $valid                                  = false;
                $this->_errorList[$elementItem->name()] = $elementItem->name();
            }
        } // foreach

        return $valid;
    } // function


    /**
     * Returns all DB values
     *
     * @return array
     * @see    FormElement::dbValue()
     */
    public function dbValues(): array
    {
        $ret = [];
        foreach ($this->_elementList as $elementItem) {
            $ret[$elementItem->name()] = $elementItem->dbValue();
        } // foreach

        return $ret;
    } // function


    #######################################################################
    # output methods
    #######################################################################
    /**
     * Output HTML form header
     *
     * @see        setAttribute()
     * @see        display()
     * @see        displayFooter()
     */
    public function displayFormHeader(): void
    {
        echo $this->htmlFormHeader();
    }

    /**
     * HTML form header
     *
     * @return string                    HTML code
     * @see    display()
     * @see    displayFooter()
     * @see    setAttribute()
     */
    public function htmlFormHeader(): string
    {
        return '
        <form ' . Html::array2attributes($this->attribute()) . '>
        ';
    }

    /**
     * Output HTML form body
     *
     * @see  displayForm()
     * @see  displayFormHeader()
     * @see  displayFormFooter()
     */
    public function displayFormBody(): void
    {
        echo $this->htmlFormBody();
    }

    /**
     * HTML form body
     *
     * @return string                    HTML code
     * @see    displayFormHeader()
     * @see    displayFormFooter()
     * @see    displayForm()
     */
    public function htmlFormBody(): string
    {
        $html = null;

        $elementList = $this->_elementList;
        // display hidden fields first (browsers like IE 6/7
        // may crash if hidden fields are displayed between
        // table rows).
        foreach ($elementList as $key => $elementItem) {
            if ($elementItem instanceof FormElementHidden) {
                $html .= $elementItem->htmlFormRow();
                unset($elementList[$key]);
            }
        }

        $html .= '
		<table class="formClass">
		<tbody>';

        foreach ($elementList as $elementItem) {
            // display group-less elements
            $html .= $elementItem->htmlFormRow();
        }

        $html .= '
		</tbody>
		</table>';

        return $html;
    }

    /**
     * Output HTML form footer
     *
     * @see   displayForm()
     * @see   displayFormHeader()
     */
    public function displayFormFooter(): void
    {
        echo $this->htmlFormFooter();
    }

    /**
     * HTML form footer
     *
     * @return string                    HTML code
     * @see    displayFormHeader()
     * @see    displayForm()
     */
    public function htmlFormFooter(): string
    {
        return '
		</form>';
    }

    /**
     * Output HTML form header/body/footer
     *
     * @see   displayForm()
     * @see   displayFormHeader()
     * @see   displayFormFooter()
     */
    public function displayForm(): void
    {
        echo $this->htmlForm();
    }

    /**
     * HTML form header/body/footer
     *
     * @return string                    HTML code
     * @see    displayFormHeader()
     * @see    displayFormFooter()
     * @see    displayForm()
     */
    public function htmlForm(): string
    {
        return $this->htmlFormHeader() . $this->htmlFormBody() . $this->htmlFormFooter();
    }


    #######################################################################
    # accessor methods
    #######################################################################
    /**
     * Returns the value of the form tag value related to the given key
     *
     * If the key is null, the whole array will be returned. A non-existing value will be returned as null.
     *
     * IMPORTANT: all keys are lowercase.
     *
     * @param  string|null  $key  Name of attribute
     * @return null|string|array  Attribute value
     * @see    setAttribute
     */
    public function attribute(?string $key = null)
    {
        if ($key === null) {
            return $this->_attributeList;
        }

        return $this->_attributeList[strtolower($key)] ?? null;
    }


    /**
     * Will set the form html attributes
     *
     * @param  string  $key    Lower case name of attribute
     * @param  string  $value  Value of attribute
     * @return Form            Return $this for fluent interface (method chaining)
     * @see    attribute
     */
    public function setAttribute(string $key, string $value): self
    {
        $this->_attributeList[strtolower($key)] = $value;

        return $this;
    }
}
