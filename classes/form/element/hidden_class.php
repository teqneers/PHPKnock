<?php
/**
 * Form Element Hidden Class
 *
 * @author		Oliver Mueller <mueller@teqneers.de>
 * @package		Knock
 * @subpackage	Classes
 * @copyright	Copyright (C) 2003-2012 TEQneers GmbH & Co. KG. All rights reserved.
 * @version		$Revision: 20147 $
 * @internal	$Id: button_bar_class.php 20147 2011-06-13 14:18:46Z oliver $
 */

/**
 * Form Element Hidden Class
 *
 * This class represents a single html form element of type hidden.
 *
 * @package		Knock
 * @subpackage	Classes
 */
class FormElementHidden extends FormElement {

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
	public function htmlFormRow() {
		if( $this->isEmpty() && $this->defaultValue() !== null ) {
			$this->setDbValue( $this->defaultValue() );
		}

		// define HTML attributes for input field
		$attr	= array(
			'type'		=> 'hidden',
			'name'		=> 'data['.$this->name().']',
			'value'		=> $this->htmlValue()
		);

		$ret	= '
		<input '.Html::array2attributes( $attr ).' />';

		return $ret;
	}


	#######################################################################
	# accessor methods
	#######################################################################



}
?>