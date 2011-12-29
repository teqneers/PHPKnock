<?php
/**
 * Form Element Class
 *
 * @author		Oliver Mueller <mueller@teqneers.de>
 * @package		Knock
 * @subpackage	Classes
 * @copyright	Copyright (C) 2003-2012 TEQneers GmbH & Co. KG. All rights reserved.
 * @version		$Revision: 20147 $
 * @internal	$Id: button_bar_class.php 20147 2011-06-13 14:18:46Z oliver $
 */

/**
 * Form Element Class
 *
 * This class represents a single html form element.
 *
 * @package		Knock
 * @subpackage	Classes
 */
class FormElement {

	##################################################
	# attributes
	##################################################
	/**
	 * Name of this form element
	 *
	 * @var		string
	 */
	protected $_name	= null;


	/**
	 * The name is used as label
	 *
	 * @var		string
	 */
	protected $_label	= null;


	/**
	 * A mouse over hint which is put into a title tag around the label
	 *
	 * It will help the user to understand the meaning of the current element.
	 *
	 * @var		string
	 */
	protected $_hint;


	/**
	 * Contains the value itself
	 *
	 * It is important to know, that this value might
	 * appear in different forms. This depends on the source (either DB or
	 * http request). It should not be read directly to write it into a DB.
	 * Use {@link dbValue()} instead.
	 *
	 * @var		string
	 */
	protected $_value;


	/**
	 * Defines a default value
	 *
	 * If set to anything not empty, this value will be used in case form data is empty.
	 * The use of this value depends on the form element object. Some objects (e.g. FormElementText)
	 * will display this value instead of an empty html form.
	 *
	 * IMPORTANT: This value will not be used in text mode.
	 *
	 * @var		string
	 */
	protected $_default	= null;


	/**
	 * Defines whether {@link value()} can be empty/null/unselected
	 *
	 * Might trigger an {@link setError()}.
	 *
	 * @var		boolean
	 */
	protected $_notNull	= false;


	/**
	 * Flag which indicates a validation error
	 *
	 * @var		boolean
	 */
	protected $_error	= false;


	/**
	 * Flag to indicate if the form element has been validated
	 *
	 * @var		boolean
	 */
	protected $_isValidated	= true;


	##################################################
	# methods
	##################################################
	/**
	 * CONSTRUCTOR
	 *
	 * @param	string	$name		Name of form
	 */
	function __construct( $name ) {
		$this->_name	= $name;
	}


	#######################################################################
	# data methods
	#######################################################################
	/**
	 * This method is going to automatically fetch the correct value from the request or global namespace
	 *
	 * The data in the request has a higher priority than the global namespace data, which means, that once data
	 * is set in a request the global name space data will be ignored.
	 *
	 * @see		fetchGlobal()
	 * @see		fetchRequest()
	 */
	public function fetch() {
		// find out if this value came out of the DB or from a form
		if( isset( $_REQUEST['data'][ $this->_name ] ) ) {
			$this->fetchRequest();
		} elseif( isset( $GLOBALS['data'][ $this->_name ] ) ) {
			$this->fetchGlobal();
		} else {
			// no value has been set.
			$this->_value	= null;
		}
	}


	/**
	 * This method is going to fetch the value from global namespace
	 */
	public function fetchGlobal() {
		$global	= &$GLOBALS[ 'data' ][ $this->_name ];

		$this->setDbValue( $global );
	}


	/**
	 * This method is going to fetch the value from request
	 */
	public function fetchRequest() {
		// value was delivered by a request. Keep it like it is
		$global	= &$_REQUEST[ 'data' ][ $this->_name ];

		$this->_isValidated	= false;
		$this->setValue( $global );
	}


	/**
	 * This method will validate the element's value against defined validation rules (e.g. not null, ...)
	 *
	 * @see		setNotNull()
	 * @return	bool		TRUE if no errors occurred
	 */
	public function validate() {

		$this->setError( false );

		// elements like uploads and images will return arrays for value()
		// so simply check, if the array is empty.
		if( $this->notNull() && $this->isEmpty() ) {
			$this->setError( 'EMPTY VALUE' );
		}

		$this->_isValidated	= true;
		return !$this->error();
	}


	/**
	 * Internal name of element
	 *
	 * @see		setName()
	 * @return 	string
	 */
	public function name() {
		return $this->_name;
	}


	/**
	 * Internal name of element
	 *
	 * IMPORTANT: Changing this, will influence all fetching methods.
	 *			  Already fetch values may reference to wrong request values.
	 *
	 * @see		name()
	 * @param	string	$newValue		Name of element
	 * @return	FormElement				Return $this for fluent interface (method chaining)
	 */
	public function setName( $newValue ) {
		$this->_name	= trim($newValue);

		return $this;
	}


	/**
	 * Returns value in a DB compatible form
	 *
	 * @see 	setDbValue()
	 * @see 	setDbColumnName()
	 * @see 	dbValueMatrix()
	 * @see 	returnDbValue()
	 * @return 	Mixed
	 */
	public function dbValue() {
		return $this->_value;
	}


