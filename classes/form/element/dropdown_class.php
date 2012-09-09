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
 * Form Element Dropdown Class
 *
 * @author		Oliver G. Mueller <mueller@teqneers.de>
 * @package		PHPKnock
 * @subpackage	Classes
 * @copyright	Copyright (C) 2003-2012 TEQneers GmbH & Co. KG. All rights reserved
 */

/**
 * Form Element Dropdown Class
 *
 * This class represents a single html form element for dropdown.
 *
 * @package		PHPKnock
 * @subpackage	Classes
 */
class FormElementDropdown extends FormElement {

	#######################################################################
	# attributes
	#######################################################################
	/**
	 * This array contains selectable options
	 *
	 * The key defines the DB value. The value will be shown to the user.
	 *
	 * @var		array
	 */
	protected $_options	= array();


	/**
	 * Defines the ability to select multiple options
	 *
	 * @var		boolean
	 */
	protected $_isMultiple	= false;


	/**
	 * Defines the display size of a dropdown
	 *
	 * This value has to greater 0 and should be
	 * greater 1 if {@link isMultiple()} is set.
	 *
	 * @see		setMaximumSize()
	 * @var		integer
	 */
	protected $_size	= 1;


	/**
	 * Defines a maximum displayed size of a dynamic growing dropdown
	 *
	 * @see		setSize()
	 * @var		integer
	 */
	protected $_maximumSize;


	#######################################################################
	# methods
	#######################################################################
	/**
	 * Constructor
	 *
	 * @param	string	$name			Database column name
	 * @param	string	$label			Label content
	 * @param	array	$options		Array containing values available for selection
	 */
	public function __construct( $name, $label, $options = array() ) {
		parent::__construct( $name );

		$this->setLabel( $label );
		$this->setOptions( $options );
	}


	/**
	 * Sets value from a DB compatible format
	 *
	 * @see 	dbValue()
	 * @see 	_fetchGlobal()
	 * @param	mixed	$newValue			DB compatible value
	 * @return	FormElementDropdown			Return $this for fluent interface (method chaining)
	 */
	public function setDbValue( $newValue ) {
		$this->_value	= null;
		if( !is_array($newValue) ) {
			// value was delivered by DB. Keep it like it is.
			$this->_value 	= $newValue;
		} else {
			if( is_array( reset($newValue) ) ) {
				$this->_value	= array();
				// assume that this data has been delivered with related data
				// just pick the dbColumnName
				if( isset($newValue[0][$this->name()]) ) {
					// ensure to get all rows from the given matrix data
					foreach( $newValue as $row ) {
						$this->_value[]	= $row[$this->name()];
					}
				}
			} else {
				$this->_value	= array_values($newValue);
			}
		} // if

		return $this;
	} // function


	/**
	 * Returns true if element's value is empty
	 *
	 * An empty value will usually not displayed in text mode.
	 *
	 * @return 	mixed
	 */
	public function isEmpty() {
		return (is_array($this->_value) && count($this->_value) === 0) || (!is_array($this->_value) && strlen($this->_value) === 0 );
	}


	/**
	 * Accessor
	 *
	 * Will define element value. It is important to know, that this value might
	 * appear in different forms and should be set using the {@link fetch()} method.
	 * Different values depend on the data source (either DB or http request).
	 * The value should not be read directly from this method in order to write
	 * it into a DB. Use method {@link dbValue()} instead.
	 *
	 * @see		value()
	 * @see		dbValue()
	 * @see		fetch()
	 * @see		_fetchRequest()
	 * @param	mixed	$newValue			Input compatible value
	 * @return	FormElementDropdown			Return $this for fluent interface (method chaining)
	 */
	public function setValue( $newValue ) {
		if( !is_array($newValue) || !is_array(reset($newValue)) ) {
			// value was delivered by DB. Keep it like it is.
			$this->_value 	= $newValue;
		} else {
			// assume that this data has been delivered with related data
			// just pick the dbColumnName
			if( isset($newValue[0][$this->name()]) ) {
				$this->_value	= $newValue[0][$this->name()];
			} else {
				$this->_value	= null;
			}
		}

		// ensure that all preselected values are of type STRING.
		// this is important in order to mark the correct ones
		// in showForm-function.
		if( $this->_value !== null ){

			if( !is_array($this->_value) ) {
				$this->_value	= (string)$this->_value;
			} else {
				// make sure that all values are strings
				$this->_value	= array_map( 'strval', $this->_value );
			} // if

		} // if

		return $this;
	} // function


