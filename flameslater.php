#!/usr/bin/php
<?php

// flameslater v1.00 beta (c) 2017 Bob Maple (bobm at burner dot com)
//
// Licensed under the Creative Commons Attribution-ShareAlike (CC BY-SA)
// https://creativecommons.org/licenses/by-sa/4.0/


require_once( "config.php" );
require_once( "class-ttgObject.php" );

error_reporting( E_ERROR | E_PARSE );
date_default_timezone_set( $cfg_timezone );

if( $argc < 2 ) {

	print( "Usage: {$argv[0]} CSVFILE [-t TEMPLATE] [-d] [-n] [-x] [-ds]\n\n" );
	print( "  -t TEMPLATE  Use file TEMPLATE as text setup template;\n" );
	print( "                 defaults to " . dirname( $argv[0] ) . "/templates/slate_template.ttg\n" );
	print( "  -d           Enable debug output\n" );
	print( "  -n           No text setups (implies -x)\n" );
	print( "  -x           Copy spot codes to clipboard with xclip\n" );
	print( "  -ds          Don't strip dashes from spot codes copied to the clipboard\n\n" );

	// Spit out a list of templates

	$templates = scandir( dirname( __FILE__ ) . "/templates" );

	if( $templates ) {

		print( "\nTemplates found:\n\n" );

		foreach( $templates as $curtemplate ) {

			if( preg_match( "/^(.*)\\.ttg$/", $curtemplate, $matches ) )
				print( "  " . $matches[1] . "\n" );
		}
	}

	else die( "Error: no templates found\n" );

	exit( "\n" );
}

// Read the csv or die

if( !($csvfile = file( $argv[1] )) )
	die( "Could not read {$argv[1]}\n" );

$do_xclip = FALSE;
$no_texts = FALSE;
$do_debug = FALSE;
$do_strip = TRUE;

$ttg_template = dirname( __FILE__ ) . "/templates/slate_template.ttg";

// Parse the rest of the args

for( $i = 2; $i < $argc; $i++ ) {

	if( $argv[$i] == "-x" )
		$do_xclip = TRUE;
	if( $argv[$i] == "-ds" )
		$do_strip = FALSE;
	if( $argv[$i] == "-n" ) {
		$do_xclip = TRUE;
		$no_texts = TRUE;
	}
	if( $argv[$i] == "-d" )
		$do_debug = TRUE;
	if( $argv[$i] == "-t" ) {

		if( file_exists( $argv[$i+1] ) ) {
			$ttg_template = $argv[$i+1];
			$i++;
		}
		elseif( file_exists( dirname(__FILE__) . "/templates/" . $argv[$i+1] . ".ttg" ) ) {
			$ttg_template = dirname(__FILE__) . "/templates/" . $argv[$i+1] . ".ttg";
			$i++;
		}
		else die( "Could not find template " . $argv[$i+1] . "\n" );
	}
}

print( "\n" );
print( "Generating from " . $argv[1] . "\n" );
print( "Using template " . $ttg_template . "\n\n" );

// Read the template and find all the fields in it

$ttgObj = new ttgObject( $ttg_template );
$ttgObj->FindFields();

if( !isset( $ttgObj->ttgFields['Spot Code'] ) )
	die( "Error: no %Spot Code% field found in the template." );

// Find longest field name for display padding

$maxLen = 0;
foreach( $ttgObj->ttgFields as $tmpKey => $tmpVal ) {
        if( strlen( $tmpKey ) > $maxLen )
                $maxLen = strlen( $tmpKey );
}

$samprow = str_getcsv( $csvfile[0] );
$numcols = strlen( count($samprow) );

print( "First row of data in " . $argv[1] . ":\n\n" );

for( $i = 0; $i < count($samprow); $i++ )
	print( " [" . str_pad( $i, $numcols, " ", STR_PAD_RIGHT ) . "] " . $samprow[$i] . "\n" );

print( "\nSelect the column number to use for each slate field (enter accepts\ndefault column) or enter a string for static data.\n\n" );

$colIdx = 1;

// Iterate through the fields found in the template and connect them
// with columns of the csv

foreach( $ttgObj->ttgFields as $curKey => $curVal ) {

	// Hotwire the %Date Now% field and fill in with the current date
	if( $curKey == "Date Now" ) {
		$ttgObj->ttgFields['Date Now']['data'] = strftime( $cfg_datestring );
		continue;
	}

	print( str_pad( $curKey, $maxLen ) . " (" . str_pad( $colIdx, $numcols, " ", STR_PAD_RIGHT ) . "): " );

	$resp = trim(fgets(STDIN));
	$resp = strlen($resp) ? $resp : $colIdx;
	
	$ttgObj->ttgFields[$curKey]['data'] = is_numeric( $resp ) ? (int)$resp : $resp;

	if( is_numeric( $resp ) && ($colIdx < count( $samprow )) )
		$colIdx++;
}

// Clear the clipboard

if( $do_xclip == TRUE )
	exec( "/usr/bin/qdbus org.kde.klipper /klipper clearClipboardHistory" );


// Make some slates

foreach( $csvfile as $currow ) {

	$csv_columns = str_getcsv( $currow );
	$tmp         = trim( $csv_columns[0] );

	if( empty( $tmp ) )
		continue;

	print( "\n" );

	// Create our fields

	$spot_code = is_string( $ttgObj->ttgFields['Spot Code']['data'] ) ? $ttgObj->ttgFields['Spot Code']['data'] : $csv_columns[$ttgObj->ttgFields['Spot Code']['data']];

	if( empty( $spot_code ) ) {
		print( "ERROR: Spot code is empty - skipping\n" );
		continue;
	}

	if( !$no_texts ) {

		print( "Creating " . $spot_code . ".ttg\n" );

		foreach( $ttgObj->ttgFields as $curKey => $curVal ) {

			$field_data = is_string( $curVal['data'] ) ? $curVal['data'] : $csv_columns[$curVal['data']];
			$ttgObj->ReplaceField( $curKey, $field_data );
		}

		$ttgfp = fopen( $spot_code . ".ttg", "w" );
	  
		fwrite( $ttgfp, $ttgObj->GetTTG() );
		fclose( $ttgfp );
	}

	print( "   Code: [" . $spot_code . "]\n" );

	// Copy the spot code to the clipboard with xclip

	if( $do_xclip ) {

		$xfp = popen( dirname( __FILE__ ) . "/xclip -selection clipboard -i", "w" );

		if( $xfp ) {

			print ( "Copied to clipboard\n" );

			if( $do_strip )
				fwrite( $xfp, str_replace( "-", "", $spot_code ) );
			else
				fwrite( $xfp, $spot_code );

			pclose( $xfp );
			usleep( 150000 );
		}
		else
			print( "Unable to call xclip\n" );
	}
}

exit( "\n" );


//
// FUNCTIONS
//

function debugstr( $string ) {

	global $do_debug;

	if( $do_debug )
		print( ">> $string\n" );
}
