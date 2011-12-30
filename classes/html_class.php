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
 * HTML Class
 *
 * @author		Oliver G. Mueller <mueller@teqneers.de>
 * @package		PHPKnock
 * @subpackage	Classes
 * @copyright	Copyright (C) 2003-2012 TEQneers GmbH & Co. KG. All rights reserved
 */

/**
 * HTML Class
 *
 * This class helps to keep the html headers and footers
 * correct and alike.
 *
 * @package		PHPKnock
 * @subpackage	Classes
 */
class Html {

	#######################################################################
	# attributes
	#######################################################################
	/**
	 * This contains the body tag attribute list
	 *
	 * @var		array
	 */
	protected $_attributeList	= array();

	/**
	 * This is the HTML title
	 *
	 * @var		string
	 */
	protected $_title	= '';

	/**
	 * This will hold information of all javascripts which must be included
	 *
	 * @var		array
	 */
	protected $_javaScriptList	= array();

	/**
	 * This will hold information of all stylesheet which should be available
	 *
	 * @var		array
	 */
	protected $_styleSheetList	= array();

	/**
	 * This represents the favorite icon, shown in most browser URL bars
	 *
	 * @var		string
	 */
	protected $_favicon;

	/**
	 * Contains the HTML language information
	 *
	 * @var		string
	 */
	protected $_language	= 'en';

	/**
	 * Additional meta attributes
	 *
	 * @var 	array
	 */
	protected $_metaList	= array();

	/**
	 * Defer javascript loading
	 *
	 * @var		boolean
	 */
	protected $_deferJavascript	= false;


