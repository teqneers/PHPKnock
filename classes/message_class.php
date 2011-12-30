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
 * Message Class
 *
 * @author		Oliver G. Mueller <mueller@teqneers.de>
 * @package		PHPKnock
 * @subpackage	Classes
 * @copyright	Copyright (C) 2003-2012 TEQneers GmbH & Co. KG. All rights reserved
 */

/**
 * Message Class
 *
 * Messages, Warning and Errors can be displayed,
 * added or cleared.
 *
 * @package		PHPKnock
 * @subpackage	Classes
 */
class Message {

	/**
	 * Error constant
	 */
	const ERROR	= 1;

	/**
	 * Warning constant
	 */
	const WARNING	= 2;

	/**
	 * Notice constant
	 */
	const NOTICE	= 4;

	/**
	 * Message constant
	 */
	const MESSAGE	= 8;

	/**
	 * All messages constant
	 */
	const ALL	= 15;

	#######################################################################
	# attributes
	#######################################################################
	/**
	 * List of all errors occured
	 *
	 * @var	array
	 */
	protected $_errorList	= array();

	/**
	 * List of all warnings occured
	 *
	 * @var	array
	 */
	protected $_warningList	= array();

	 /**
	 * List of all notices occured
	 *
	 * @var	array
	 */
	protected $_noticeList	= array();

	/**
	 * List of all messages occured
	 *
	 * @var	array
	 */
	protected $_messageList	= array();


	#######################################################################
	# methods
	#######################################################################

	/**
	 * Add message
	 *
	 * HINT: duplicate messages will be overwriten
	 *
	 * @param 	string|array		$message		Message text
	 */
	public function addMessage( $message ) {
		if( !empty( $message ) ) {
			if( is_array( $message ) ) {
				foreach( $message as $text ) {
					$this->addMessage( $text );
				}
			} else {
				// only add unique messages
				$this->_messageList[md5($message)]	= $message;
			} // if
		} // if
	}

	/**
	 * Add notice
	 *
	 * HINT: duplicate messages will be overwriten
	 *
	 * @param 	string|array		$notice			Notice text
	 */
	public function addNotice( $notice ) {
		if( !empty( $notice ) ) {
			if( is_array( $notice ) ) {
				foreach( $notice as $text ) {
					$this->addNotice( $text );
				}
			} else {
				// only add unique messages
				$this->_noticeList[md5($notice)]	= $notice;
			} // if
		} // if
	}

	/**
	 * Add warning
	 *
	 * HINT: duplicate messages will be overwriten
	 *
	 * @param 	string|array		$warning		Warning text
	 */
	public function addWarning( $warning ) {
		if( !empty( $warning ) ) {
			if( is_array( $warning ) ) {
				foreach( $warning as $text ) {
					$this->addWarning( $text );
				}
			} else {
				// only add unique messages
				$this->_warningList[md5($warning)]	= $warning;
			} // if
		} // if
	}

	/**
	 * Add error
	 *
	 * HINT: duplicate messages will be overwriten
	 *
	 * @param 	string|array		$error			Error text
	 */
	public function addError( $error ) {
		if( !empty( $error ) ) {
			if( is_array( $error ) ) {
				foreach( $error as $text ) {
					$this->addError( $text );
				}
			} else {
				// only add unique messages
				$this->_errorList[md5($error)]	= $error;
			} // if
		} // if
	}

	/**
	 * Returns an array of the given messages with the following keys
	 * - error
	 * - warning
	 * - notice
	 * - message
	 *
	 * @param	int|null	$messageTypes	Which messages to render (use Message::* bitflags)
	 * @param 	boolean		$cleanup		Clean up all messages after output
	 * @return	array
	 */
	public function get( $messageTypes = self::ALL, $cleanup = true ) {
		$items	= array();

		if (!is_int($messageTypes)) {
			$messageTypes	= Message::ALL;
		}

		if ($messageTypes && self::ERROR) {
			$items['error']	= $this->errors($cleanup);
		}
		if ($messageTypes && self::WARNING) {
			$items['warning']	= $this->warnings($cleanup);
		}
		if ($messageTypes && self::NOTICE) {
			$items['notice']	= $this->notices($cleanup);
		}
		if ($messageTypes && self::MESSAGE) {
			$items['message']	= $this->messages($cleanup);
		}
		return $items;
	}

