<?php
	/***************************************/
	//
	//
	// PHP Live! Support
	//
	// https://www.phplivesupport.com
	//
	/***************************************/
	include_once( "./web/config.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Error.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Format.php" ) ;
	$embed = Util_Format_Sanatize( Util_Format_GetVar( "embed" ), "n" ) ; // up here for error purposes
	$page_origin = Util_Format_Sanatize( rawurldecode( Util_Format_GetVar( "pgo" ) ), "url" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_IP.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Email.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/".Util_Format_Sanatize($CONF["SQLTYPE"], "ln") ) ;
	if ( is_file( "$CONF[DOCUMENT_ROOT]/API/Util_Extra_Pre.php" ) ) { include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Upload_.php" ) ; }
	else { include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Upload.php" ) ; }
	include_once( "$CONF[DOCUMENT_ROOT]/API/Depts/get.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/get.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/update.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Lang/get.php" ) ;
	use GeoIp2\Database\Reader ;

	$action = Util_Format_Sanatize( Util_Format_GetVar( "action" ), "ln" ) ;
	$deptid = Util_Format_Sanatize( Util_Format_GetVar( "deptid" ), "n" ) ;
	$gid = Util_Format_Sanatize( Util_Format_GetVar( "gid" ), "n" ) ;
	$theme = Util_Format_Sanatize( Util_Format_GetVar( "theme" ), "ln" ) ;
	$opid = Util_Format_Sanatize( Util_Format_GetVar( "opid" ), "n" ) ;
	$ces = Util_Format_Sanatize( Util_Format_GetVar( "ces" ), "lns" ) ;
	$cp = Util_Format_Sanatize( Util_Format_GetVar( "cp" ), "n" ) ;
	$chat = Util_Format_Sanatize( Util_Format_GetVar( "chat" ), "n" ) ;
	$disconnect_click = Util_Format_Sanatize( Util_Format_GetVar( "disconnect_click" ), "n" ) ;
	$vname = Util_Format_Sanatize( Util_Format_Sanatize( Util_Format_GetVar( "vname" ), "v" ), "ln" ) ; if ( preg_replace( "/ /", "", $vname ) == "" ) { $vname = "Visitor" ; }
	$vemail = Util_Format_Sanatize( Util_Format_GetVar( "vemail" ), "e" ) ;
	$vsubject = Util_Format_Sanatize( Util_Format_GetVar( "vsubject" ), "" ) ;
	$vquestion = Util_Format_Sanatize( Util_Format_GetVar( "vquestion" ), "" ) ;
	$onpage = Util_Format_Sanatize( Util_Format_GetVar( "onpage" ), "url" ) ; $onpage = ( $onpage ) ? $onpage : "" ;
	$custom = Util_Format_Sanatize( Util_Format_GetVar( "custom" ), "htmltags" ) ;
	$token = Util_Format_Sanatize( Util_Format_GetVar( "token" ), "ln" ) ;
	$vclick = Util_Format_Sanatize( Util_Format_GetVar( "vclick" ), "n" ) ;
	$emarketid = Util_Format_Sanatize( Util_Format_GetVar( "emarketid" ), "n" ) ;
	$dept_themes = ( isset( $VALS["THEMES"] ) && $VALS["THEMES"] ) ? unserialize( $VALS["THEMES"] ) : Array() ;
	if ( !$theme && isset( $dept_themes[$deptid] ) && $deptid ) { $theme = $dept_themes[$deptid] ; }
	else if ( !$theme ) { $theme = $CONF["THEME"] ; }
	else if ( $theme && !is_file( "$CONF[DOCUMENT_ROOT]/themes/$theme/style.css" ) ) { $theme = $CONF["THEME"] ; }
	if ( !is_file( "$CONF[DOCUMENT_ROOT]/themes/$theme/style.css" ) || isset( $THEMES_EXCLUDE[$theme] ) ) { $theme = "default" ; }

	$agent = isset( $_SERVER["HTTP_USER_AGENT"] ) ? $_SERVER["HTTP_USER_AGENT"] : "&nbsp;" ;
	LIST( $ip, $vis_token ) = Util_IP_GetIP( $token ) ;
	LIST( $os, $browser ) = Util_Format_GetOS( $agent ) ;
	$mobile = ( $os == 5 ) ? 1 : 0 ; $error = "" ;

	if ( is_file( "$CONF[CONF_ROOT]/addons.php" ) ) { include_once( "$CONF[CONF_ROOT]/addons.php" ) ; }

	$addon_phplivebot = is_file( "$CONF[DOCUMENT_ROOT]/addons/phplivebot/phplivebot.php" ) ? 1 : 0 ;
	$addon_marquee = is_file( "$CONF[DOCUMENT_ROOT]/addons/marquee/marquee.php" ) ? 1 : 0 ;

	if ( preg_match( "/$ip/", $VALS["CHAT_SPAM_IPS"] ) ) { $spam_exist = 1 ; }
	else { $spam_exist = 0 ; }

	$departments = Depts_get_AllDepts( $dbh ) ; // needed for logo hash check
	$deptinfo = Depts_get_DeptInfo( $dbh, $deptid ) ;
	$deptvars = Depts_get_DeptVars( $dbh, $deptid ) ;

	$emlogos_hash = ( isset( $VALS["EMLOGOS"] ) && $VALS["EMLOGOS"] ) ? unserialize( $VALS["EMLOGOS"] ) : Array() ;
	if ( !isset( $deptinfo["deptID"] ) )
	{
		$query = isset( $_SERVER["QUERY_STRING"] ) ? Util_Format_Sanatize( $_SERVER["QUERY_STRING"], "query" ) : "" ;
		$query = preg_replace( "/^d=(\d+)&/", "d=0&", $query ) ;
		database_mysql_close( $dbh ) ;
		HEADER( "location: phplive.php?$query&" ) ; exit ;
	}

	if ( $deptinfo["smtp"] )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Functions_itr.php" ) ;
		$smtp_array = unserialize( Util_Functions_itr_Decrypt( $CONF["SALT"], $deptinfo["smtp"] ) ) ;
	}

	if ( $deptinfo["lang"] )
		$CONF["lang"] = $deptinfo["lang"] ;
	include_once( "$CONF[DOCUMENT_ROOT]/lang_packs/".Util_Format_Sanatize($CONF["lang"], "ln").".php" ) ;

	$requestinfo = Chat_get_RequestHistCesInfo( $dbh, $ces ) ;
	$custom_vars = ( isset( $requestinfo["custom"] ) && $requestinfo["custom"] ) ? $requestinfo["custom"] : "" ;
	// process screenshot here because it adds file URL as custom variable
	if ( is_file( "$CONF[DOCUMENT_ROOT]/addons/screenshot/inc_m.php" ) ) { include_once( "$CONF[DOCUMENT_ROOT]/addons/screenshot/inc_m.php" ) ; }
	if ( $custom )
	{
		// prepsend dup check because they are the most recent for leave a message
		$custom_vars = $custom."-cus-".$custom_vars ;

		$custom_hash = Array() ;
		$customs = explode( "-cus-", $custom_vars ) ;
		for ( $c = 0; $c < count( $customs ); ++$c )
		{
			$custom_var = $customs[$c] ;
			if ( $custom_var && preg_match( "/-_-/", $custom_var ) )
			{
				LIST( $custom_var_name, $custom_var_val ) = explode( "-_-", $custom_var ) ;
				if ( $custom_var_val && ( !isset( $custom_hash[$custom_var_name] ) || !$custom_hash[$custom_var_name] ) )
					$custom_hash[$custom_var_name] = $custom_var_val ;
			}
		}
		$custom_vars = "" ;
		foreach ( $custom_hash as $custom_var_name => $custom_var_val )
			$custom_vars .= "$custom_var_name-_-$custom_var_val-cus-" ;
	}

	if ( $action === "send_email" )
	{
		$trans = Util_Format_Sanatize( Util_Format_GetVar( "trans" ), "n" ) ;
		$extra = ( $trans ) ? "trans" : "offline" ;

		if ( !$vsubject ) { $vsubject = $LANG["CHAT_JS_LEAVE_MSG"] ; }
		if ( $trans )
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/get.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/get_ext.php" ) ;

			$transcript = Chat_ext_get_Transcript( $dbh, $ces ) ;
			$opinfo = Ops_get_OpInfoByID( $dbh, $transcript["opID"] ) ;

			// override for emailing transcript
			if ( ( isset( $deptvars["trans_f_dept"] ) && $deptvars["trans_f_dept"] ) || ( !isset( $opinfo["opID"] ) || ( isset( $opinfo["opID"] ) && ( $opinfo["login"] == "phplivebot" ) ) ) )
			{
				$from_name = $deptinfo["name"] ;
				$from_email = $deptinfo["email"] ;
			}
			else
			{
				$from_name = $opinfo["name"] ;
				$from_email = $opinfo["email"] ;
			}

			$to_name = $transcript["vname"] ;
			$to_email = $vemail ;
			$message = Util_Email_FormatTranscript( $ces, $vquestion, $deptinfo["name"], $deptinfo["email"], $requestinfo["vname"], $requestinfo["vemail"], $opinfo["name"], $opinfo["email"], $custom_vars, $transcript["formatted"] ) ;
		}
		else
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/IPs/get.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Messages/get.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Messages/put.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Footprints/get_ext.php" ) ;

			$ipinfo = IPs_get_IPInfo( $dbh, $vis_token, $ip ) ;
			$referinfo = Footprints_get_IPRefer( $dbh, $vis_token ) ;
			$t_footprints = isset( $ipinfo["t_footprints"] ) ? $ipinfo["t_footprints"] : 1 ;
			$refer_url = ( isset( $referinfo["refer"] ) && $referinfo["refer"] ) ? $referinfo["refer"] : "" ;
			$prev_message_info = ( isset( $ipinfo["t_footprints"] ) ) ? Messages_get_MessageByMd5( $dbh, $vis_token ) : false ;
			if ( !isset( $prev_message_info["created"] ) ) { $prev_message_info = Messages_get_MessageByIP( $dbh, $ip ) ; }

			if ( isset( $prev_message_info["created"] ) && ( time() < ( $prev_message_info["created"] + (60*$VARS_MAIL_SEND_BUFFER) ) ) )
				$error = $LANG["MSG_PROCESSING"] ;
			else if ( !$error )
			{
				if ( $VARS_ADDON_EMARKET_ENABLED && is_file( "$CONF[DOCUMENT_ROOT]/addons/emarketing/emarketing.php" ) )
				{
					include_once( "$CONF[DOCUMENT_ROOT]/addons/emarketing/inc_save_response.php" ) ;
				}
				if ( $deptinfo["savem"] )
				{
					include_once( "$CONF[DOCUMENT_ROOT]/API/Messages/remove.php" ) ;
					Messages_remove_LastMessages( $dbh, $deptinfo["deptID"], $deptinfo["savem"] ) ;
				}
				Messages_put_Message( $dbh, $vis_token, $deptid, $chat, $t_footprints, $ip, $ces, $vname, $vemail, $vsubject, $onpage, $refer_url, $custom_vars, $vquestion ) ;
				if ( $chat )
				{
					if ( $vclick ) { Chat_update_RequestLogValue( $dbh, $ces, "status_msg", 4 ) ; }
					else { Chat_update_RequestLogValue( $dbh, $ces, "status_msg", 2 ) ; }
					Chat_update_RequestLogValue( $dbh, $ces, "custom", $custom_vars ) ;
				}

				$custom_string = "" ;
				$customs = explode( "-cus-", $custom_vars ) ;
				for ( $c = 0; $c < count( $customs ); ++$c )
				{
					$custom_var = $customs[$c] ;
					if ( $custom_var && preg_match( "/-_-/", $custom_var ) )
					{
						LIST( $cus_name, $cus_var ) = explode( "-_-", $custom_var ) ;
						if ( $cus_var && ( $cus_name != "ProAction ID" ) ) { $custom_string .= $cus_name.": ".$cus_var."\r\n" ; }
					}
				}

				$from_name = $vname ;
				$from_email = $vemail ;

				$to_name = $deptinfo["name"] ;
				$to_email = $deptinfo["email"] ;

				include_once( "$CONF[DOCUMENT_ROOT]/examples/inc_default_vars.php" ) ;
				$template_subject = $DEFAULT_VAR_OFFLINE_TEMPLATE_SUBJECT ;
				$template_body = $DEFAULT_VAR_OFFLINE_TEMPLATE_BODY ;
				if ( isset( $deptvars["offline_msg_template"] ) && preg_match( "/-_-/", $deptvars["offline_msg_template"] ) )
				{	
					LIST( $template_subject, $template_body ) = explode( "-_-", $deptvars["offline_msg_template"] ) ;
				}

				$vquestion = preg_replace( "/\\$/", "-dollar-", $vquestion ) ;
				$vsubject = preg_replace( "/\\$/", "-dollar-", $vsubject ) ;

				$vsubject = preg_replace( "/%%visitor_subject%%/i", $vsubject, $template_subject ) ;
				$vsubject = preg_replace( "/%%department_name%%/i", $to_name, $vsubject ) ;
				$vsubject = preg_replace( "/%%visitor_message%%/i", $vquestion, $vsubject ) ;
				$vsubject = preg_replace( "/%%custom_variables%%/i", $custom_string, $vsubject ) ;
				$vsubject = preg_replace( "/%%visitor%%/i", $vname, $vsubject ) ;
				$vsubject = preg_replace( "/%%visitor_email%%/i", $vemail, $vsubject ) ;
				$vsubject = preg_replace( "/%%stat_total_footprints%%/i", $t_footprints, $vsubject ) ;
				$vsubject = preg_replace( "/%%stat_ip%%/i", $ip, $vsubject ) ;
				$vsubject = preg_replace( "/%%stat_visitor_id%%/i", $vis_token, $vsubject ) ;
				$vsubject = preg_replace( "/%%stat_onpage_url%%/i", $onpage, $vsubject ) ;

				$message = preg_replace( "/%%visitor_subject%%/i", $vsubject, $template_body ) ;
				$message = preg_replace( "/%%department_name%%/i", $to_name, $message ) ;
				$message = preg_replace( "/%%visitor_message%%/i", $vquestion, $message ) ;
				$message = preg_replace( "/%%custom_variables%%/i", $custom_string, $message ) ;
				$message = preg_replace( "/%%visitor%%/i", $vname, $message ) ;
				$message = preg_replace( "/%%visitor_email%%/i", $vemail, $message ) ;
				$message = preg_replace( "/%%stat_total_footprints%%/i", $t_footprints, $message ) ;
				$message = preg_replace( "/%%stat_ip%%/i", $ip, $message ) ;
				$message = preg_replace( "/%%stat_visitor_id%%/i", $vis_token, $message ) ;
				$message = preg_replace( "/%%stat_onpage_url%%/i", $onpage, $message ) ;
			}
		}
		$custom_processed = 0 ; // indication if custom code was processed
		if ( !$error )
		{
			$message = preg_replace( "/&lt;/", "<", $message ) ; $message = preg_replace( "/&gt;/", ">", $message ) ;
			if ( !$spam_exist )
			{
				// place custom code option before sending for variable overwrite situations
				// $trans flag indicates if it is a transcript email (visitor sending the chat transcript or operator sending the chat transcript)
				if ( is_file( "$CONF[DOCUMENT_ROOT]/custom_code/offline_email_prep.php" ) && !$trans )
				{
					include_once( "$CONF[DOCUMENT_ROOT]/custom_code/offline_email_prep.php" ) ;
				}

				//
				// if custom code exists, intercept email sending and run custom code
				//		* set the $process_system_send_email = false ; INSIDE THE CUSTOM CODE for not sending out email after processing custom code
				//
				$process_system_send_email = true ;
				//
				if ( is_file( "$CONF[DOCUMENT_ROOT]/custom_code/offline_email_send.php" ) && !$trans )
				{
					include_once( "$CONF[DOCUMENT_ROOT]/custom_code/offline_email_send.php" ) ;
				}
				if ( $process_system_send_email ) { $error = Util_Email_SendEmail( $from_name, $from_email, $to_name, $to_email, $vsubject, $message, $extra ) ; }
			} else { $error = "" ; }
			if ( !$error && !$cp && $deptinfo["emailm_cc"] && !$spam_exist )
			{
				Util_Email_SendEmail( $from_name, $from_email, $to_name, $deptinfo["emailm_cc"], $vsubject, $message, $extra ) ;
			}
			if ( !$error && !$cp && isset( $deptvars["offline_auto_reply"] ) && preg_match( "/-_-/", $deptvars["offline_auto_reply"] ) && is_file( "$CONF[DOCUMENT_ROOT]/addons/auto_reply/inc_email.php" ) ) { include_once( "$CONF[DOCUMENT_ROOT]/addons/auto_reply/inc_email.php" ) ; }
		}

		if ( !$error )
			$json_data = "json_data = { \"status\": 1, \"custom\": $custom_processed };" ;
		else
			$json_data = "json_data = { \"status\": 0, \"error\": \"$error\" };" ;

		if ( isset( $dbh ) && isset( $dbh['con'] ) ) { database_mysql_close( $dbh ) ; }
		HEADER('Content-Type: text/plain; charset=utf-8') ;
		print $json_data ; exit ;
	}
	else if ( $action === "send_email_trans" )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/put_itr.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/Util.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/get_ext.php" ) ;
		$download = Util_Format_Sanatize( Util_Format_GetVar( "download" ), "n" ) ;

		$output = UtilChat_ExportChat( "{$ces}.txt" ) ;
		if ( !is_array( $output ) || !isset( $output[1][0] ) ) { $transcript_info = Chat_ext_get_Transcript( $dbh, $ces ) ; }

		if ( ( is_array( $output ) && isset( $output[1][0] ) ) || isset( $transcript_info["formatted"] ) )
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/get.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get.php" ) ;

			if ( is_array( $output ) && isset( $output[1][0] ) ) { $transcript = $output[1][0] ; }
			else { $transcript = $transcript_info["formatted"] ; }

			$opinfo = Ops_get_OpInfoByID( $dbh, $opid ) ;
			$deptinfo = Depts_get_DeptInfo( $dbh, $deptid ) ;

			$subject_visitor = $LANG["TRANSCRIPT_SUBJECT"]." $opinfo[name]" ;
			$message = Util_Email_FormatTranscript( $ces, $deptinfo["msg_email"], $deptinfo["name"], $deptinfo["email"], $vname, $vemail, $opinfo["name"], $opinfo["email"], $requestinfo["custom"], $transcript ) ;

			if ( !$download )
			{
				if ( isset( $deptvars["trans_f_dept"] ) && $deptvars["trans_f_dept"] )
				{
					$from_name = $deptinfo["name"] ;
					$from_email = $deptinfo["email"] ;
				}
				else
				{
					$from_name = $opinfo["name"] ;
					$from_email = $opinfo["email"] ;
				}
				$error = Util_Email_SendEmail( $from_name, $from_email, $vname, $vemail, $subject_visitor, $message, "trans" ) ;

				if ( !$error )
					$json_data = "json_data = { \"status\": 1 };" ;
				else
					$json_data = "json_data = { \"status\": 0, \"error\": \"$error\" };" ;
			}
			else
			{
				if ( isset( $dbh ) && isset( $dbh['con'] ) ) { database_mysql_close( $dbh ) ; }
				HEADER( "Content-Type: text/plain" ) ;
				// final strip tags for possible custom HTML code in template
				print strip_tags( $message ) ; exit ;
			}
		}
		else
			$json_data = "json_data = { \"status\": 0, \"error\": \"Could not locate chat session file.\" };" ;

		if ( isset( $dbh ) && isset( $dbh['con'] ) ) { database_mysql_close( $dbh ) ; }
		HEADER('Content-Type: text/plain; charset=utf-8') ;
		print $json_data ; exit ;
	}
	else
	{
		// on stats db the leave a message is not op specific, just use the current opID to
		// track requests that went to leave a messge
		if ( $ces && $deptid )
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/put_itr.php" ) ;

			if ( isset( $requestinfo["status_msg"] ) && !$requestinfo["status_msg"] && !$requestinfo["status"] && !$requestinfo["op2op"] && ( $vclick != 2 ) )
			{
				if ( $vclick ) { Chat_update_RequestLogValue( $dbh, $ces, "status_msg", 3 ) ; }
				else { Chat_update_RequestLogValue( $dbh, $ces, "status_msg", 1 ) ; }
				Ops_put_itr_OpReqStat( $dbh, $deptid, 0, "message", 1 ) ;
			}
			if ( isset( $requestinfo["status"] ) && !$requestinfo["status"] && !$requestinfo["accepted"] && !$requestinfo["initiated"] && is_file( "$CONF[CHAT_IO_DIR]/{$ces}.txt" ) ) { @unlink( "$CONF[CHAT_IO_DIR]/{$ces}.txt" ) ; }
		}
	}
	include_once( "$CONF[DOCUMENT_ROOT]/API/Queue/update.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Queue/remove.php" ) ;

	Queue_update_QueueLogValueByCes( $dbh, $ces, "status", -1 ) ;
	Queue_remove_Queue( $dbh, $ces ) ;
	if ( is_file( "$CONF[TYPE_IO_DIR]/{$ces}.txt" ) ) { @unlink( "$CONF[TYPE_IO_DIR]/{$ces}.txt" ) ; }

	$json_data = "json_data = { \"status\": 1 };" ;
	if ( isset( $dbh ) && isset( $dbh['con'] ) ) { database_mysql_close( $dbh ) ; }
	usleep( 250000 ) ;
	HEADER('Content-Type: text/plain; charset=utf-8') ;
	print $json_data ; exit ;
?>