	/**
	 * Accessor
	 *
	 * Returns the current available options to choose from.
	 *
	 * @see		setOptions()
	 * @param	mixed		$value		Optional option value
	 * @return	array|mixed				Complete option array or in case a value was given
	 *									related value name or NULL if option does not exists
	 */
	public function options( $value = null ) {
		if( $value === null ) {
			return $this->_options;
		} else {

			// only add existing keys to output
			if( array_key_exists( $value, $this->_options ) ) {
				return $this->_options[$value];
			}
			return null;
		}
	}


	/**
	 * Accessor
	 *
	 * Define the current available options to choose from.
	 *
	 * @see		options()
	 * @param	array	$newValue			Key will be returned, value will be shown to the user
	 * @return	FormElementDropdown			Return $this for fluent interface (method chaining)
	 */
	public function setOptions( $newValue ) {
		$this->_options	= (array)$newValue;

		return $this;
	} // function


	#######################################################################
	# data methods
	#######################################################################
	/**
	 * This function will validate the element's value against defined validation rules (e.g. not null, ...)
	 *
	 * The validation will also activate the error flag, if any
	 * rule is broken, which will trigger the error message in
	 * form mode output.
	 *
	 * @see		setNotNull()
	 * @see		FormElement::validate()
	 * @return	boolean		TRUE if no errors occurred
	 */
	public function validate() {
		$this->setError( false );

		// remove empty value which might have been set by setValueWhenEmpty
		if( is_array( $this->_value ) ) {
			// find empty value in array
			$key	= array_search( '', $this->_value, true );
			if( $key !== false ) {
				unset($this->_value[$key]);
			} // if
		} else {
			if( $this->_value === '' ) {
				$this->_value	= null;
			}
		}

		// if not null is set and no value is set an error will occur
		if( $this->notNull() && ( $this->value() === null || count($this->value()) == 0) ) {
			$this->setError( 'EMPTY VALUE' );
		}

		$this->_isValidated	= true;
		return !$this->error();
	}


	#######################################################################
	# output methods
	#######################################################################
	/**
	 * Generate HTML form output for element
	 *
	 * @return	string			HTML output
	 */
	public function htmlFormRow() {
		$attr			= array();
		//		$attr['class']	= $this->formClass();

		$labelAttr	= Html::array2attributes( $attr );

		$label	= '<label '.Html::array2attributes( $attr ).'>';

		if( $this->hint() != '' ) {
			// show help cursor when a hint is given and the mouse hovers above the label
			$label	.= '<span style="cursor:help;" title="'.htmlentities( $this->hint(), ENT_QUOTES, CHARSET, true ).'">'.$this->label().'</span>';
		} else {
			$label	.= $this->label();
		}
		$label	.= '</label>';


		if( !$this->isMultiple() && ( $this->isEmpty() ) && $this->defaultValue() !== null ) {
			$this->setDbValue( $this->defaultValue() );
		}

		// Evaluate dynamic size. Define its maximum by comparing no. of
		// option values and the maximum given size.
		if( $this->_maximumSize !== null ) {
			$size	=  min($this->_maximumSize, count($this->options()) );
		} else {
			$size	= $this->_size;
		}

		$value	= $this->value();


		// define HTML attributes for select tag
		$attr	= array(
			'name'		=> 'data['.$this->name().']',
//			'class'		=> $this->formClass(),
			'size'		=> $size,
			'multiple'	=> null,
			'onkeypress'	=> 'if( event.keyCode==13 || event.which==13) this.form.submit();'
		);

		if( $this->isMultiple() ) {
			$attr['multiple']	= 'multiple';
			$attr['name']		.= '[]';
		}

		$output	= '<select '.Html::array2attributes( $attr ).' />';

		if( is_array( $this->options() ) ) {
			foreach( $this->options() as $key => $option ) {
				// SELECTED must be compared as STRICT because sometimes the values are '0'.
				$tmp		= count($value) && in_array((string)$key, (array)$value, true) != false ? 'selected' : null;
				$optionAttr	= array(
					'value'		=> htmlspecialchars( $key, ENT_QUOTES, 'UTF-8', true ),
					'selected'	=> $tmp
				);

				$output	.= '
						<option '.Html::array2attributes( $optionAttr ).'>'.htmlspecialchars( $option, ENT_QUOTES, 'UTF-8', true ).'</option>';

			}
		}
		$output	.= '
		</select>';

		$ret	= '
		<tr><td '.$labelAttr.'>'.$label.'</td><td>'.$output.$this->htmlErrorMessage().'</td></tr>';

		return $ret;
	}


