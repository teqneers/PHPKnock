<?php
/**
 * Standard Functions
 *
 * @author		Oliver Mueller <mueller@teqneers.de>
 * @copyright	Copyright (C) 2003-2012 TEQneers GmbH & Co. KG. All rights reserved.
 * @package		KNOCK PHP
 * @subpackage	base
 * @version		$Revision: 20689 $
 * @internal	$Id: functions.php 20689 2011-08-01 16:00:34Z nico $
 */


/**
 * This method will automatically load classes
 *
 * @param	string	$class		Name of class
 * @return	bool				TRUE on successful inclusion, FALSE otherwise
 */
function autoload( $class ) {
	if( preg_match_all( '/([A-Z][^A-Z]+)/', $class, $path ) ) {
		if( end($path[1]) != 'Interface' ) {
			$type	= 'class';
		} else {
			$type	= 'interface';
			array_pop( $path[1] );
		}

		$i			= count( $path[1] );
		$filename	= '';
		$found		= false;
		do {
			$filename	= empty($filename) ? array_pop($path[1]) : array_pop($path[1]).'_'.$filename;
			$include	= __DIR__.'/classes/'.strtolower(implode( '/', $path[1] ).'/'.$filename).'_'.$type.'.php';
			$found		= file_exists($include);
		} while( !$found && count($path[1]) );

		if( $found ) {
			require $include;
		}

		return $found;

	} // if

	return false;
}


/**
 * Var dump
 *
 * Var_dump replacement
 *
 * @param 	mixed 	$dump		Dump var
 * @param 	string	$name		Dump name
 */
function vd( $dump, $name = null ) {
	if( $GLOBALS['ERRORS_VERBOSE'] ) {
		// TODO: this output should only happen with teqneers internal servers
		if ( empty( $GLOBALS['jsDebugDragNDropHandling'] ) || !is_array( $GLOBALS['jsDebugDragNDropHandling'] ) ) {
			$GLOBALS['jsDebugDragNDropHandling']	= array();
		}
		$uniqueId	= count( $GLOBALS['jsDebugDragNDropHandling'] );
		$GLOBALS['jsDebugDragNDropHandling'][]	= 'jsDebug'.$uniqueId;

		if( $name === null ) {
			$backtrace	= debug_backtrace();
			$tmp 		= explode( DIRECTORY_SEPARATOR, $backtrace[0]['file']);
			$lastDir	= (count($tmp)>1) ? ($tmp[ count($tmp)-2 ] . '/') : '';
			$name		= $lastDir.basename($backtrace[0]['file']).' [line '.$backtrace[0]['line'].'] ';
			$name		.= $uniqueId + 1;
		} else {
			$name		.= ' ['.($uniqueId + 1).']';
		}

		if( !CLI_CALL ) {

			echo '
			<div id="jsDebug'.$uniqueId.'" class="debug">
				<div class="debugTitle">
					<div onClick="javascript:document.getElementById(\'jsDebug'.$uniqueId.'\').style.display=\'none\';" class="debugClose">X</div>
					'.$name.'
				</div>
				<pre>'."\n";
					// @codingStandardsIgnoreStart
					var_dump( $dump );
					// @codingStandardsIgnoreEnd
					echo '
				</pre>
			</div>';

		} else {
			echo "\n############################### ".$name." ###############################\n";
			// @codingStandardsIgnoreStart
			var_dump($dump);
			// @codingStandardsIgnoreEnd
			echo "############################### /".$name." ##############################\n";
		}
	} // if
}

/**
 * Print r
 *
 * Print_r replacement
 *
 * @param 	mixed	$dump		Dump var
 * @param	string	$name		Dump name
 */
function pr( $dump, $name = null ) {
	if( $GLOBALS['ERRORS_VERBOSE'] ) {
		// TODO: this output should only happen with teqneers internal servers
		if ( empty( $GLOBALS['jsDebugDragNDropHandling'] ) || !is_array( $GLOBALS['jsDebugDragNDropHandling'] ) ) {
			$GLOBALS['jsDebugDragNDropHandling']	= array();
		}
		$uniqueId	= count( $GLOBALS['jsDebugDragNDropHandling'] );
		$GLOBALS['jsDebugDragNDropHandling'][]	= 'jsDebug'.$uniqueId;

		if( $name === null ) {
			$backtrace	= debug_backtrace();
			$tmp 		= explode( DIRECTORY_SEPARATOR, $backtrace[0]['file']);
			$lastDir	= (count($tmp)>1) ? ($tmp[ count($tmp)-2 ] . '/') : '';
			$name		= $lastDir.basename($backtrace[0]['file']).' [line '.$backtrace[0]['line'].'] ';
			$name		.= $uniqueId + 1;
		} else {
			$name		.= ' ['.($uniqueId + 1).']';
		}

		if( !CLI_CALL ) {

			echo '
			<div id="jsDebug'.$uniqueId.'" class="debug">
				<div class="debugTitle">
					<div onClick="javascript:document.getElementById(\'jsDebug'.$uniqueId.'\').style.display=\'none\';" class="debugClose">X</div>
					'.$name.'
				</div>
				<pre>'."\n";
					// @codingStandardsIgnoreStart
					print_r( $dump );
					// @codingStandardsIgnoreEnd
					echo '
				</pre>
			</div>';

		} else {
			echo "\n############################### ".$name." ###############################\n";
			// @codingStandardsIgnoreStart
			print_r($dump);
			// @codingStandardsIgnoreEnd
			echo "############################### /".$name." ##############################\n";
		}
	} // if
}