	/**
	 * Returns an array of all messages with the following keys
	 * - errors
	 * - warnings
	 * - notices
	 * - messages
	 *
	 * @param 	boolean		$cleanup		Clean up all messages after output
	 * @return	array
	 */
	public function all( $cleanup = true ) {
		return $this->get(self::ALL, $cleanup);
	}

	/**
	 * Return an array of all messages
	 *
	 * @param 	boolean		$cleanup		Clean up all messages after output
	 * @return 	array						Messages as an array
	 */
	public function messages( $cleanup = true ) {
		$messages	= array();
		if( $this->hasMessages() ) {
			$messages	= $this->_messageList;
		}
		if ( $cleanup ) {
			$this->clearMessages();
		}
		return $messages;
	}

	/**
	 * Return an array of all notices
	 *
	 * @param 	boolean		$cleanup		Clean up all notices after output
	 * @return 	array						Notices as an array
	 */
	public function notices( $cleanup = true ) {
		$notices	= array();
		if( $this->hasNotices() > 0 ) {
			$notices	= $this->_noticeList;
		}
		if ( $cleanup ) {
			$this->clearNotices();
		}
		return $notices;
	}

	/**
	 * Return an array of all warnings
	 *
	 * @param 	boolean		$cleanup		Clean up all warnings after output
	 * @return 	array						Warnings as an array
	 */
	public function warnings( $cleanup = true ) {
		$warnings	= array();
		if( $this->hasWarnings() > 0 ) {
			$warnings	= $this->_warningList;
		}
		if ( $cleanup ) {
			$this->clearWarnings();
		}
		return $warnings;
	}

	/**
	 * Return an array of all errors
	 *
	 * @param 	boolean		$cleanup		Clean up all errors after output
	 * @return 	array						Errors as an array
	 */
	public function errors( $cleanup = true ) {
		$errors	= array();
		if( $this->hasErrors() ) {
			$errors	= $this->_errorList;
		}
		if ( $cleanup ) {
			$this->clearErrors();
		}
		return $errors;
	}

	/**
	 * Removes all messages from list
	 */
	public function clearMessages() {
		$this->_messageList	= array();
	}

	/**
	 * Removes all notices from list
	 */
	public function clearNotices() {
		$this->_noticeList	= array();
	}

	/**
	 * Removes all warnings from list
	 */
	public function clearWarnings() {
		$this->_warningList	= array();
	}

	/**
	 * Removes all errors from list
	 */
	public function clearErrors() {
		$this->_errorList	= array();
	}

	/**
	 * Removes all lists
	 */
	public function clear() {
		$this->clearMessages();
		$this->clearNotices();
		$this->clearWarnings();
		$this->clearErrors();
	}


	/**
	 * Method to check for existing messages
	 *
	 * @return	bool				TRUE on existing messages, otherwise FALSE
	 **/
	public function hasMessages() {
		return (bool)count($this->_messageList);
	}


	/**
	 * Method to check for existing notices
	 *
	 * @return	bool				TRUE on existing notices, otherwise FALSE
	 **/
	public function hasNotices() {
		return (bool)count($this->_noticeList);
	}


	/**
	 * Method to check for existing warnings
	 *
	 * @return	bool				TRUE on existing warnings, otherwise FALSE
	 **/
	public function hasWarnings() {
		return (bool)count($this->_warningList);
	}


	/**
	 * Method to check for existing errors
	 *
	 * @return	bool				TRUE on existing errors, otherwise FALSE
	 **/
	public function hasErrors() {
		return (bool)count($this->_errorList);
	}


}
?>