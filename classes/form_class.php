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
 * @author		Oliver G. Mueller <mueller@teqneers.de>
 * @package		PHPKnock
 * @subpackage	Classes
 * @copyright	Copyright (C) 2003-2012 TEQneers GmbH & Co. KG. All rights reserved
 */

/**
 * Form Class
 *
 * This class represents a html form.
 *
 * @package		PHPKnock
 * @subpackage	Classes
 */
class Form {

	##################################################
	# attributes
	##################################################
	/**
	 * Name of form
	 *
	 * @var		string
	 */
	protected $_name	= null;


	/**
	 * List of form elements
	 *
	 * @var		array
	 */
	protected $_elementList	= array();


	/**
	 * This contains the form tag attribute list
	 *
	 * @var		array
	 */
	protected $_attributeList	= array();


	##################################################
	# methods
	##################################################
	/**
	 * CONSTRUCTOR
	 *
	 * @param	string	$name		Name of form
	 */
	public function __construct( $name ) {
		$this->_name	= $name;

		// define default form attributes
		$this->setAttribute( 'name', $name );
		$this->setAttribute( 'action', $_SERVER['SCRIPT_NAME'] );
		$this->setAttribute( 'method', 'post' );
	}


	/**
	 * Return an element of the given type
	 *
	 * It is also possible to add more parameter. These will transfered to
	 * the element's constructor.
	 *
	 * The created element is going to be added to this form as well.
	 *
	 * IMPORTANT: certain element will need more than one argument in their
	 * constructor. These args have to be added as well. The function keeps
	 * them in same order.
	 *
	 * HINT: in order to use autoloading, it might be necessary to be case
	 * sensitive with $elementType.
	 *
	 * @param	string	$elementType	Type of element, e.g. 'Text', 'DropdownDb', etc
	 * @param	string	$elementName	Name of element
	 * @return	FormElement				Form element instance
	 */
	public function factory( $elementType, $elementName ) {
		// arguments you wish to pass to constructor of new object
		$args	= func_get_args();
		array_shift($args);

		// class name of new object
		$className	= 'FormElement'.$elementType;
		if( !class_exists( $className ) ) {
			trigger_error( get_class($this).': Element of type "'.$className.'" does not exists.', E_USER_ERROR );
		}

		// make a reflection object
		$reflectionObj	= new ReflectionClass($className);

		// use Reflection to create a new instance, using the $args
		$obj	= $reflectionObj->newInstanceArgs($args);

		$this->_elementList[ $obj->name() ]	= $obj;
		return $obj;
	}


	/**
	 * Accessor
	 *
	 * Return a single element with given name. NULL will be
	 * return, if element does not exists.
	 *
	 * @param	string	$name		Name of element
	 * @return	FormElement			Form element
	 */
	public function element( $name ) {
		if( array_key_exists( $name, $this->_elementList ) ) {
			return $this->_elementList[$name];
		} else {
			$ret	= null;
			return $ret;
		} // if
	}


	/**
	 * Will fetch data
	 *
	 * @see		fetchGlobal()
	 * @see		fetchRequest()
	 * @see		FormElement::fetch()
	 */
	public function fetch() {
		foreach( $this->_elementList as $elementItem ) {
			$elementItem->fetch();
		} // foreach

	} // function


	/**
	 * Will fetch data again
	 *
	 * @see		FormElement::fetchGlobal()
	 */
	public function fetchGlobal() {
		foreach( $this->_elementList as $elementItem ) {
			$elementItem->fetchGlobal();
		} // foreach

	} // function


	/**
	 * Validates all elements
	 *
	 * @see		FormElement::validate()
	 * @return	boolean					TRUE indicates correct data, FALSE indicates an error
	 */
	public function validate() {
		$valid	= true;

		// reset error list
		$this->_errorList	= array();

		// check form integrity
		// check each element
		foreach( $this->_elementList as $elementItem ) {
			if( !$elementItem->validate() ) {
				$valid	= false;
				$this->_errorList[$elementItem->name()]	= $elementItem->name();
			}
		} // foreach

		return $valid;
	} // function


