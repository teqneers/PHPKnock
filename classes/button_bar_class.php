<?php
/**
 * Button Bar Class
 *
 * @author		Oliver Mueller <mueller@teqneers.de>
 * @package		Knock
 * @subpackage	Classes
 * @copyright	Copyright (C) 2003-2012 TEQneers GmbH & Co. KG. All rights reserved.
 * @version		$Revision: 20147 $
 * @internal	$Id: button_bar_class.php 20147 2011-06-13 14:18:46Z oliver $
 */

/**
 * Button Bar Class
 *
 * This class represents a html button bar.
 *
 * @package		Knock
 * @subpackage	Classes
 */
class ButtonBar {

	##################################################
	# attributes
	##################################################
	/**
	 * Button list
	 *
	 * @var 	array
	 */
	protected $_buttons	= array();


	##################################################
	# methods
	##################################################
	/**
	 * Display button bar as HTML code
	 */
	public function display() {
		foreach( $this->_buttons as $button ) {
			if( $button['type'] == 'html' ) {
				echo '<input class="button" type="submit" '.Html::array2attributes($button['attributes']).'>';
			} else {
				echo '<input class="button" type="submit" '.Html::array2attributes($button['attributes']).' onclick="javascript:'.$button['url'].'">';
			}
		}
	}

	/**
	 * Add element
	 *
	 * This method is a helper function which make it easier to use the
	 * display classes. this will get the default parameter to handle
	 * for a simple element and add it to the element list. It will override
	 * the base method, because buttons will often use images.
	 *
	 * @param	string		$key		Key used as unique identifier
	 * @param 	string		$type		Display element type (html|js|ajax)
	 * @param 	string		$name		Name
	 * @param 	string		$hint		Hint text
	 * @param 	string		$url		Url or type specific linking
	 * @param 	string		$target		Target
	 */
	public function add( $key, $type = 'html', $name, $hint = null, $url = '#', $target = null ) {

		// get a new element using the analog factory method
		$this->_buttons[]	= array(
			'key'			=> $key,
			'type'			=> $type,
			'url'			=> $url,
			'target'		=> $target,
			'attributes'	=> array(
				'value'		=> $name,
				'name'		=> $hint,
				'alt'		=> $hint,
			)
		);
	}

	/**
	 * Alias for add() specialized for html elements
	 *
	 * @param	string		$key		Key used as unique identifier
	 * @param 	string		$name		Name
	 * @param 	string		$hint		Hint text
	 * @param 	string		$url		Url or type specific linking
	 * @param 	string		$target		Target
	 */
	public function addHtml( $key, $name, $hint = null, $url = '#', $target = null ) {
		$this->add( $key, 'html', $name, $hint, $url, $target );
	}

	/**
	 * Alias for add() specialized for javascript elements
	 *
	 * @param	string		$key		Key used as unique identifier
	 * @param 	string		$name		Name
	 * @param 	string		$hint		Hint text
	 * @param 	string		$url		Url or type specific linking
	 * @param 	string		$target		Target
	 */
	public function addJs( $key, $name, $hint = null, $url = '#', $target = null ) {
		$this->add( $key, 'js', $name, $hint, $url, $target );
	}

}
?>