	/**
	 * Sets value from a DB compatible format
	 *
	 * @see 	dbValue()
	 * @see 	_fetchGlobal()
	 * @param	mixed	$newValue		DB compatible value
	 * @return	FormElement				Return $this for fluent interface (method chaining)
	 */
	public function setDbValue( $newValue ) {
		if( !is_array($newValue) ) {
			// value was delivered by DB. Keep it like it is.
			$this->_value 	= $newValue;
		} else {
			// assume that this data has been delivered with related data
			if( isset($newValue[0][$this->name()]) ) {
				$this->_value	= $newValue[0][$this->name()];
			} else {
				$this->_value	= null;
			}
		}

		return $this;
	}


	/**
	 * Returns true if element's value is empty
	 *
	 * An empty value will usually not displayed in text mode.
	 *
	 * @return 	Mixed
	 */
	public function isEmpty() {
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
	 * @see		setValue()
	 * @see		fetch()
	 * @return	string					Encoded HTML value
	 */
	public function htmlValue() {
		return htmlspecialchars( $this->value(), ENT_QUOTES, CHARSET, true );
	} // function


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


		if( $this->isEmpty() && $this->defaultValue() !== null ) {
			$this->setDbValue( $this->defaultValue() );
		}

		// define HTML attributes for input field
		$attr	= array(
			'type'			=> 'text',
			'name'			=> 'data['.$this->name().']',
			'value'			=> $this->htmlValue(),
//			'class'			=> $this->formClass(),
			'onkeypress'	=> 'if( event.keyCode==13 || event.which==13) this.form.submit();'
		);

		$value	= '<input '.Html::array2attributes( $attr ).' />';

		$ret	= '
		<tr><td '.$labelAttr.'>'.$label.'</td><td>'.$value.$this->htmlErrorMessage().'</td></tr>';

		return $ret;
	}

	/**
	 * Return error message if error occurred
	 *
	 * @return	null|string			Error message
	 */
	public function htmlErrorMessage() {
		if( $this->error() ) {
			return '
			<br /><span style="color: red;">Invalid value.</span>';
		}

		return null;
	}


	#######################################################################
	# accessor methods
	#######################################################################
	/**
	 * Accessor
	 *
	 * @see		setHint()
	 * @return	string		Mouse over help text
	 */
	public function hint() {
		return $this->_hint;
	}


	/**
	 * Accessor
	 *
	 * A mouse over hint which is put into a title tag around the label.
	 * It will help the user to understand the meaning of the current element.
	 *
	 * @see		hint()
	 * @param	string	$newValue		Mouse over help text
	 * @return	FormElement				Return $this for fluent interface (method chaining)
	 */
	public function setHint( $newValue ) {
		$this->_hint	= $newValue;

		return $this;
	}


	/**
	 * Accessor
	 *
	 * @see		setNotNull()
	 * @see		validate()
	 * @return	boolean			TRUE forbids empty values, FALSE allows empty values
	 */
	public function notNull() {
		return $this->_notNull;
	}


	/**
	 * Accessor
	 *
	 * Defines whether {@link value()} can be empty/null/unselected.
	 * Might trigger an {@link setError()}.
	 *
	 * @see		notNull()
	 * @see		validate()
	 * @param	boolean	$newValue		TRUE forbids empty values, FALSE allows empty values
	 * @return	FormElement				Return $this for fluent interface (method chaining)
	 */
	public function setNotNull( $newValue = true ) {
		$this->_notNull	= (bool)$newValue;

		return $this;
	}


	/**
	 * Accessor
	 *
	 * @see		setError()
	 * @return	bool
	 */
	public function error() {
		return $this->_error;
	}

	/**
	 * Accessor
	 *
	 * Flag which indicates a validation error.
	 *
	 * @see		error()
	 * @param	mixed	$newValue		Boolean value or occurred error type
	 * @return	FormElement				Return $this for fluent interface (method chaining)
	 */
	public function setError( $newValue = true ) {
		$this->_error	= (bool) $newValue;

		return $this;
	}


	/**
	 * Accessor
	 *
	 * @see		setValue()
	 * @see		dbValue()
	 * @see		fetch()
	 * @return	Mixed		Current value
	 */
	public function value() {
		return $this->_value;
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
	 * @param	mixed	$newValue		Input compatible value
	 * @return	FormElement				Return $this for fluent interface (method chaining)
	 */
	public function setValue( $newValue ) {
		if( !is_array($newValue) ) {
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
		} // if

		return $this;
	} // function


	/**
	 * Accessor
	 *
	 * @see		setDefaultValue()
	 * @return	string		Default value
	 */
	public function defaultValue() {
		return $this->_default;
	}


	/**
	 * Accessor
	 *
	 * If set to anything not empty, this value will be used in case form data is empty.
	 * The use of this value depends on the form element object. Some objects (e.g. FormElementText)
	 * will display this value instead of an empty html form.
	 *
	 * IMPORTANT: This value will not be used in text mode, but only activated in form mode.
	 *
	 * @see		defaultValue()
	 * @param	string	$newValue		Default value
	 * @return	FormElement				Return $this for fluent interface (method chaining)
	 */
	public function setDefaultValue( $newValue ) {
		$this->_default	= $newValue;

		return $this;
	}


	/**
	 * Will return element label
	 *
	 * @see		setLabel()
	 * @return	string		Element label
	 */
	public function label() {
		return $this->_label;
	}


	/**
	 * Will define element label
	 *
	 * @see		label()
	 * @param	string	$newValue			Element label
	 * @return	FormElement					Return $this for fluent interface (method chaining)
	 */
	public function setLabel( $newValue ) {
		$this->_label	= $newValue;

		return $this;
	}



}
?>