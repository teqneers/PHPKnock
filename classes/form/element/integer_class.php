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
 * Form Element Integer Class
 *
 * @author		Oliver G. Mueller <mueller@teqneers.de>
 * @package		PHPKnock
 * @subpackage	Classes
 * @copyright	Copyright (C) 2003-2012 TEQneers GmbH & Co. KG. All rights reserved
 */

/**
 * Form Element Integer Class
 *
 * This class represents a single html form element of type integer.
 *
 * @package		PHPKnock
 * @subpackage	Classes
 */
class FormElementInteger extends FormElement {

	#######################################################################
	# Attributes
	#######################################################################
	/**
	 * Defines the minimum valid value
	 *
	 * @var		integer
	 */
	protected $_minValue;


	/**
	 * Defines the maximum valid value
	 *
	 * @var		integer
	 */
	protected $_maxValue;


	#######################################################################
	# methods
	#######################################################################
	/**
	 * CONSTRUCTOR
	 *
	 * @param	string	$name			Name of form
	 * @param	string	$label			Label content
	 */
	function __construct( $name, $label ) {
		$this->_name	= $name;
		$this->_label	= $label;
	}


	#######################################################################
	# data methods
	#######################################################################
	/**
	 * This function will validate the element's value against defined validation rules (e.g. not null, ...)
	 *
	 * @see		setNotNull()
	 * @see		FormElement::validate()
	 * @return	boolean		TRUE if no errors occurred
	 */
	public function validate() {
		$this->setError( false );

		$this->setValue( trim($this->value()) );

		// when not null is false and value is null return true and set value to null
		// otherwise return false
		if( $this->isEmpty() ) {
			if( $this->notNull() ) {
				$this->setError( 'EMPTY VALUE');
			}
			$this->setValue( null );

			return !$this->error();
		}

		// check chars
		$search	= '/^[-+]?[0-9]+$/i';
		if( !preg_match( $search, $this->value() ) ) {
			// invalid format
			$this->setError( 'INVALID FORMAT');
		} // if

		$floatValue	= floatval( $this->value() );

		// check minimum limit
		if( !$this->isEmpty() && strlen($this->minimum()) > 0 && $floatValue < $this->minimum() ) {
			$this->setError( 'MIN EXCEEDED');
		}

		// check maximum limit
		if( !$this->isEmpty() && strlen($this->maximum()) > 0 && $floatValue > $this->maximum() ) {
			$this->setError( 'MAX EXCEEDED');
		}

		$this->_isValidated	= true;
		return !$this->error();
	}


	/**
	 * Returns true if element's value is empty
	 *
	 * An empty value will usually not displayed in text mode.
	 *
	 * @return 	Mixed
	 */
	public function isEmpty() {
		return ($this->value() === null || trim($this->value()) === '');
	}


	#######################################################################
	# output methods
	#######################################################################


	#######################################################################
	# accessor methods
	#######################################################################
	/**
	 * Accessor
	 *
	 * @see		setMinimum()
	 * @return	float
	 */
	public function minimum() {
		return $this->_minValue;
	}


	/**
	 * Accessor
	 *
	 * Defines the minimum valid value.
	 *
	 * @see		minimum()
	 * @see		validate()
	 * @param	float	$value			Inclusive minimum value
	 * @return	FormElementInteger		Return $this for fluent interface (method chaining)
	 */
	public function setMinimum( $value ) {
		$this->_minValue	= $value;

		return $this;
	}


	/**
	 * Accessor
	 *
	 * @see		setMaximum()
	 * @return	float
	 */
	public function maximum() {
		return $this->_maxValue;
	}


	/**
	 * Accessor
	 *
	 * Defines the maximum valid value.
	 *
	 * @see		maximum()
	 * @see		validate()
	 * @param	float	$value			Inclusive maximum value
	 * @return	FormElementInteger		Return $this for fluent interface (method chaining)
	 */
	public function setMaximum( $value ) {
		$this->_maxValue	= $value;

		return $this;
	}


}
?>