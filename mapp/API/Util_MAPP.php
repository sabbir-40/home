<?php
	if ( defined( 'Util_MAPP' ) ) { return ; }
	define( 'Util_MAPP', true ) ;

	FUNCTION Util_MAPP_Publish( $opid, $push_type, $platform, $arn, $message )
	{
		if ( $arn == "no_arn" ) { return true ; }
		global $CONF ; global $VALS ; global $KEY ; global $VARS_SET_VERIFYPEER ;

		if ( ( isset( $CONF["MAPP_KEY"] ) && $CONF["MAPP_KEY"] ) && ( ( $platform >= 1 ) && ( $platform <= 4 ) ) && $opid && $arn && $message )
		{
			$op_sounds = ( isset( $VALS["op_sounds"] ) && $VALS["op_sounds"] ) ? unserialize( $VALS["op_sounds"] ) : Array() ;
			if ( isset( $op_sounds[$opid] ) ) { $op_sounds_vals = $op_sounds[$opid] ; }
			else { $op_sounds_vals = Array( "default", "default" ) ; }
			if ( $push_type == "new_request" ) { $sound = "new_request_".$op_sounds_vals[0] ; }
			else if ( $push_type == "new_text" ) { $sound = "new_text_".$op_sounds_vals[1] ; }
			else { $sound = "" ; }

			// new sound file to fix rare sound file issue pre v.4.7.9.1
			if ( $sound == "new_text_return" ) { $sound = "new_text_return_android" ; }

			$message = preg_replace( "/'/", "", strip_tags( $message ) ) ;
			if ( function_exists( "mb_strlen" ) && function_exists( "mb_substr" ) )
				$message = ( mb_strlen( $message, 'UTF-8' ) > 75 ) ? mb_substr( $message, 0, 75, 'UTF-8' ) . "..." : $message ;
			else if ( function_exists( "mb_substr" ) )
				$message = ( strlen( $message ) > 75 ) ? mb_substr( $message, 0, 75, 'UTF-8' ) . "..." : $message ;
			else
				$message = ( strlen( $message ) > 75 ) ? substr( $message, 0, 75 ) . "..." : $message ;
			$request = curl_init( "https://mapp1.phplivesupport.com/Util/mapp_process.php" ) ;
			//curl_setopt( $request, CURLOPT_PROXY, "http://url.com" ) ;
			//curl_setopt( $request, CURLOPT_PROXYPORT, "80" ) ;
			curl_setopt( $request, CURLOPT_RETURNTRANSFER, true ) ;
			curl_setopt( $request, CURLOPT_CUSTOMREQUEST, "POST" ) ;
			curl_setopt( $request, CURLOPT_POSTFIELDS, array( "a"=>"$arn", "m"=>"$message", "p"=>"$platform", "s"=>"$sound", "k"=>"$CONF[MAPP_KEY]", "ck"=>"$KEY" ) ) ;
			if ( !isset( $VARS_SET_VERIFYPEER ) || ( $VARS_SET_VERIFYPEER == 1 ) )
			{
				curl_setopt( $request, CURLOPT_SSL_VERIFYPEER, true ) ;
				curl_setopt( $request, CURLOPT_CAINFO, "$CONF[DOCUMENT_ROOT]/mapp/API/cacert.pem" ) ;
			}
			else { curl_setopt( $request, CURLOPT_SSL_VERIFYPEER, false ) ; }
			$response = curl_exec( $request ) ;
			$curl_errno = curl_errno( $request ) ;
			$status = curl_getinfo( $request, CURLINFO_HTTP_CODE ) ; 
			curl_close( $request ) ;
			if ( $response == 1 ) { return true ; }
		} return false ;
	}
?>