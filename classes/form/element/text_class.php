<?php
/**
 * Form Element Text Class
 *
 * @author		Oliver Mueller <mueller@teqneers.de>
 * @package		Knock
 * @subpackage	Classes
 * @copyright	Copyright (C) 2003-2012 TEQneers GmbH & Co. KG. All rights reserved.
 * @version		$Revision: 20147 $
 * @internal	$Id: button_bar_class.php 20147 2011-06-13 14:18:46Z oliver $
 */

/**
 * Form Element Text Class
 *
 * This class represents a single html form element of type text.
 *
 * @package		Knock
 * @subpackage	Classes
 */
class FormElementText extends FormElement {

	#######################################################################
	# attributes
	#######################################################################
	/**
	 * Validate value against this regular expression
	 *
	 * If this value is set to anything not empty, the value will be validated
	 * against this regular expression (PCRE compatible, not POSIX!).
	 * This value might produce an {@link setError()} if the regular expression
	 * is not found in {@link value()}.
	 *
	 * @var		string
	 */
	protected $_validRegExp	= null;


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


	/**
	 * This function will validate the element's value against defined validation rules (e.g. not null, ...)
	 *
	 * The validation will also activate the error flag, if any
	 * rule is broken, which will trigger the error message in
	 * form mode output.
	 *
	 * @see		setMaximumLength()
	 * @see		FormElement::validate()
	 * @return	boolean		TRUE if no errors occurred
	 */
	public function validate() {
		parent::validate();

		$value	= trim($this->value());

		// only use reg exp validation if value is not empty.
		// an empty value validation should be made with setNotNull()
		if( !$this->isEmpty() && $this->validRegExp() != '' && !preg_match( $this->validRegExp(), $value ) ) {
			$this->setError( 'REGEXP MISMATCH' );
		}

		$this->_isValidated	= true;
		return !$this->error();
	}


	#######################################################################
	# data methods
	#######################################################################


	#######################################################################
	# output methods
	#######################################################################


	#######################################################################
	# accessor methods
	#######################################################################
	/**
	 * Accessor
	 *
	 * @see		setValidRegExp()
	 * @see		validate()
	 * @return	string		Valid PCRE compatible regular expression
	 */
	public function validRegExp() {
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
	 * An element which will only exept numbers as a valid input:
	 * <code>
	 *   $element->setValidRegExp( '/^[0-9]+$/' );
	 * </code>
	 *
	 * @see		validRegExp()
	 * @param	string	$regExp			Valid regular expression (PCRE)
	 * @return	FormElementText			Return $this for fluent interface (method chaining)
	 */
	public function setValidRegExp( $regExp ) {
		$this->_validRegExp	= $regExp;

		return $this;
	}





}
?>