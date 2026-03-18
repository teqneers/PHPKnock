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

namespace PHPKnock;

use PHPKnock\Form\Element;

/**
 * Form Class
 *
 * This class represents an HTML form.
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
     *
     * @var array<string, Element>
     */
    protected array $_elementList = [];

    /**
     * This contains the form tag attribute list
     *
     * @var array<string, string>
     */
    protected array $_attributeList = [];

    /**
     * List of errors, which occured during validation
     *
     * @var array<string, string>
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
        $this->setAttribute('action', is_string($_SERVER['SCRIPT_NAME'] ?? null) ? $_SERVER['SCRIPT_NAME'] : '');
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
     * HINT: to use autoloading, it might be necessary to be case-sensitive with $elementType.
     *
     * @param  string  $elementType  Type of element, e.g. 'Text', 'Dropdown', etc
     * @param  string  $elementName  Name of element
     * @param  string  $label        Label for the element (not used for Hidden)
     * @param  array<string|int, string>  $options  Options for Dropdown element
     */
    public function factory(string $elementType, string $elementName, string $label = '', array $options = []): Element
    {
        $obj = match ($elementType) {
            'Text' => new Element\Text($elementName, $label),
            'Password' => new Element\Password($elementName, $label),
            'Integer' => new Element\Integer($elementName, $label),
            'Dropdown' => new Element\Dropdown($elementName, $label, $options),
            'Hidden' => new Element\Hidden($elementName),
            default => throw new \InvalidArgumentException(self::class . ': Element of type "' . $elementType . '" does not exist.'),
        };

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
    public function element(string $name): ?Element
    {
        return $this->_elementList[$name] ?? null;
    }


    /**
     * Will fetch data
     *
     * @see        fetchGlobal()
     * @see        fetchRequest()
     * @see        Element::fetch()
     */
    public function fetch(): void
    {
        foreach ($this->_elementList as $elementItem) {
            $elementItem->fetch();
        }
    }


    /**
     * Will fetch data again
     *
     * @see        Element::fetchGlobal()
     */
    public function fetchGlobal(): void
    {
        foreach ($this->_elementList as $elementItem) {
            $elementItem->fetchGlobal();
        }
    }


    /**
     * Validates all elements
     *
     * @return boolean                    TRUE indicates correct data, FALSE indicates an error
     * @see    Element::validate()
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
        }

        return $valid;
    }


    /**
     * Returns all DB values
     *
     * @return array<string, mixed>
     * @see    Element::dbValue()
     */
    public function dbValues(): array
    {
        $ret = [];
        foreach ($this->_elementList as $elementItem) {
            $ret[$elementItem->name()] = $elementItem->dbValue();
        }

        return $ret;
    }


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
        $html = '';

        $elementList = $this->_elementList;
        // display hidden fields first (browsers like IE 6/7
        // may crash if hidden fields are displayed between
        // table rows).
        foreach ($elementList as $key => $elementItem) {
            if ($elementItem instanceof Element\Hidden) {
                $html .= $elementItem->htmlFormRow();
                unset($elementList[$key]);
            }
        }

        $html .= '
		<div class="form-fields">';

        foreach ($elementList as $elementItem) {
            // display group-less elements
            $html .= $elementItem->htmlFormRow();
        }

        $html .= '
		</div>';

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
     * @return ($key is null ? array<string, string> : string|null)
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
