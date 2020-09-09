<?php
	/***************************************/
	//
	//
	// PHP Live! Support
	//
	// https://www.phplivesupport.com
	//
	/***************************************/
	include_once( "../../web/config.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Format.php" ) ;

	$action = Util_Format_Sanatize( Util_Format_GetVar( "action" ), "ln" ) ;

	if ( !isset( $_COOKIE["cO"] ) )
	{
		$json_data = "json_data = { \"status\": -1 };" ;
	}
	else
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/".Util_Format_Sanatize($CONF["SQLTYPE"], "ln") ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get.php" ) ;

		$opid_cookie = Util_Format_Sanatize( $_COOKIE["cO"], "n" ) ;
		$opinfo = Ops_get_OpInfoByID( $dbh, $opid_cookie ) ;
		$ses = isset( $_COOKIE["cS"] ) ? Util_Format_Sanatize( $_COOKIE["cS"], "ln" ) : "" ;

		if ( !$ses || !isset( $opinfo["ses"] ) || ( $ses != $opinfo["ses"] ) )
		{
			$json_data = "json_data = { \"status\": -1 };" ;
		}
		else if ( $action === "update_mapp_c" )
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/update.php" ) ;
			$value = Util_Format_Sanatize( Util_Format_GetVar( "value" ), "n" ) ;
			Ops_update_OpVarValue( $dbh, $opid_cookie, "mapp_c", $value ) ;
			$json_data = "json_data = { \"status\": 1 };" ; 
		}
		else { $json_data = "json_data = { \"status\": 0 };" ; }
	}

	if ( isset( $dbh ) && $dbh['con'] ) { database_mysql_close( $dbh ) ; }
	$json_data = Util_Format_Trim( $json_data ) ;
	$json_data = preg_replace( "/\t/", "", $json_data ) ;
	HEADER('Content-Type: text/plain; charset=utf-8') ;
	print $json_data ; exit ;
?>