	/**
	 * Returns all DB values
	 *
	 * @see		FormElement::dbValue()
	 * @return	Array
	 */
	public function dbValues() {
		$ret	= array();
		foreach( $this->_elementList as $elementItem ) {
			$ret[$elementItem->name()]	= $elementItem->dbValue();
		} // foreach

		return $ret;
	} // function


	#######################################################################
	# output methods
	#######################################################################
	/**
	 * Output HTML form header
	 *
	 * @see		setAttribute()
	 * @see		display()
	 * @see		displayFooter()
	 */
	public function displayFormHeader() {
		echo $this->htmlFormHeader();
	}

	/**
	 * HTML form header
	 *
	 * @see		setAttribute()
	 * @see		display()
	 * @see		displayFooter()
	 * @return 	string					HTML code
	 */
	public function htmlFormHeader() {
		$html	= '
		<form '.Html::array2attributes($this->attribute()).'>
		';

		return $html;
	}

	/**
	 * Output HTML form body
	 *
	 * @see		displayForm()
	 * @see		displayFormHeader()
	 * @see		displayFormFooter()
	 */
	public function displayFormBody() {
		echo $this->htmlFormBody();
	}

	/**
	 * HTML form body
	 *
	 * @see		displayForm()
	 * @see		displayFormHeader()
	 * @see		displayFormFooter()
	 * @return 	string					HTML code
	 */
	public function htmlFormBody() {
		$html	= null;

		$elementList	= $this->_elementList;
		// display hidden fields first (browsers like IE 6/7
		// may crash, if hidden fields are displayed between
		// table rows.
		foreach( $elementList as $key => $elementItem ) {
			if( $elementItem instanceof FormElementHidden ) {
				$html	.= $elementItem->htmlFormRow();
				unset( $elementList[$key] );
			}
		}

		$html	.= '
		<table summary="" class="formClass">
		<tbody>';

		foreach( $elementList as $elementItem ) {
			// display groupless elements
			$html	.= $elementItem->htmlFormRow();
		}

		$html	.= '
		</tbody>
		</table>';

		return $html;
	}

	/**
	 * Output HTML form footer
	 *
	 * @see		displayForm()
	 * @see		displayFormHeader()
	 */
	public function displayFormFooter() {
		echo $this->htmlFormFooter();
	}

	/**
	 * HTML form footer
	 *
	 * @see		displayForm()
	 * @see		displayFormHeader()
	 * @return 	string					HTML code
	 */
	public function htmlFormFooter() {
		return '
		</form>';
	}

	/**
	 * Output HTML form header/body/footer
	 *
	 * @see		displayForm()
	 * @see		displayFormHeader()
	 * @see		displayFormFooter()
	 */
	public function displayForm() {
		echo $this->htmlForm();
	}

	/**
	 * HTML form header/body/footer
	 *
	 * @see		displayForm()
	 * @see		displayFormHeader()
	 * @see		displayFormFooter()
	 * @return 	string					HTML code
	 */
	public function htmlForm() {
		return $this->htmlFormHeader() . $this->htmlFormBody() . $this->htmlFormFooter();
	}


	#######################################################################
	# accessor methods
	#######################################################################
	/**
	 * Accessor
	 *
	 * Returns the value of the form tag value related to the
	 * given key. If key is null, the whole array will be returned.
	 * A non existing value will be return as null.
	 *
	 * IMPORTANT: all keys are lowercase.
	 *
	 * @see		setAttribute
	 * @param	string	$key	Name of attribute
	 * @return	NULL|string		Attribute value
	 */
	public function attribute( $key = null ) {
		if( $key === null ) {
			return $this->_attributeList;
		} else {
			if( array_key_exists( strtolower($key), $this->_attributeList ) ) {
				return $this->_attributeList[strtolower($key)];
			} else {
				return null;
			}
		}
	}


	/**
	 * Accessor
	 *
	 * Will set the form html attributes
	 *
	 * @see		attribute
	 * @param	string	$key			Lower case name of attribute
	 * @param	string	$value			Value of attribute
	 * @return	Form					Return $this for fluent interface (method chaining)
	 */
	public function setAttribute( $key, $value ) {
		$this->_attributeList[ strtolower($key) ]	= $value;

		return $this;
	}


}
?>