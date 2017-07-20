<?php

// flameslater (c) 2017 Bob Maple (bobm at burner dot com)
//
// Licensed under the Creative Commons Attribution-ShareAlike (CC BY-SA)
// https://creativecommons.org/licenses/by-sa/4.0/

// class-ttgObject
//
// Does rudimentary handling of Flame text setups (.ttg) in a template
// context using a %Field Name% search and replace syntax


class ttgObject {
	
	var $ttgData;		// Array of the whole .ttg file
	var $ttgFields;		// Array of field names found in the template


	function ttgObject( $ttg_file = FALSE ) {

		$ttgFields = array();

		if( $ttg_file )
			$this->Load( $ttg_file );
	}

	//

	function Load( $ttg_file ) {

		if( $this->ttgData = file( $ttg_file, FILE_IGNORE_NEW_LINES ) )
			return( TRUE );
		else
			return( FALSE );
	}

	function FindFields() {
	// Scan the ttg for %Fields% and generate an array for each one containing
	// the array index in the template for the field

		foreach( $this->ttgData as $curKey => $curVal ) {

			if( preg_match( "/^Text.* 37 (.*) 37( |$)/", $curVal, $matches ) ) {

				$field_name = $this->ASCIItoSTR( $matches[1] );
				$this->ttgFields[$field_name]['line'] = $curKey;
			}
		}

		if( count( $this->ttgFields ) )
			return( TRUE );
		else
			return( FALSE );
	}

	function GetTTG() {
	// Return a multiline string of the complete .ttg

		return( implode( "\n", $this->ttgData ) );
	}

	function ReplaceField( $ttg_field, $string ) {
	// Replace the $ttg_field in the template with $string

		$ttg_key = $this->ttgFields[$ttg_field]['line'];

		$this->ttgData[$ttg_key]   = "Text " . $this->UTFtoDEC( $string, $codecount );
		$this->ttgData[$ttg_key-1] = "TextLength " . $codecount;
	}

	function ASCIItoSTR( $ascii ) {
	// Decode a string of space-separated ASCII codes into a normal string
	// Not UTF8-aware - field names should be kept simple for now

		$codes  = explode( " ", trim( $ascii ) );
		$string = "";

		foreach( $codes as $curcode ) {
			$string .= chr( $curcode );
		}

		return( $string );
	}

	function UTFtoDEC( $string, &$codecount ) {
	// Decode a (possibly) UTF8-encoded string to a string of space-separated decimal codes

		$utfcodes = array();

		for( $i = 0; $i < strlen( $string ); ) {

			$utfbytes = 1;

				 if( ($string[$i] & "\xE0") === "\xC0" ) $utfbytes = 2;
			else if( ($string[$i] & "\xF0") === "\xE0" ) $utfbytes = 3;
			else if( ($string[$i] & "\xF8") === "\xF0" ) $utfbytes = 4;

			switch( $utfbytes ) {

				case 1:	$utfcodes[] = ord( $string[$i] );
						$i++;
						break;

				case 2:	$utfcodes[] = (( ord( $string[$i] ) & 0x1F) << 6) | (ord( $string[$i+1] ) & 0x3F);
						$i += 2;
						break;

				case 3:	$utfcodes[] = ((ord( $string[$i] ) & 0x0F) << 12) | ((ord( $string[$i+1] ) & 0x3F) << 6) | (ord( $string[$i+2] ) & 0x3F);
						$i += 3;
						break;

				case 3: $utfcodes[] = ((ord( $chr[0] ) & 0x07) << 18) | ((ord( $chr[1] ) & 0x3F) << 12) | ((ord( $chr[2] ) & 0x3F) << 6) | (ord( $chr[3] ) & 0x3F);
						$i += 4;
			}
		}

		$codecount = count( $utfcodes );
		return( implode( " ", $utfcodes ) );
	}

/*	function STRtoFlameText( $string ) {
	// Takes a normal string and returns a multiline string of Kern, TextLength and Text commands
	// to create the string in a Flame text setup file. Applies kerning pairs from kernpairs.php

		global $kernpairs;

		$curkern = 99;
		$curline = "";
		$flametext = array();

		//$flametext[] = "Kern 0";

		debugstr( "--- Making Flame string for [$string]" );

		for( $i = 0; $i < strlen( $string ); $i++ ) {

			// See if this character needs a kern tweak

			if( isset( $kernpairs[$string{$i}][$string{($i+1)}] ) ) {
				$kernval = $kernpairs[$string{$i}][$string{$i+1}];
				debugstr( "Found kerning pair for " . $string{$i} . " and " . $string{$i+1} . " of $kernval" );
			}
			else {

				// This character has no kerning pair def, so
				// we reset to 0

				debugstr( "Resetting kernval to 0" );
				$kernval = 0;
			}

			if( $curkern != $kernval ) {

				// Output what we have so far, if anything

				if( strlen( $curline ) ) {

					debugstr( "Flushing buffer" );

					$flametext[] = "TextLength " . strlen( $curline );
					$flametext[] = "Text " . strtoascii( $curline );
					$curline = "";
				}

				debugstr( "Creating Kern line of $kernval" );

				$curkern = $kernval;
				$flametext[] = "Kern " . $kernval;
			}

			// Add character to the buffer
			$curline .= $string[$i];

			debugstr( "Adding [" . $string[$i] . "] to buffer" );
			debugstr( "  curline is now [$curline]" );
		}

		if( strlen( $curline ) ) {

			debugstr( "Flushing buffer final" );
			$flametext[] = "TextLength " . strlen( $curline );
			$flametext[] = "Text " . strtoascii( $curline );
		}

		return( implode( "\n", $flametext ) );
	} */

}