	#######################################################################
	# accessor methods
	#######################################################################
	/**
	 * Accessor
	 *
	 * @see		setIsMultiple()
	 * @return	boolean		TRUE on multiple selection, FALSE on single selection
	 */
	public function isMultiple() {
		return $this->_isMultiple;
	}


	/**
	 * Accessor
	 *
	 * Defines the ability to select multiple options.
	 *
	 * @see		isMultiple()
	 * @param	boolean	$newValue			TRUE on multiple selection, FALSE on single selection
	 * @return	FormElementDropdown			Return $this for fluent interface (method chaining)
	 */
	public function setIsMultiple( $newValue = true ) {
		$this->_isMultiple	= $newValue;

		// if dropdown is set to multiple and size has not been set
		// yet, the dynamic maximum size will be set to a default of 5.
		if( $newValue && $this->_size == 1 ) {
			$this->setMaximumSize( 5 );
		}

		return $this;
	}


	/**
	 * Accessor
	 *
	 * @see		setSize()
	 * @see		maximumSize()
	 * @return	integer		Size of dropdown
	 */
	public function size() {
		return $this->_size;
	}

	/**
	 * Accessor
	 *
	 * Defines number of dropdown rows.
	 *
	 * IMPORTANT: Any given dynamic {@link maximumSize()} is going to be
	 * resetted. A size < 2 will deactivate multiple selection.
	 *
	 * @see		size()
	 * @see		setMaximumSize()
	 * @param	integer	$newValue			Size of dropdown
	 * @return	FormElementDropdown			Return $this for fluent interface (method chaining)
	 */
	public function setSize( $newValue ) {
		$this->_size	= max( 1, abs($newValue) );

		if( $this->_size == 1 ) {
			$this->setIsMultiple( false );
		}

		// reset dynamic size
		$this->_maximumSize	= null;

		return $this;
	}

	/**
	 * Accessor
	 *
	 * @see		setMaximumSize()
	 * @see		size()
	 * @return	integer		Maximum size of dropdown
	 */
	public function maximumSize() {
		return $this->_maximumSize;
	}

	/**
	 * Accessor
	 *
	 * Defines number of maximum dropdown rows. The dropdown
	 * will grow with each option until maximum size is
	 * reached. E.g. this value is set to 10 but there are only
	 * 4 options to select from, than the dropdown's size is going
	 * to be 4. If more than 10 options are given, the size is
	 * going to be 10.
	 *
	 * IMPORTANT: A value below 2 will deactivate this feature.
	 *
	 * @see		maximumSize()
	 * @see		setSize()
	 * @param	integer	$newValue			Maximum size of dropdown
	 * @return	FormElementDropdown			Return $this for fluent interface (method chaining)
	 */
	public function setMaximumSize( $newValue ) {
		if( $newValue > 1 ) {
			$this->_maximumSize	= $newValue;
			// set size to the same value (seems to be a good idea)
			$this->_size	= $newValue;
		} else {
			// deactivate feature if value is below 2
			$this->_maximumSize	= null;
		}

		return $this;
	}


}
?>