	#######################################################################
	# methods
	#######################################################################
	/**
	 * Accessor
	 *
	 * Returns the value of the body tag value related to the
	 * given key. If key is null, the whole array will be returned.
	 * A non existing value will be return as null.
	 * IMPORTANT: all keys are lowercase.
	 *
	 * @see			setBodyAttribute
	 * @param		string		$key	Name of attribute
	 * @return		string
	 */
	public function bodyAttribute( $key = null ) {
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
	 * Will set the HTML title in header.
	 *
	 * @see			bodyAttribute
	 * @param		string	$key	Lower case name of attribute
	 * @param		string	$value	Value of attribute
	 */
	public function setBodyAttribute( $key, $value ) {
		$this->_attributeList[ strtolower($key) ]	= $value;
	}


	/**
	 * Accessor
	 *
	 * @see		setTitle()
	 * @return	string						Title string
	 */
	public function title() {
		return $this->_title;
	}


	/**
	 * Accessor
	 *
	 * Will set the HTML title in header.
	 *
	 * @see		title()
	 * @param	string	$newValue			Title string
	 */
	public function setTitle($newValue) {
		$this->_title	= $newValue;
	}


	/**
	 * Accessor
	 *
	 * @see		setLanguage()
	 * @return	string						Language information
	 */
	public function language() {
		return $this->_language;
	}


	/**
	 * Accessor
	 *
	 * Will set the XHTML language.
	 *
	 * @see		language()
	 * @param	string	$newValue			Language information
	 */
	public function setLanguage( $newValue ) {
		$this->_language	= $newValue;
	}


	/**
	 * Accessor
	 *
	 * Will set a stylesheet. All other stylesheet, which might have been set already, will be deleted.
	 *
	 * @param		string	$css			Relative path to document root
	 * @param		string	$media			Media to be used with this stylesheet (e.g. screen, print)
	 * @param		string	$title			Name of stylesheet, which might be shown in some browsers
	 * @param		boolean	$alternative	Flag if this is an alternative stylesheet
	 * @param		string	$conditional	Comment condition to wrap html into
	 */
	public function setStyleSheet( $css, $media = 'all', $title = 'Default', $alternative = false, $conditional = null ) {
		if ( $css ) {
			$this->_styleSheetList	= array();
			$this->addStyleSheet( $css, $media, $title, $alternative, $conditional );
		}
	}

	/**
	 * Adds a stylesheet to the current set
	 *
	 * @param	string	$css				Relative path to document root
	 * @param	string	$media				Media to be used with this stylesheet (e.g. screen, print)
	 * @param	string	$title				Name of stylesheet, which might be shown in some browsers
	 * @param	boolean	$alternative		Flag if this is an alternative stylesheet
	 * @param	string	$conditional		Comment condition to wrap html into
	 */
	public function addStyleSheet( $css, $media = 'all', $title = 'Default', $alternative = false, $conditional = null ) {
		if ( $css ) {
			$this->_styleSheetList[$css]	= array( 'css' => $css,
													 'media' => $media,
													 'title' => $title,
													 'alternative' => $alternative,
													 'conditional' => $conditional );
		}
	}


	/**
	 * Drops a stylesheet of the current set
	 *
	 * @param	string	$css				Relative path to document root
	 */
	public function dropStyleSheet( $css ) {
		if( array_key_exists( $css, $this->_styleSheetList ) ) {
			unset( $this->_styleSheetList[$css] );
		} // if
	} // function


	/**
	 * Accessor
	 *
	 * @see		setFavicon()
	 * @return	string						Relative path to document root
	 */
	public function favicon() {
		return $this->_favicon;
	}


	/**
	 * Accessor
	 *
	 * Will set the favorite icon, which some browser will show in the URL bar or as a bookmark icon.
	 *
	 * @see		favicon()
	 * @param	string	$icon				Relative path to document root
	 */
	public function setFavicon( $icon ) {
		$this->_favicon	= $icon;
	}


	/**
	 * Returns a list of all JavaScripts
	 *
	 * @see		addJavaScript()
	 * @see		dropJavaScript()
	 * @return	array
	 */
	public function javaScript() {
		return array_keys( $this->_javaScriptList );
	}


	/**
	 * This function adds a JavaScript file to the HTML header
	 *
	 * @see		javaScript()
	 * @see		dropJavaScript()
	 * @param	string	$script				Relative path to document root (e.g. '/openWindows.js')
	 */
	public function addJavaScript( $script ) {
		$this->_javaScriptList[$script]	= '<script src="'.$script.'" type="text/javascript"></script>';
	}


	/**
	 * This function drops a JavaScript file of the current list
	 *
	 * @see		javaScript()
	 * @see		addJavaScript()
	 * @param	string	$script				Relative path to document root (e.g. '/openWindows.js')
	 */
	public function dropJavaScript( $script ) {
		if( array_key_exists( $script, $this->_javaScriptList ) ) {
			unset( $this->_javaScriptList[$script] );
		} // if
	}

	/**
	 * Accessor
	 *
	 * This function will set refresh meta information.
	 *
	 * @param	string	$url			Path to new URL
	 * @param	integer	$time			Time in seconds
	 */
	public function setRefresh( $url, $time ) {
		if ( $url ) {
			$this->addMeta( array( 'http-equiv'	=> 'refresh',
								   'content'	=> (int)abs($time).'; URL='.$url ) );
		}
	}


	/**
	 * Accessor
	 *
	 * @see		addMeta()
	 * @return	array					Attribute array
	 */
	public function meta() {
		return $this->_metaList;
	}


	/**
	 * Accessor
	 *
	 * Add some additional meta tags. A single meta tag should be defined
	 * as an array containing a key=>value list for all attributes to add to.
	 *
	 * @see		meta()
	 * @param	array	$attr			Attribute array
	 */
	public function addMeta( $attr ) {
		if ( is_array( $attr ) && !empty( $attr ) ) {
			$this->_metaList[]	= $attr;
		}
	}


	/**
	 * This function will return the compiled header information
	 *
	 * This includes all information like JavaScripts, CascadingStyleSheets, and so on
	 *
	 * @see		displayFooter()
	 * @return 	string					Html header
	 */
	public function header() {

		$language	= ( !empty($this->_language) ) ? ' xml:lang="en" lang="en"' : null;
		$header		= '';

		if ( !headers_sent() ) {
			header( 'Content-Type: text/html; charset='.CHARSET );
		}

		// echo '<?xml version="1.0"? >';
		$header	.= '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';

		$header	.= '
<html xmlns="http://www.w3.org/1999/xhtml"'.$language.'>
<head>';

		$metaList	= $this->_metaList;
		// this ensures that the content-type-meta-tag is printed first on page
		array_unshift($metaList, array( 'http-equiv'	=> 'content-type',
										'content'		=> 'text/html; charset='.CHARSET ));
		foreach( $metaList as $meta ) {
			$header	.= '
	<meta '.self::array2attributes( $meta ).' />';
		}

		$header	.= '
	<title>'.$this->_title.'</title>';

		if ( count($this->_styleSheetList) > 0 ) {
			foreach( $this->_styleSheetList as $style ) {
				$type	= ($style['alternative']) ? 'alternative stylesheet' : 'stylesheet';
				if ( $style['conditional'] == null ) {
					$header	.= '
	<link rel="'.$type.'" type="text/css" href="'.$style['css'].'" title="'.$style['title'].'" media="'.$style['media'].'" />';
				} else {
					$header	.= '
	<!--[if '.$style['conditional'].']><link rel="'.$type.'" type="text/css" href="'.$style['css'].'" title="'.$style['title']
						.'" media="'.$style['media'].'" /><![endif]-->';
				}
			} // foreach
		} // if

		if ( !$this->_deferJavascript && count($this->_javaScriptList) > 0 ) {
			$header	.= '
	'.implode("\n	", $this->_javaScriptList);
		}

		if ( $this->_favicon ) {
			$header	.= '
	<link rel="shortcut icon" href="'.$this->_favicon.'" />';
		}

		$header	.= '
</head>
<body '.self::array2attributes( $this->_attributeList ).'>';
		$header	.= "\n";

		return $header;
	}

	/**
	 * This function will return the HTML footer
	 *
	 * @see		displayHeader()
	 * @return 	string					Html footer
	 */
	public function footer() {
		$footer	= '';

		if ( $this->_deferJavascript && count($this->_javaScriptList) > 0 ) {
			$footer	.= '
	'.implode("\n	", $this->_javaScriptList);
		}

		$footer	.= '
</body>
</html>';

		return $footer;
	}

	/**
	 * This function will print out the compiled header information
	 *
	 * This includes all information like JavaScripts, CascadingStyleSheets, and so on
	 *
	 * @see		displayFooter()
	 */
	public function displayHeader() {
		echo $this->header();
	}

	/**
	 * This function will print out the HTML footer
	 *
	 * @see		displayHeader()
	 */
	public function displayFooter() {
		echo $this->footer();
	}


	/**
	 * This function will convert an associative array into a string
	 *
	 * Its using keys as attribute name and values as attribute value.
	 * Very useful to convert an array into HTML tag attributes.
	 *
	 * @param	array	$attributes	Associative array with attribute values
	 * @return	string				Imploded array like: key="value" key="value" ...
	 */
	public static function array2attributes( $attributes ) {
		$ret	= array();
		foreach( $attributes as $key => $value ) {
			if( $value !== null ) {
				if( !is_array($value) ) {
					$ret[]	= $key.'="'.$value.'"';
				} elseif( count($value) > 0 ) {
					// recursive call, in case an attribute is an array
					$ret[]	= self::array2attributes( $value );
				}
			}
		}

		return implode( ' ', $ret );
	}


} // class
?>
