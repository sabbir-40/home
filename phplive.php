<?php
	/***************************************/
	//
	//
	// PHP Live! Support
	//
	// https://www.phplivesupport.com
	//
	/***************************************/
	if ( !is_file( "./web/config.php" ) ){ HEADER("location: setup/install.php") ; exit ; }
	include_once( "./web/config.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Error.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Format.php" ) ;
	$embed = Util_Format_Sanatize( Util_Format_GetVar( "embed" ), "n" ) ; // up here for error purposes
	$page_origin = Util_Format_Sanatize( rawurldecode( Util_Format_GetVar( "pgo" ) ), "url" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_IP.php" ) ;
	$query = isset( $_SERVER["QUERY_STRING"] ) ? preg_replace( "/&&/", "&", Util_Format_Sanatize( $_SERVER["QUERY_STRING"], "query" ) ) : "" ;
	/* AUTO PATCH */
	if ( !is_file( "$CONF[CONF_ROOT]/patches/$patch_v" ) )
	{
		HEADER( "location: patch.php?from=chat&".$query."&" ) ; exit ;
	}
	if ( is_file( "$CONF[DOCUMENT_ROOT]/API/Util_Extra_Pre.php" ) ) { include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Upload_.php" ) ; }
	else { include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Upload.php" ) ; }
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Functions.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Depts/get.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get_itr.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/update_itr.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/get_itr.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Lang/get.php" ) ;
	if ( is_file( "$CONF[CONF_ROOT]/addons.php" ) ) { include_once( "$CONF[CONF_ROOT]/addons.php" ) ; }
	use GeoIp2\Database\Reader ;

	$onpage = Util_Format_Sanatize( base64_decode( Util_Format_GetVar( "pg" ) ), "url" ) ;
	$title = Util_Format_Sanatize( base64_decode( Util_Format_GetVar( "tl" ) ), "title" ) ;
	if ( !$onpage )
	{
		$onpage = Util_Format_Sanatize( Util_Format_GetVar( "onpage" ), "url" ) ;
		$title = Util_Format_Sanatize( Util_Format_GetVar( "title" ), "title" ) ;
	}
	$deptid = Util_Format_Sanatize( Util_Format_GetVar( "d" ), "n" ) ;
	if ( !$deptid ) { $deptid = Util_Format_Sanatize( Util_Format_GetVar( "deptid" ), "n" ) ; } $deptid_orig = $deptid ;
	$gid = ( $deptid > 100000000 ) ? $deptid : 0 ;
	$opid = Util_Format_Sanatize( Util_Format_GetVar( "opid" ), "n" ) ;
	$theme = Util_Format_Sanatize( Util_Format_GetVar( "theme" ), "ln" ) ;
	$vquestion = rawurldecode( Util_Format_Sanatize( Util_Format_GetVar( "vquestion" ), "htmltags" ) ) ;
	if ( !$vquestion ) { $vquestion = "" ; } // to remove the zero (0) just in case
	$popout = Util_Format_Sanatize( Util_Format_GetVar( "popout" ), "n" ) ;
	$autoinvite = Util_Format_Sanatize( Util_Format_GetVar( "i" ), "n" ) ;
	$js_name = rawurldecode( base64_decode( Util_Format_Sanatize( Util_Format_GetVar( "js_name" ), "b64" ) ) ) ;
	$js_email = rawurldecode( base64_decode( Util_Format_Sanatize( Util_Format_GetVar( "js_email" ), "b64" ) ) ) ;
	$vsubject = Util_Format_ConvertTags( Util_Format_ConvertQuotes( Util_Format_Sanatize( Util_Format_GetVar( "vsubject" ), "" ) ) ) ;
	$custom = Util_Format_Sanatize( Util_Format_GetVar( "custom" ), "htmltags" ) ;
	$lang = Util_Format_Sanatize( Util_Format_GetVar( "lang" ), "ln" ) ;
	$token = Util_Format_Sanatize( Util_Format_GetVar( "token" ), "ln" ) ;
	$proid = Util_Format_Sanatize( Util_Format_GetVar( "proid" ), "ln" ) ;
	$peer_support = Util_Format_Sanatize( Util_Format_GetVar( "prs" ), "n" ) ;
	$api_key = Util_Format_Sanatize( Util_Format_GetVar( "api_key" ), "ln" ) ;
	$redirected = Util_Format_Sanatize( Util_Format_GetVar( "r" ), "n" ) ;
	$preview = Util_Format_Sanatize( Util_Format_GetVar( "preview" ), "n" ) ;
	$style = Util_Format_Sanatize( Util_Format_GetVar( "style" ), "lns" ) ;
	$win_style = ( isset( $VALS["STYLE"] ) && $VALS["STYLE"] ) ? $VALS["STYLE"] : "modern" ; if ( $style ) { $win_style = $style ; }
	if ( !$token ) { $query = preg_replace( "/token=0/", "", $query ) ; HEADER( "location: ./fetch_token.php?$query" ) ; exit ; }
	include_once( "$CONF[DOCUMENT_ROOT]/API/".Util_Format_Sanatize($CONF["SQLTYPE"], "ln") ) ;

	$agent = isset( $_SERVER["HTTP_USER_AGENT"] ) ? $_SERVER["HTTP_USER_AGENT"] : "&nbsp;" ;
	LIST( $ip, $vis_token ) = Util_IP_GetIP( $token ) ;
	LIST( $os, $browser ) = Util_Format_GetOS( $agent ) ;
	$mobile = ( $os == 5 ) ? 1 : 0 ;
	$cookie = ( !isset( $CONF["cookie"] ) || ( $CONF["cookie"] == "on" ) ) ? 1 : 0 ;

	$js_custom_hash = "" ; if ( $autoinvite ) { $custom = ( $custom ) ? "{$custom}-cus-From-_-Automatic Chat Invite-cus-" : "From-_-Automatic Chat Invite-cus-" ; }
	if ( preg_match( "/$ip/", $VALS["CHAT_SPAM_IPS"] ) ) { $spam_exist = 1 ; } else { $spam_exist = 0 ; }
	$dept_online_text = $dept_offline = $dept_settings = $dept_customs = "" ;
	$total_ops = 0 ; $dept_online = Array() ; $departments = Array() ;

	$temp_vname = ( !$js_name && ( isset( $_COOKIE["phplivevname"] ) && ( $_COOKIE["phplivevname"] != "null" ) && $cookie ) ) ? Util_Format_Sanatize( $_COOKIE["phplivevname"], "ln" ) : $js_name ;
	$temp_vemail = ( !$js_email && ( isset( $_COOKIE["phplivevemail"] ) && ( $_COOKIE["phplivevemail"] != "null" ) && $cookie ) ) ? Util_Format_Sanatize( $_COOKIE["phplivevemail"], "e" ) : $js_email ;

	Ops_update_itr_IdleOps( $dbh ) ;
	$departments_pre = Depts_get_AllDepts( $dbh, "display ASC, name ASC" ) ;
	$departments_visible = Array() ;
	for ( $c = 0; $c < count( $departments_pre ); ++$c )
	{
		$department_temp = $departments_pre[$c] ;
		if ( $department_temp["visible"] ) { $departments_visible[] = $department_temp ; }
	}
	if ( !$deptid && ( count( $departments_visible ) == 1 ) )
	{
		$deptid = $departments_visible[0]["deptID"] ;
	}
	$dept_themes = ( isset( $VALS["THEMES"] ) && $VALS["THEMES"] ) ? unserialize( $VALS["THEMES"] ) : Array() ;
	if ( !$theme && isset( $dept_themes[$deptid] ) && $deptid ) { $theme = $dept_themes[$deptid] ; }
	else if ( !$theme ) { $theme = $CONF["THEME"] ; }
	else if ( $theme && !is_file( "$CONF[DOCUMENT_ROOT]/themes/$theme/style.css" ) ) { $theme = $CONF["THEME"] ; }
	if ( !is_file( "$CONF[DOCUMENT_ROOT]/themes/$theme/style.css" ) || isset( $THEMES_EXCLUDE[$theme] ) ) { $theme = "default" ; }

	$screenshots = ( isset( $VALS["SCREENSHOTS"] ) && $VALS["SCREENSHOTS"] ) ? unserialize( $VALS["SCREENSHOTS"] ) : Array() ;
	$screenshot_found = 0 ;
	foreach ( $screenshots as $this_deptid => $value ) { if ( $value ) { $screenshot_found = 1 ; } }
	$addon_marquee = is_file( "$CONF[DOCUMENT_ROOT]/addons/marquee/marquee.php" ) ? 1 : 0 ;
	$addon_phplivebot = is_file( "$CONF[DOCUMENT_ROOT]/addons/phplivebot/phplivebot.php" ) ? 1 : 0 ;
	$addon_screenshot = ( $screenshot_found && is_file( "$CONF[DOCUMENT_ROOT]/addons/screenshot/inc_screenshot.php" ) ) ? 1 : 0 ;
	$phplivebots = ( isset( $VALS_ADDONS ) && isset( $VALS_ADDONS["phplivebots"] ) && $VALS_ADDONS["phplivebots"] ) ? unserialize( base64_decode( $VALS_ADDONS["phplivebots"] ) ) : Array() ;

	$auto_connect_array = ( isset( $VALS["auto_connect"] ) && $VALS["auto_connect"] ) ? unserialize( $VALS["auto_connect"] ) : Array() ;
	$auto_connect_array_dept = Array() ;
	if ( isset( $auto_connect_array[$deptid] ) ) { $auto_connect_array_dept = $auto_connect_array[$deptid] ; }
	if ( !$preview && !$redirected && isset( $auto_connect_array_dept["auto_connect"] ) && ( $auto_connect_array_dept["auto_connect"] != "off" ) )
	{
		/*********************************************************/
		// simply append to custom variable to utilize current method
		/*********************************************************/
		$custom = preg_replace( "/opID-_-(.*?)-cus-/i", "", $custom ) ;
		$custom = preg_replace( "/deptID-_-(.*?)-cus-/i", "", $custom ) ;
		$custom = preg_replace( "/api_key-_-(.*?)-cus-/i", "", $custom ) ;

		$append_api_key = 0 ;
		// append -cus- (custom) separator for safe measure
		if ( $auto_connect_array_dept["opid"] )
		{
			$process = 1 ;
			// need to filter out $deptid of zero so it does not redirect causing a $redirect flag that stops Auto Start chat
			// fixes issue of specific department Auto Start enabled and deptID zero enabled causing a redirect flag set if Auto Start op is not online
			if ( !$deptid )
			{
				$process = 0 ;
				$op_depts = Ops_get_OpDepts( $dbh, $auto_connect_array_dept["opid"] ) ;
				for ( $c = 0; $c < count( $op_depts ); ++$c )
				{
					if ( $op_depts[$c]["status"] ) { $process = 1 ; break ; }
				} if ( !$process ) { unset( $auto_connect_array_dept ) ; }
			}
			if ( $process )
			{
				$append_api_key = 1 ;
				$custom .= "-cus-opID-_-".$auto_connect_array_dept["opid"]."-cus-" ;
			}
		}
		else if ( $auto_connect_array_dept["deptid"] )
		{
			$append_api_key = 1 ;
			$custom .= "-cus-deptID-_-".$auto_connect_array_dept["deptid"]."-cus-" ;
		}
		else if ( !$auto_connect_array_dept["opid"] && !$auto_connect_array_dept["deptid"] && ( count( $departments_pre ) == 1 ) && isset( $departments_pre[0]["deptID"] ) )
		{
			$append_api_key = 1 ;
			$custom .= "-cus-deptID-_-".$departments_pre[0]["deptID"]."-cus-" ;
		}
		if ( $append_api_key ) { $custom .= "-cus-api_key-_-".$CONF['API_KEY']."-cus-" ; }
	}
	$bot_form = "" ; $bot_form_array = Array() ;
	foreach ( $auto_connect_array as $this_deptid => $this_array )
	{
		if ( isset( $this_array["bot_form"] ) && $this_array["bot_form"] )
		{
			$bot_form .= "bot_form[$this_deptid] = 1 ; " ;
			$bot_form_array[$this_deptid] = 1 ;
		}
	}
	/*********************************************************/

	if ( $custom )
	{
		$custom_pairs = explode( "-cus-", $custom ) ;
		for ( $c = 0; $c < count( $custom_pairs ); ++$c )
		{
			if ( $custom_pairs[$c] ) { LIST( $custom_var_name, $custom_var_val ) = explode( "-_-", $custom_pairs[$c] ) ; $js_custom_hash .= "custom_hash['$custom_var_name'] = '$custom_var_val' ; " ; }
		}
		preg_match( "/vquestion-_-(.*?)-cus-/i", $custom, $matches ) ;
		if ( isset( $matches[1] ) ) { $custom = preg_replace( "/vquestion-_-(.*?)-cus-/i", "", $custom ) ; $vquestion = $matches[1] ; }
		else
		{
			preg_match( "/question-_-(.*?)-cus-/i", $custom, $matches ) ;
			if ( isset( $matches[1] ) ) { $custom = preg_replace( "/question-_-(.*?)-cus-/i", "", $custom ) ; $vquestion = $matches[1] ; }
		}

		$append_api_key = 0 ;
		// delete sensitive vars that should not be visible to the public
		preg_match( "/deptID-_-(.*?)-cus-/i", $custom, $matches ) ;
		if ( isset( $matches[1] ) && $matches[1] )
		{
			$append_api_key = 1 ;
			$custom = preg_replace( "/deptID-_-(.*?)-cus-/i", "", $custom ) ; $deptid = $matches[1] ;
		}
		preg_match( "/opID-_-(.*?)-cus-/i", $custom, $matches ) ;
		if ( isset( $matches[1] ) && $matches[1] )
		{
			$append_api_key = 1 ;
			$custom = preg_replace( "/opID-_-(.*?)-cus-/i", "", $custom ) ; $opid = $matches[1] ;
		}
		// double check just in case the api_key was passed in custom field query but deptID and opID does not exist
		preg_match( "/api_key-_-(.*?)-cus-/i", $custom, $matches ) ;
		if ( isset( $matches[1] ) && $append_api_key )
		{
			$custom = preg_replace( "/api_key-_-(.*?)-cus-/i", "", $custom ) ; $api_key = $matches[1] ;
		}
	}

	/*********************************************************/
	// queue redirect
	/*********************************************************/
	$queue_embed_query = ( $embed || $popout ) ? " AND embed = 1 " : " AND embed = 0 " ;
	$query_db = "SELECT queueID, ces, deptID FROM p_queue WHERE md5_vis = '$vis_token' $queue_embed_query LIMIT 1" ;
	database_mysql_query( $dbh, $query_db ) ; $queueinfo = database_mysql_fetchrow( $dbh ) ;
	if ( isset( $queueinfo["ces"] ) && !$preview )
	{
		if ( $popout )
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Queue/update.php" ) ;
			Queue_update_QueueValue( $dbh, $queueinfo["queueID"], "embed", 0 ) ;
		}
		$deptid = $queueinfo["deptID"] ;
		if ( isset( $dept_themes[$deptid] ) && $deptid ) { $theme = $dept_themes[$deptid] ; }
		$requestinfo_onpage = isset( $requestinfo["onpage"] ) ? rawurlencode( Util_Format_URL( $requestinfo["onpage"] ) ) : "" ;
		database_mysql_close( $dbh ) ;
		HEADER( "location: phplive_.php?embed=$embed&popout=$popout&deptid=$deptid&token=$token&vis_token=$vis_token&theme=$theme&ces=$queueinfo[ces]&vname=null&vquestion=null&onpage=$requestinfo_onpage&queue=1&gid=$gid&pgo=".rawurlencode( Util_Format_Sanatize( $page_origin, "url" ) )."&".$now ) ; exit ;
	}
	/*********************************************************/

	/*********************************************************/
	// active chat redirect
	/*********************************************************/
	$requestinfo = ( !$preview ) ? Chat_get_itr_RequestGetInfo( $dbh, 0, "", $vis_token ) : Array() ;
	// popout from embed chat
	if ( isset( $requestinfo["deptID"] ) && ( $requestinfo["md5_vis"] || $requestinfo["md5_vis_"] ) && !$preview )
	{
		$deptid = $requestinfo["deptID"] ;
		if ( isset( $dept_themes[$deptid] ) && $deptid ) { $theme = $dept_themes[$deptid] ; }
		if ( $popout )
		{
			$query = "UPDATE p_requests SET md5_vis = '' WHERE requestID = $requestinfo[requestID]" ;
			database_mysql_query( $dbh, $query ) ;
		}
		else if ( $requestinfo["initiated"] && !$requestinfo["status"] )
		{
			if ( is_file( "$CONF[TYPE_IO_DIR]/{$vis_token}.txt" ) ) { @unlink( "$CONF[TYPE_IO_DIR]/{$vis_token}.txt" ) ; }
			if ( $embed )
			{
				// to ensure it automatically opens the embed window
				$query = "UPDATE p_requests SET md5_vis = '$vis_token' WHERE requestID = $requestinfo[requestID]" ;
				database_mysql_query( $dbh, $query ) ;
			}
		} database_mysql_close( $dbh ) ;
		HEADER( "location: phplive_.php?embed=$embed&popout=$popout&deptid=$deptid&token=$token&vis_token=$vis_token&theme=$theme&ces=$requestinfo[ces]&vname=null&vquestion=null&onpage=".rawurlencode( Util_Format_URL( $requestinfo["onpage"] ) )."&gid=$gid&pgo=".rawurlencode( Util_Format_Sanatize( $page_origin, "url" ) )."&".$now ) ; exit ;
	} if ( is_file( "$CONF[TYPE_IO_DIR]/{$vis_token}.txt" ) ) { @unlink( "$CONF[TYPE_IO_DIR]/{$vis_token}.txt" ) ; }
	/*********************************************************/

	$vars = Util_Format_Get_Vars( $dbh ) ;
	if ( isset( $vars["ts_clear"] ) && ( $vars["ts_clear"] <= ( $now - $VARS_CYCLE_CLEAN ) ) )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Files.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Footprints/remove.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Footprints/remove_itr.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/remove_itr.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/IPs/remove.php" ) ;

		Util_Format_Update_TimeStamp( $dbh, "clear", $now ) ;
		Footprints_remove_itr_Expired_U( $dbh ) ;
		Footprints_remove_ExpiredStats( $dbh ) ;
		Util_Files_CleanUploadDir() ;
		Chat_remove_itr_OldRequests( $dbh ) ;
		IPs_remove_Expired_IPs( $dbh ) ;
		Util_Files_CleanExportDir() ;
	}

	/*********************************************************/
	// start chat automatically
	/*********************************************************/
	if ( !$preview && $opid && ( $deptid > 100000000 ) )
	{
		$this_departments = Depts_get_OpDepts( $dbh, $opid ) ;
		if ( isset( $this_departments[0] ) ) { $deptid = $this_departments[0]["deptID"] ; }
		else { $opid = 0; $deptid = 0 ; }
	}

	/*********************************************************/
	// If redirected from phplive_m.php, skip auto start of chat
	/*********************************************************/
	if ( $redirected && ( $onpage == "message" ) )
	{
		// empty api_key is sufficient
		$api_key = "" ;
	}
	/*********************************************************/

	if ( !$preview && ( $opid || $deptid ) && $api_key && ( $api_key == $CONF['API_KEY'] ) && !$spam_exist && ( !isset( $bot_form_array[$deptid] ) ) )
	{
		database_mysql_close( $dbh ) ;
		$auto_pop = ( $js_name || $js_email ) ? 1 : 0 ;
		$vname_query = ( $js_name ) ? $js_name : Util_Format_Sanatize( Util_Format_GetVar( "vname" ), "ln" ) ;
		if ( preg_replace( "/ /", "", $vname_query ) == "" ) { $vname_query = "Visitor" ; }
		$vemail_query = ( $js_email ) ? $js_email : Util_Format_Sanatize( Util_Format_GetVar( "vemail" ), "e" ) ;
		if ( !$vemail_query ) { $vemail_query = "null" ; }
		$vquestion = rawurlencode( $vquestion ) ;
		$url = base64_encode( "phplive_.php?embed=$embed&prs=$peer_support&popout=$popout&opid=$opid&theme=$theme&api_key=$api_key&vquestion=$vquestion&vis_token=$vis_token&custom=".rawurlencode( $custom )."&vname=".rawurlencode($vname_query)."&vemail=".rawurlencode($vemail_query)."&auto_pop=$auto_pop&gid=$gid" ) ;
		HEADER( "location: blank.php?deptid=$deptid&url=$url&pgo=".rawurlencode( Util_Format_URL( $page_origin ) )."&onpage=".base64_encode( rawurlencode( Util_Format_URL( $onpage ) ) )."&title=".base64_encode( rawurlencode( $title ) ) ) ; exit ;
	}

	// replace the $vname and $vemail with cookie if not autostart
	$vname = ( $temp_vname ) ? $temp_vname : "" ;
	$vemail = ( $temp_vemail ) ? $temp_vemail : "" ;
	$popout = 0 ;

	/*********************************************************/
	// department groups will always be greater than 100000000
	/*********************************************************/
	if ( $deptid > 100000000 )
	{
		$dept_group = Depts_get_DeptGroup( $dbh, $deptid ) ;
		$dept_group_deptids = ( isset( $dept_group["deptids"] ) ) ? explode( ",", $dept_group["deptids"] ) : null ;

		if ( $dept_group_deptids != null )
		{
			$departments_pre_temp = $departments_pre ; $departments_pre = Array() ;
			for ( $c = 0; $c < count( $departments_pre_temp ); ++$c )
			{
				$found = 0 ;
				for ( $c2 = 0; $c2 < count( $dept_group_deptids ); ++$c2 )
				{
					if ( $dept_group_deptids[$c2] && ( $departments_pre_temp[$c]["deptID"] == $dept_group_deptids[$c2] ) )
						$found = 1 ;
				} if ( $found ) { $departments_pre[] = $departments_pre_temp[$c] ; }
			}
			if ( isset( $dept_group["lang"] ) ) { $lang = $dept_group["lang"] ; }
			$departments_visible = $departments_pre ;
		}
		else { $deptid = 0 ; }
	}

	$deptinfo = Array() ;
	if ( $deptid && ( $deptid < 100000000 ) )
	{
		for ( $c = 0; $c < count( $departments_pre ); ++$c )
		{
			$deptinfo_temp = $departments_pre[$c] ;
			if ( $deptid == $deptinfo_temp["deptID"] ) { $deptinfo = Depts_get_DeptInfo( $dbh, $deptid ) ; break 1 ; }
		}
		$departments[0] = $deptinfo ;
		if ( !isset( $deptinfo["deptID"] ) )
		{
			$query = preg_replace( "/embed=(.*?)(\&|$)/", "emb=$1$2", $query ) ; // workaround for the d= situation
			$query = preg_replace( "/(d=(.*?)(&|$))/", "d=0&", $query ) ;
			$query = preg_replace( "/emb=(.*?)(\&|$)/", "embed=$1$2", $query ) ;
			$query = preg_replace( "/deptID-_-(.*?)-cus-/i", "", $query ) ; // clear custom variable to prevent looping
			database_mysql_close( $dbh ) ;
			HEADER( "location: phplive.php?$query&" ) ; exit ;
		}
		if ( $deptinfo["lang"] ) { $CONF["lang"] = $deptinfo["lang"] ; }
	}
	else
	{
		$departments = $departments_visible ;
	}
	for ( $c = 0; $c < count( $departments ); ++$c )
	{
		$department = $departments[$c] ;
		$vupload = ( $department["vupload"] ) ? $department["vupload"] : "" ;
		$screenshot = ( isset( $screenshots[$department["deptID"]] ) && $screenshots[$department["deptID"]] ) ? 1 : 0 ;
		if ( $spam_exist ) { $total = 0 ; }
		else { $total = Ops_get_itr_AnyOpsOnline( $dbh, $department["deptID"] ) ; }
		$total_ops += $total ;

		$auto_connect = isset( $auto_connect_array[$department["deptID"]] ) ? $auto_connect_array[$department["deptID"]]["auto_connect"] : "" ;
		if ( $auto_connect == "op" )
		{
			// check to see if operator online (to limit page load on function select_dept in visitor.js)
			$opid_auto_connect = $auto_connect_array[$department["deptID"]]["opid"] ;
			$is_online = Ops_get_IsOpOnline( $dbh, $opid_auto_connect ) ;
			if ( !$is_online ) { $auto_connect = "" ; }
		}

		// account for zero (0) situation so it doesn't treat it as a valid string (safe measure)
		if ( !$auto_connect ) { $auto_connect = "" ; }

		$bid = ( $addon_phplivebot && isset( $auto_connect_array[$department["deptID"]] ) && ( $auto_connect_array[$department["deptID"]]["auto_connect"] == "bot" ) && isset( $phplivebots[$department["deptID"]] ) ) ? 1 : 0 ;
		$dept_online[$department["deptID"]] = $total ;
		$dept_offline .= "dept_offline[$department[deptID]] = '".preg_replace( "/'|&quot;/", "\"", $department["msg_offline"] )."' ; " ;
		$dept_settings .= " dept_settings[$department[deptID]] = Array( $department[remail], $department[temail], $department[rquestion], $department[rname], $bid, '$vupload', $screenshot, '$auto_connect' ) ; " ;
		$custom_fields = ( $department["custom"] ) ? unserialize( $department["custom"] ) : Array( ) ;
		if ( isset( $custom_fields[0] ) )
		{
			$dept_customs .= " dept_customs[$department[deptID]] = Array( '$custom_fields[0]', $custom_fields[1] " ;
			if ( isset( $custom_fields[2] ) ) { $dept_customs .= ", '$custom_fields[2]', $custom_fields[3] " ; }
			if ( isset( $custom_fields[4] ) ) { $dept_customs .= ", '$custom_fields[4]', $custom_fields[5] " ; }
			if ( isset( $custom_fields[6] ) ) { $dept_customs .= ", '$custom_fields[6]', $custom_fields[7] " ; }
			$dept_customs .= " ) ;" ;
		}
	}

	/*********************************************************/
	// Migration from phplive_m.php to this area to utilize the custom fields
	/*********************************************************/
	$vclick = Util_Format_Sanatize( Util_Format_GetVar( "vclick" ), "n" ) ;
	$ces = "" ; $chat = 0 ; // indicates redirected from declined chat request
	if ( $redirected && ( $onpage == "message" ) && isset( $deptinfo["deptID"] ) )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/get.php" ) ;
		$ces = Util_Format_Sanatize( Util_Format_GetVar( "ces" ), "ln" ) ; $chat = 1 ;

		$dept_online[$deptid] = 0 ;
		$dept_offline = "dept_offline[$deptinfo[deptID]] = '".preg_replace( "/'|&quot;/", "\"", $deptinfo["msg_busy"] )."' ; " ;

		$requestinfo = Chat_get_RequestHistCesInfo( $dbh, $ces ) ;

		$custom_vars = isset( $requestinfo["ces"] ) ? $requestinfo["custom"] : "" ;
		$customs = explode( "-cus-", $custom_vars ) ;
		for ( $c = 0; $c < count( $customs ); ++$c )
		{
			$custom_var = $customs[$c] ;
			if ( $custom_var && preg_match( "/-_-/", $custom_var ) )
			{
				LIST( $custom_var_name, $custom_var_val ) = explode( "-_-", rawurldecode( $custom_var ) ) ;
				if ( $custom_var_val ) { $js_custom_hash .= "custom_hash['$custom_var_name'] = '".Util_Format_StripQuotes( $custom_var_val )."' ; " ; }
			}
		}

		if ( isset( $requestinfo["ces"] ) && ( $requestinfo["vname"] == "Visitor" ) )
			$requestinfo["vname"] = "" ;
		if ( isset( $requestinfo["ces"] ) && ( $requestinfo["vemail"] == "null" ) )
			$requestinfo["vemail"] = "" ;

		// revert onpage to original for email variables
		$onpage = isset( $requestinfo["ces"] ) ? $requestinfo["onpage"] : "" ;
	}
	/*********************************************************/

	$emarketings = Array() ; $emarketinginfo = Array( "id"=>0, "message"=>"", "val_1"=>"", "val_0"=>"", "isreq"=>1, "status"=>0 ) ;
	if ( $VARS_ADDON_EMARKET_ENABLED && is_file( "$CONF[DOCUMENT_ROOT]/addons/emarketing/emarketing.php" ) )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/addons/emarketing/API/Util_Emarketing.php" ) ;
		$emarketings = ( isset( $VALS_ADDONS ) && isset( $VALS_ADDONS["emarketing"] ) && $VALS_ADDONS["emarketing"] ) ? unserialize( base64_decode( $VALS_ADDONS["emarketing"] ) ) : Array() ;
		if ( count( $emarketings ) && !Util_Emarketing_VisExists( $dbh, $vis_token ) )
			$emarketinginfo = current( (Array)$emarketings ) ;
	}

	$deptvars_all = Depts_get_AllDeptsVars( $dbh ) ;
	$dept_offline_form = "" ; $dept_offline_hasform = 0 ; $dept_prechat_form = "" ; $dept_haspolicy = "" ; $dept_addon_emarketings = "" ;
	foreach ( $deptvars_all as $deptid_temp => $deptvar )
	{
		if ( isset( $deptvar["offline_form"] ) )
		{
			$dept_offline_form .= "dept_offline_form[$deptid_temp] = $deptvar[offline_form] ; " ;
			$dept_prechat_form .= "dept_prechat_form[$deptid_temp] = $deptvar[prechat_form] ; " ;

			if ( isset( $deptvar["gdpr_msg"] ) && preg_match( "/-_-/", $deptvar["gdpr_msg"] ) )
			{
				LIST( $text_checkbox, $gdpr_message ) = explode( "-_-", $deptvar["gdpr_msg"] ) ;
				$text_checkbox = rawurlencode( preg_replace( "/\[link\](.*?)\[\/link\]/", "<a href='JavaScript:void(0)' onClick='toggle_policy( $deptid_temp, 0 )'>$1</a>", $text_checkbox ) ) ;
				$dept_haspolicy .= "dept_haspolicy[$deptid_temp] = \"$text_checkbox\" ; " ;
			}
			if ( $deptvar["offline_form"] ) { $dept_offline_hasform = 1 ; }
			if ( $deptvar["emarketID"] ) { $dept_addon_emarketings .= "dept_addon_emarketings[$deptid_temp] = 1 ; " ; }
		}
	}
	if ( !count( $deptvars_all ) ) { $dept_offline_hasform = 1 ; }
	else if ( $total_ops ) { $dept_offline_hasform = 1 ; }
	else if ( isset( $deptvars_all[$deptid] ) ) { $dept_offline_hasform = $deptvars_all[$deptid]["offline_form"] ; }
	else if ( count( $deptvars_all ) == 1 )
	{
		if ( $deptid && !isset( $deptvars_all[$deptid] ) ) { $dept_offline_hasform = 1 ; }
		else if ( !$deptid && ( count( $departments ) > 1 ) ) { $dept_offline_hasform = 1 ; }
	}
	$deptvars = isset( $deptvars_all[$deptid] ) ? $deptvars_all[$deptid] : Array() ;
	$emlogos_hash = ( isset( $VALS["EMLOGOS"] ) && $VALS["EMLOGOS"] ) ? unserialize( $VALS["EMLOGOS"] ) : Array() ;
	$autolinker_js_file = ( isset( $VARS_JS_AUTOLINK_FILE ) && ( ( $VARS_JS_AUTOLINK_FILE == "min" ) || ( $VARS_JS_AUTOLINK_FILE == "src" ) ) ) ? "autolinker_$VARS_JS_AUTOLINK_FILE.js" : "autolinker_min.js" ;

	if ( $lang ) { $CONF["lang"] = $lang ; }
	$CONF["lang"] = ( isset( $CONF["lang"] ) && $CONF["lang"] && is_file( "$CONF[DOCUMENT_ROOT]/lang_packs/".Util_Format_Sanatize($CONF["lang"], "ln").".php" ) ) ? $CONF["lang"] : "english" ;
	// due to Chat_remove_itr_OldRequests include lang, need to use include just in case
	include( "$CONF[DOCUMENT_ROOT]/lang_packs/".Util_Format_Sanatize($CONF["lang"], "ln").".php" ) ;

	/////////////////////////////////////////////
	if ( defined( "LANG_CHAT_WELCOME" ) || !isset( $LANG["CHAT_JS_CUSTOM_BLANK"] ) )
	{ ErrorHandler( 611, "Update to your custom language file is required ($CONF[lang]).  Copy an existing language file and create a new custom language file.", $PHPLIVE_FULLURL, 0, Array( ) ) ; exit ; } if ( $preview && $deptid ) { $dept_online[$deptid] = 1 ; }

	$dept_offline_urls = "" ;
	$offline = ( isset( $VALS['OFFLINE'] ) && $VALS['OFFLINE'] ) ? unserialize( $VALS['OFFLINE'] ) : Array( ) ;
	if ( $gid > $VARS_GID_MIN )
	{
		if ( isset( $offline[$gid] ) && !preg_match( "/^(icon|hide|embed|tab)$/", $offline[$gid] ) ) { $dept_offline_urls .= "dept_offline_urls[0] = '$offline[$gid]' ; " ; }
		else if ( isset( $offline[0] ) && !preg_match( "/^(icon|hide|embed|tab)$/", $offline[0] ) ) { $dept_offline_urls .= "dept_offline_urls[0] = '$offline[0]' ; " ; }
	}
	else
	{
		if ( $deptid && isset( $offline[$deptid] ) && !preg_match( "/^(icon|hide|embed|tab)$/", $offline[$deptid] ) ) { $dept_offline_urls .= "dept_offline_urls[$deptid] = '$offline[$deptid]' ; " ; }
		else if ( $deptid && isset( $offline[$deptid] ) && preg_match( "/^(icon|hide|embed|tab)$/", $offline[$deptid] ) ) { } // no offline url
		else
		{
			foreach( $offline as $this_deptid => $value )
			{
				if ( !isset( $offline[$this_deptid] ) ) { $offline[$this_deptid] = $offline[0] ; }
				$redirect_url = ( isset( $offline[$this_deptid] ) && !preg_match( "/^(icon|hide|embed|tab)$/", $offline[$this_deptid] ) ) ? $offline[$this_deptid] : "" ;
				if ( $redirect_url ) { $dept_offline_urls .= "dept_offline_urls[$this_deptid] = '$redirect_url' ; " ; }
			}
		}
	}
	if ( $preview == 2 )
	{
		// if preview, always online to properly display the texts and form fields for Setup Admin > Interface area
		foreach( $dept_online as $thisdeptid => $null )
			$dept_online[$thisdeptid] = 1 ;
		$dept_offline_hasform = 1 ;
	}
	$LANG_DBS = Lang_get_AllLangs( $dbh ) ;
	if ( isset( $LANG_DBS[$deptid] ) )
	{
		// global chat welcome not in use as of v.4.7.99.9 (display depertment welcome to limit confusion)
		//if ( isset( $LANG_DBS[$deptid]["CHAT_WELCOME"] ) && $LANG_DBS[$deptid]["CHAT_WELCOME"] )
		//	$LANG["CHAT_WELCOME"] = $LANG_DBS[$deptid]["CHAT_WELCOME"] ;
	}
?>
<?php include_once( "./inc_doctype.php" ) ?>
<?php if ( isset( $CONF["KEY"] ) && ( $CONF["KEY"] == md5($KEY."_key_phplive") ) ): ?><?php else: ?>
<!--
********************************************************************
* (c) PHP Live!
* www.phplivesupport.com
********************************************************************
-->
<?php endif ; ?>
<head>
<title> <?php echo urldecode( $LANG["CHAT_WELCOME"] ) ?> </title>
<?php
	// process it after the title display because the LANG will be overwritten here
	$LANG_TEXTS = Array() ; $LANG_TEXTS[$CONF["lang"]] = $LANG ; $depts_lang = "depts_lang[0] = '$CONF[lang]' ; " ;
	for ( $c = 0; $c < count( $departments ); ++$c )
	{
		$deptinfo_temp = $departments[$c] ;
		if ( ( $deptinfo_temp["lang"] != $CONF["lang"] ) && is_file( "$CONF[DOCUMENT_ROOT]/lang_packs/$deptinfo_temp[lang].php" ) )
		{
			include_once( "$CONF[DOCUMENT_ROOT]/lang_packs/$deptinfo_temp[lang].php" ) ;
			$LANG_TEXTS[$deptinfo_temp["lang"]] = $LANG ;
		}
		$depts_lang .= "depts_lang[$deptinfo_temp[deptID]] = '$deptinfo_temp[lang]' ; " ;
	}
	if ( isset( $dept_group ) && isset( $dept_group ) && $dept_group["lang"] ) { $depts_lang .= "depts_lang[$dept_group[groupID]] = '$dept_group[lang]' ; " ; }
?>
<meta name="description" content="v.<?php echo $VERSION ?>">
<meta name="keywords" content="<?php echo md5( $KEY ) ?>">
<meta name="robots" content="noindex,nofollow">
<meta http-equiv="content-type" content="text/html; CHARSET=<?php echo $LANG["CHARSET"] ?>">
<?php include_once( "./inc_meta_dev.php" ) ; ?>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>

<link rel="Stylesheet" href="./themes/<?php echo $theme ?>/style.css?<?php echo filemtime ( "./themes/$theme/style.css" ) ; ?>">
<?php if ( $win_style != "classic" ): ?>
<style>
html, body { font-size: 14px; line-height: 1.4; }
</style>
<?php endif ; ?>

</head>
<body style="overflow: hidden; -webkit-text-size-adjust: 100%;">
<div id="span_loading" style="display: none; position: absolute; right: 5px; bottom: 5px; text-align: right; z-Index: 20;" class="round">
	<img src="./themes/<?php echo $theme ?>/loading_chat.gif" width="16" height="16" border="0" alt="" title="loading..." alt="loading..." class="info_neutral">
</div>
<div id="chat_canvas" style="min-height: 100%; width: 100%;"></div>
<div id="request_body_wrapper_wrapper" style="position: fixed; top: 0px; left: 0px; width: 100%; height: 100%; opacity:0.0; filter:alpha(opacity=00);">
	<?php include_once( "inc_embed_menu.php" ) ; ?>
	<div id="request_body_wrapper">
		<div id="request_body" style="padding: 25px; overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch;">

			<?php if ( !isset( $emlogos_hash[$deptid] ) || ( isset( $emlogos_hash[$deptid] ) && $emlogos_hash[$deptid] ) ): ?>
			<div id="chat_logo" style="padding-bottom: 15px;"><img src="<?php echo Util_Upload_GetLogo( "logo", $deptid ) ?>" border=0 style="max-width: 100%; max-height: 150px; border: 0px;"></div>
			<?php endif ; ?>
			<div style="display: none; margin-bottom: 15px;" class="info_content" id="div_online_pics" onClick="scroll_to_form()">
				<center>
				<table cellspacing=0 cellpadding=2 border=0>
				<tr>
					<td style="display: none; padding-left: 4px; padding-right: 4px;" id="td_pic_0" align="center"></td>
					<td style="display: none; padding-left: 4px; padding-right: 4px;" id="td_pic_1" align="center"></td>
					<td style="display: none; padding-left: 4px; padding-right: 4px;" id="td_pic_2" align="center"></td>
				</tr>
				<tr><td colspan=3><div class="info_good" style="text-align: center;" id="LANG_TXT_ONLINE"></div></td></tr>
				</table>
				</center>
			</div>
			<div id="chat_text_header" style="margin-bottom: 5px;"><span id="LANG_CHAT_WELCOME"></span></div>
			<div id="chat_text_header_sub" style=""><span id="LANG_CHAT_WELCOME_SUBTEXT"></span></div>
			<form method="POST" action="phplive_.php" id="theform" accept-charset="UTF-8">
			<input type="hidden" name="deptid" id="deptid" value="<?php echo ( isset( $requestinfo["deptID"] ) ) ? $requestinfo["deptID"] : $deptid ; ?>">
			<input type="hidden" name="deptid_orig" value="<?php echo $deptid_orig ?>">
			<input type="hidden" name="gid" id="gid" value="<?php echo $gid ; ?>">
			<input type="hidden" name="ces" id="ces" value="<?php echo ( isset( $requestinfo["ces"] ) ) ? $requestinfo["ces"] : "" ; ?>">
			<input type="hidden" name="onpage" id="onpage" value="<?php echo ( isset( $requestinfo["ces"] ) ) ? rawurlencode( Util_Format_URL( $requestinfo["onpage"] ) ) : rawurlencode( Util_Format_URL( $onpage ) ) ; ?>">
			<input type="hidden" name="title" id="title" value="<?php echo ( isset( $requestinfo["ces"] ) ) ? rawurlencode( $requestinfo["title"] ) : rawurlencode( $title ) ; ?>">
			<input type="hidden" name="win_dim" id="win_dim" value="">
			<input type="hidden" name="token" id="token" value="">
			<input type="hidden" name="embed" id="embed" value="<?php echo $embed ?>">
			<input type="hidden" name="vis_token" value="<?php echo $vis_token ?>">
			<input type="hidden" name="skp" id="skp" value="0">
			<input type="hidden" name="prs" id="prs" value="<?php echo $peer_support ?>">
			<input type="hidden" name="theme" id="theme" value="<?php echo $theme ?>">
			<input type="hidden" name="popout" id="popout" value="<?php echo $popout ?>">
			<input type="hidden" name="custom" id="custom" value="<?php echo rawurlencode( $custom ) ?>">
			<input type="hidden" name="opid" id="opid" value="<?php echo $opid ?>">
			<input type="hidden" name="vname_" id="vname_" value="">
			<input type="hidden" name="vemail_" id="vemail_" value="">
			<input type="hidden" name="vquestion_" id="vquestion_" value="">
			<input type="hidden" name="proid" id="proid" value="<?php echo $proid ?>">
			<input type="hidden" name="emarketid" id="emarketid" value="0">
			<input type="hidden" name="pgo" id="pgo" value="<?php echo rawurlencode( $page_origin ) ?>">

			<?php if ( $js_name || $js_email ): ?><input type="hidden" id="auto_pop" name="auto_pop" value="1"><?php endif ; ?>
			<?php if ( $js_name ): ?><input type="hidden" name="vname" value="<?php echo $vname ?>"><?php endif ; ?>
			<?php if ( $js_email ): ?><input type="hidden" name="vemail" value="<?php echo $vemail ?>"><?php endif ; ?>
			<div id="pre_chat_form" style="">
				<div id="div_vdeptids" style="display: none; margin-top: 15px;">
					<div style="margin-bottom: 3px;"><span id="chat_text_department"><span id="LANG_TXT_DEPARTMENT"></span></span></div>
					<select id="vdeptid" onChange="select_dept(this.value)" style="-webkit-appearance: none;" onClick="check_mobile_view('vdeptid', 0)"><option value=<?php echo ( $deptid < 100000000 ) ? 0 : $deptid ; ?>></option>
					<?php
						$selected = "" ;
						for ( $c = 0; $c < count( $departments ); ++$c )
						{
							$department = $departments[$c] ;
							$class = "offline" ;
							if ( $dept_online[$department["deptID"]] ) { $class = "online" ; }

							if ( !$redirected && ( $class == "offline" ) && $addon_phplivebot && isset( $auto_connect_array[$department["deptID"]] ) && ( $auto_connect_array[$department["deptID"]]["auto_connect"] == "bot" ) && isset( $phplivebots[$department["deptID"]] ) )
								$class = "online botonline" ;

							if ( count( $departments ) == 1 ) { $selected = "selected" ; }
							print "<option class=\"$class\" value=\"$department[deptID]\" $selected>$department[name] ()</option>" ;
						}
					?>
					</select>
				</div>
				<div id="div_offline_url" style="display: none; margin-top: 25px;"></div>
				<div id="table_pre_chat_form" style="display: none; margin-top: 15px;">
					<table cellspacing=0 cellpadding=0 border=0 id="table_pre_chat_form_table">
					<tr>
						<td width="50%" style="display: none; padding-right: 10px; padding-bottom: 15px;" id="div_field_0" valign="top"></td>
					</tr>
					<tr>
						<td width="50%" style="display: none; padding-right: 10px;" id="div_field_1" valign="top"></td>
						<td width="50%" style="display: none; padding-left: 10px;" id="div_field_2" valign="top"></td>
					</tr>
					<tr>
						<td width="50%" style="display: none; padding-right: 10px; padding-top: 15px;" id="div_field_3" valign="top"></td>
						<td width="50%" style="display: none; padding-left: 10px; padding-top: 15px;" id="div_field_4" valign="top"></td>
					</tr>
					<tr>
						<td width="50%" style="display: none; padding-right: 10px; padding-top: 15px;" id="div_field_5" valign="top"></td>
						<td width="50%" style="display: none; padding-left: 10px; padding-top: 15px;" id="div_field_6" valign="top"></td>
					</tr>
					<tr>
						<td width="50%" style="display: none; padding-right: 10px; padding-top: 15px;" id="div_field_7" valign="top"></td>
						<td width="50%" style="display: none; padding-left: 10px; padding-top: 15px;" id="div_field_8" valign="top"></td>
					</tr>
					<tr>
						<td colspan=2 style="display: none; padding-top: 15px;" id="div_field_9" valign="top"></td>
					</tr>
					<?php if ( $VARS_INI_UPLOAD ): ?>
					<tr id="tr_files" style="display: none;">
						<td colspan=2 style="padding-top: 20px;">
							<table cellspacing=0 cellpadding=0 border=0>
							<tr>
								<?php if ( $addon_screenshot ): ?>
								<td valign="top" style="padding-right: 25px;" id="tr_screenshot">
									<input type="hidden" name="scr_data" id="scr_data" value="">
									<div style="cursor: pointer;" onClick="screenshot_take()" id="div_screenshot_btn"><img src="themes/initiate/screenshot.png" width="16" height="16" border="0" alt="take webpage screenshot" title="take webpage screenshot"></div>
									<div id="div_screenshot_loading" style="display: none; text-align: right;"><img src="themes/<?php echo $theme ?>/loading_chat.gif" width="16" height="16" border="0" alt="loading..." title="loading..." class="round"></div>
									<div id="div_screenshot_image" style="display: none;"></div>
								</td>
								<?php endif ; ?>
								<td valign="top" id="tr_attachment">
									<span id="span_attachment_icon" style="cursor: pointer;" onClick="$('#the_file').trigger('click')"><img src="themes/initiate/attach.png" width="16" height="16" border="0" alt="" title=""></span>
									<div id="div_attachment_name" style=""></div>
									<div id="span_attachment_loading" style="display: none; width: 100px; text-align: right;"><img src="themes/<?php echo $theme ?>/loading_chat.gif" width="16" height="16" border="0" alt="loading..." title="loading..." class="round"></div>
								</td>
							</tr>
							</table>
						</div>
					</tr>
					<?php endif ; ?>
					</table>
				</div>
			</div>
			<div id="div_checkbox_emarketing_wrapper" style="display: none; margin-top: 25px; padding-bottom: 15px;">
				<div><span id="span_message"><?php echo $emarketinginfo["message"] ?><?php echo ( $emarketinginfo["isreq"] ) ? "" : " ($LANG[TXT_OPTIONAL])" ; ?> </span></div>
				<div style="margin-top: 15px;"><span class="info_clear" style="padding: 3px; cursor: pointer;" onclick="$('#emarket_val_1').prop('checked', true);"><input type="radio" name="emarket_val" id="emarket_val_1" value="1" style=""> &nbsp;<span id="span_val_1"><?php echo $emarketinginfo["val_1"] ?></span></span> &nbsp; &nbsp; <span class="info_clear" style="padding: 3px; cursor: pointer;" onclick="$('#emarket_val_0').prop('checked', true);"><input type="radio" name="emarket_val" id="emarket_val_0" value="0" style=""> &nbsp;<span id="span_val_0"><?php echo $emarketinginfo["val_0"] ?></span></span><input type="radio" name="emarket_val" id="emarket_val_neg_1" value="-1" style="display: none;" checked></div>
			</div>
			<div id="div_checkbox_data_policy_wrapper" style="display: none; margin-top: 25px; padding-bottom: 15px;">
				<div style="">
					<table cellspacing=0 cellpadding=0 border=0>
					<tr>
						<td><div id="div_checkbox_data_policy"><input type="checkbox" id="checkbox_data_policy" onClick="$('#checkbox_data_policy_arrow').hide();" style=""></div></td>
						<td style="padding-left: 10px;"><div id="div_notice_data_policy"><span id="checkbox_data_policy_arrow" style="display: none;">&larr;</span> <span id="div_notice_text_checkbox"></span></div></td>
					</tr>
					</table>
				</div>
			</div>
			</form>

			<div id="pre_chat_no_depts" style="display: none; margin-top: 10px;" class="info_error">
				There are no visible live chat departments at this time.
				
				<div style="margin-top: 15px;">If you are the live chat <b>Setup Admin</b>, please create a department or set a department to visible at:</div>
				<div style="margin-top: 15px;" class="info_box"><code>Setup Admin &gt; Departments</code></div>
			</div>

		</div>
		<div id="chat_submit_btn" style="display: none; padding: 0px !important; z-Index: 15;">
			<div style="padding-top: 6px; padding-bottom: 55px;">
				<table cellspacing=0 cellpadding=0 border=0 width="100%">
				<tr>
					<td><button id="chat_button_start" class="input_button" type="button" style="width: 160px; height: 45px; font-size: 14px; font-weight: bold; padding: 6px;"><span id="LANG_CHAT_BTN_START_CHAT"></span></button></td>
					<td align="right" width="100%"><div id="chat_text_powered" style="text-align: right; font-size: 10px; opacity: 0.5; filter: alpha(opacity=50);"><?php if ( isset( $CONF["KEY"] ) && ( $CONF["KEY"] == md5($KEY."_key_phplive") ) ): ?><?php else: ?>powered by<br><a href="https://www.phplivesupport.com/?plk=pi-23-78m-m&ref=<?php echo ( substr( $KEY, 0, 5 )."-".substr( $KEY, -5, strlen($KEY) ) ) ?>" target="_blank" style="letter-spacing: .8px;">PHP Live!</a><?php endif ; ?></div></td>
				</tr>
				</table>
			</div>
		</div>
	</div>
	<div id="div_policy_wrapper" style="display: none; padding: 10px;" class="info_content">
		<div style="text-align: center; cursor: pointer;" class="info_error" onClick="toggle_policy(0, 1)"><?php echo ( isset( $LANG["CHAT_CLOSE"] ) ) ? $LANG["CHAT_CLOSE"] : "Close" ; ?></div>
		<div id="div_policy" style="margin-top: 15px; height: 180px; overflow: auto; -webkit-overflow-scrolling: touch;"></div>
	</div>
</div>
<?php if ( $addon_marquee ) { include_once( "./addons/marquee/inc_marquee.php" ) ; } ?>

<script data-cfasync="false" type="text/javascript" src="./js/global.js?<?php echo filemtime ( "./js/global.js" ) ; ?>"></script>
<script data-cfasync="false" type="text/javascript" src="./js/visitor.js?<?php echo filemtime ( "./js/visitor.js" ) ; ?>"></script>
<script data-cfasync="false" type="text/javascript" src="./js/framework.js?<?php echo $VERSION ?>"></script>
<script data-cfasync="false" type="text/javascript" src="./js/jquery_md5.js?<?php echo $VERSION ?>"></script>
<script data-cfasync="false" type="text/javascript" src="./js/<?php echo $autolinker_js_file ?>?<?php echo $VERSION ?>"></script>
<?php if ( $addon_marquee ): ?><script data-cfasync="false" type="text/javascript" src="./addons/marquee/js/jquery.marquee.min.js"></script><?php endif ; ?>
<?php if ( $addon_screenshot ): ?><script data-cfasync="false" type="text/javascript" src="./addons/screenshot/js/screenshot.js?<?php echo filemtime ( "./addons/screenshot/js/screenshot.js" ) ; ?>"></script><?php endif ; ?>

<script data-cfasync="false" type="text/javascript">
<!--
	"use strict" ;
	var embed = <?php echo $embed ?> ;
	var mobile = ( <?php echo $mobile ?> ) ? is_mobile() : 0 ;
	var phplive_mobile = 0 ; var phplive_ios = 0 ;
	var phplive_userAgent = navigator.userAgent || navigator.vendor || window.opera ;
	if ( phplive_userAgent.match( /iPad/i ) || phplive_userAgent.match( /iPhone/i ) || phplive_userAgent.match( /iPod/i ) )
	{
		phplive_ios = 1 ;
		if ( phplive_userAgent.match( /iPad/i ) ) { phplive_mobile = 0 ; }
		else { phplive_mobile = 1 ; }
	}
	else if ( phplive_userAgent.match( /Android/i ) ) { phplive_mobile = 2 ; }

	var popout = <?php echo ( !$preview && ( isset( $VALS["POPOUT"] ) && ( $VALS["POPOUT"] == "on" ) ) ) ? 1 : 0 ?> ;
	var win_width = screen.width ;
	var win_height = screen.height ;

	var deptid = <?php echo $deptid ?> ;
	var dept_online_text = new Object ;
	var dept_offline = new Object ;
	var dept_settings = new Object ;
	var dept_customs = new Object ;
	var dept_offline_form = new Object ;
	var dept_prechat_form = new Object ;
	var dept_haspolicy = new Object ;
	var dept_offline_urls = new Object ;
	var custom_hash = new Object ;
	var dept_addon_emarketings = new Object ;
	var bot_form = new Object ;

	var global_form_x ; // global var for original form top position for unset
	var global_diff_height ;
	var global_div_online_pics_scrolltop ;

	var onoff = 0 ;
	var custom_required = 0 ; var custom_required2 = 0 ; var custom_required3 = 0 ; var custom_required4 = 0 ;
	var js_email = "<?php echo $js_email ?>" ;
	var phplive_browser = navigator.appVersion ; var phplive_mime_types = "" ;
	var phplive_display_width = screen.availWidth ; var phplive_display_height = screen.availHeight ; var phplive_display_color = screen.colorDepth ; var phplive_timezone = new Date().getTimezoneOffset() ;
	if ( navigator.mimeTypes.length > 0 ) { for (var x=0; x < navigator.mimeTypes.length; x++) { phplive_mime_types += navigator.mimeTypes[x].description ; } }
	var phplive_browser_gl = ( typeof( document.createElement("canvas").getContext ) != "undefined" ) ? document.createElement("canvas").getContext("webgl") : new Object ; var phplive_browser_gl_string = "" ; for ( var phplive_browser_gl in phplive_browser_gl ) { phplive_browser_gl_string += phplive_browser_gl+phplive_browser_gl[phplive_browser_gl] ; }
	var phplive_browser_token = phplive_md5( phplive_display_width+phplive_display_height+phplive_display_color+phplive_timezone+phplive_browser+phplive_mime_types+phplive_browser_gl_string ) ;
	var phplive_fetch_token = 0 ;
	if ( phplive_browser_token != "<?php echo $token ?>" )
	{
		phplive_fetch_token = 1 ;
		location.href = "fetch_token.php?<?php echo $query ?>" ;
	}
	var autolinker = new Autolinker( { newWindow: true, stripPrefix: false } ) ;
	var win_st_resizing ;
	var st_status_listener ; // prep for si_win_status
	var si_win_status ; var win_minimized ; var page_origin = "<?php echo $page_origin ?>" ;

	var depts_lang = new Object ; <?php echo $depts_lang ?> ;
	var LANG_TEXTS = new Object ; var LANG_DBS = new Object ; var LANG_MERGED = new Object ; 
	<?php
		foreach ( $LANG_TEXTS as $lang_temp => $lang_vars )
		{
			print "LANG_TEXTS['$lang_temp'] = new Object ; " ;
			foreach ( $lang_vars as $var_name => $var_value ) { print "LANG_TEXTS['$lang_temp']['$var_name'] = '".Util_Format_Trim( Util_Format_ConvertQuotes( urldecode( Util_Format_ConvertQuotes( $var_value ) ) ) )."' ; " ; }
		}
		foreach ( $LANG_DBS as $deptid_temp => $lang_vars )
		{
			print "LANG_DBS[$deptid_temp] = new Object ; " ;
			foreach ( $lang_vars as $var_name => $var_value )
			{
				$var_value = preg_replace_callback( "/href=(.*?)>/", function( $matches ) {
					return Util_Format_StripQuotes( $matches[0] ) ;
				}, urldecode( Util_Format_Trim( $var_value ) ) ) ;
				print "LANG_DBS[$deptid_temp]['$var_name'] = '".Util_Format_Trim( preg_replace( "/'/", "&apos;", $var_value ) )."' ; " ;
			}
		}
	?> var total_depts = 0 ;
	var VARS_MISC_MOBILE_MAX_QUIRK = <?php echo ( isset( $VARS_MISC_MOBILE_MAX_QUIRK ) && $VARS_MISC_MOBILE_MAX_QUIRK ) ? 1 : 0 ; ?> ;

	// vars moved to JS rather than PHP v.4.7.99.8
	var theme = "<?php echo $theme ?>" ;
	var preview = <?php echo $preview ; ?> ;
	var dept_offline_hasform = <?php echo $dept_offline_hasform ?> ;
	var emarketid = <?php echo $emarketinginfo["id"] ?> ;
	var emarket_isreq = <?php echo $emarketinginfo["isreq"] ?>  ;

	// check protocol match to ensure cookies are set correctly
	var base_url = "<?php echo $CONF["BASE_URL"] ?>" ;
	var base_url_full = base_url ; // needed for functions that share with phplive_.php
	var phplive_proto = ( location.href.indexOf("https") == 0 ) ? 1 : 0 ; // to avoid JS proto error, use page proto for areas needing to access the JS objects
	if ( !phplive_proto && ( base_url_full.match( /http/i ) == null ) ) { base_url_full = "http:"+base_url_full ; }
	else if ( phplive_proto && ( base_url_full.match( /https/i ) == null ) ) { base_url_full = "https:"+base_url_full ; }
	var proto = phplive_proto ;
	if ( location.href.match( /^http:/i ) && base_url_full.match( /^https/i ) )
	{
		var location_href = location.href.replace( /http:/i, "https:" ) ;
		location.href = location_href ;
	} var redirected = <?php echo $redirected ?> ;
	$('#theform').attr( "action", base_url_full+"/phplive_.php" ) ;

	// [ START ] document ready previous code
	$('#win_dim').val( win_width + " x " + win_height ) ;

	<?php echo $dept_online_text ?>
	<?php echo $dept_offline ?>
	<?php echo $dept_settings ?>
	<?php echo $dept_customs ?>
	<?php echo $dept_offline_form ?>
	<?php echo $js_custom_hash ?>
	<?php echo $dept_prechat_form ?>
	<?php echo $dept_haspolicy ?>
	<?php echo $dept_addon_emarketings ?>
	<?php echo $dept_offline_urls ?>
	<?php echo $bot_form ?>

	if ( preview == 2 )
		$('#chat_embed_title').css({ 'opacity': '1' }) ;

	$('#chat_button_start').html( "<?php echo Util_Format_ConvertQuotes( $LANG["CHAT_BTN_START_CHAT"] ) ?>" ).off('click').on('click', function( ) {
		start_chat( ) ;
	}) ;

	for ( var key_ in dept_offline ) {
		total_depts++ ;
	}
	if ( !total_depts )
	{
		$('#pre_chat_form').hide( ) ;
		$('#pre_chat_no_depts').show( ) ;
	}

	$('#token').val( phplive_browser_token ) ;

	if ( popout && 0 ) { $('#embed_win_popout').show() ; }

	$('#chat_submit_btn').show() ;

	<?php if ( count( $departments ) > 1 ) : ?>$('#div_vdeptids').show( ) ;<?php endif ; ?>

	if ( typeof( $('#pre_chat_form').position() ) != "undefined" )
	{
		var chat_form_pos = $('#pre_chat_form').position() ; global_form_x = chat_form_pos.top ;
		var chat_body_height = $('#request_body').height() ;
		var chat_form_height = $('#pre_chat_form').height() ;
		var diff_height = parseInt(chat_body_height) - parseInt(chat_form_height) ;

		global_diff_height = diff_height - parseInt( chat_form_height ) ;
	}
	// delay so it renders correctly in some devices that may process too fast
	setTimeout( function(){ select_dept( deptid ) ; }, 100 ) ;

	if ( embed )
	{
		if ( phplive_fetch_token )
		{
			// should not be arriving here but a fallback
			if ( typeof( st_status_listener ) != "undefined" ) { clearTimeout( st_status_listener ) ; }
			st_status_listener = setTimeout( function(){ start_win_status_listener() ; }, 400 ) ;
		}
		else { start_win_status_listener() ; }

		// hide if browser not supported or screenshot and files already exist in prior custom in pre-chat
		if ( phplive_mobile || !browser_promise || ( typeof( custom_hash['Screenshot URL'] ) != "undefined" ) )
		{
			$('#tr_screenshot').hide() ;
		}
	}
	else
	{
		// screenshot only available for embed due to taking screenshot of page
		// - new window tricky to detect parent and possible issues
		$('#tr_screenshot').hide() ;
	}

	// [ END ] document ready previous code

	$(window).resize(function( ) {
		init_divs_pre() ;
	});

	function init_divs_input( thedeptid, theonoff )
	{
		$("#table_pre_chat_form").find('*').each( function(){
			var div_name = this.id ;
			if ( div_name.indexOf("div_field_") != -1 )
				$(this).hide() ;
		} );

		var index = 1 ;
		thedeptid = parseInt( thedeptid ) ; // needed to fix possible issues on some browsers not recognizing it as number
		if ( ( thedeptid && ( thedeptid < 100000000 ) ) && ( !theonoff || ( typeof( dept_prechat_form[thedeptid] ) == "undefined" ) || ( ( typeof( dept_prechat_form[thedeptid] ) != "undefined" ) && parseInt( dept_prechat_form[thedeptid] ) ) ) )
		{
			if ( ( typeof( show_divs["custom_field_input_4"] ) != "undefined" ) && ( typeof( show_divs["custom_field_input_4"]["title"] ) != "undefined" ) && ( show_divs["custom_field_input_4"]["title"] != "" ) )
			{
				// intercept and place dropdown at beginning of form
				// dropdown is field custom_field_input_0 (position 0)
				var custom_fields6_array = show_divs["custom_field_input_4"]["title"].split( "," ) ;
				var title = custom_fields6_array[0] ; custom_fields6_array.shift() ;
				var custom_fields_dropdown_values = "" ;
				for ( var c = 0; c < custom_fields6_array.length; ++c )
				{
					var option = custom_fields6_array[c].trim() ;
					var selected = ( ( typeof( custom_hash[title] ) != "undefined" ) && ( custom_hash[title] == option ) ) ? "selected" : "" ;
					custom_fields_dropdown_values += "<option value=\""+option+"\" "+selected+">"+option+"</option>" ;
				}
				$('#div_field_0').html( "<div style=\"margin-bottom: 3px;\">"+title+show_divs["custom_field_input_4"]["optional"]+"</div><select id=\"custom_field_input_0\" name=\"custom_field_input_0\" onChange=\"check_mobile_view('custom_field_input_0', 1)\"><option value=\"\"></option>"+custom_fields_dropdown_values+"</select>" ).show() ;
			}
			for ( var key in show_divs )
			{
				if ( show_divs.hasOwnProperty(key) )
				{
					var thisfield = show_divs[key] ;
					if ( typeof( thisfield["required"] ) != "undefined" )
					{
						if ( key == "vname" )
						{
							var optional_string = show_divs["vname"]["optional"] ;
							$('#div_field_'+index).html( "<div style=\"margin-bottom: 3px;\">"+LANG_MERGED["TXT_NAME"]+" "+optional_string+"</div><input type=\"input\" class=\"input_text\" id=\"vname\" name=\"vname\" maxlength=\"30\" value=\"<?php echo isset( $requestinfo["vname"] ) ? $requestinfo["vname"] : $vname ; ?>\" onKeyPress=\"check_mobile_view('vname', 1);return noquotestags(event);\" onBlur=\"check_mobile_view('vname', 0)\" autocomplete=\"off\">" ).show() ;
							++index ;
						}
						else if ( ( key == "vemail" ) && ( !show_divs["vemail"]["optional"] || !theonoff ) && thedeptid )
						{
							var optional_string = ( !theonoff ) ? "" : show_divs["vemail"]["optional"] ;
							$('#div_field_'+index).html( "<div style=\"margin-bottom: 3px;\">"+LANG_MERGED["TXT_EMAIL"]+" "+optional_string+"</div><input type=\"input\" class=\"input_text\" id=\"vemail\" name=\"vemail\" maxlength=\"160\" value=\"<?php echo isset( $requestinfo["vemail"] ) ? $requestinfo["vemail"] : $vemail ; ?>\" onBlur=\"check_mobile_view('vemail', 0)\" onKeyPress=\"check_mobile_view('vemail', 1);return justemails(event);\">" ).show() ;
							++index ;
						}
						else if ( key == "custom_field_input_1" )
						{
							var disabled = ( show_divs["custom_field_input_1"]["value"] ) ? "disabled" : "" ;
							$('#div_field_'+index).html( "<div style=\"margin-bottom: 3px;\">"+show_divs["custom_field_input_1"]["title"]+show_divs["custom_field_input_1"]["optional"]+"</div><input type=\"input\" class=\"input_text\" id=\"custom_field_input_1\" name=\"custom_field_input_1\" maxlength=\"70\" onKeyPress=\"check_mobile_view('custom_field_input_1', 1);return noquotestags(event);\" onBlur=\"check_mobile_view('custom_field_input_1', 0)\" value=\""+show_divs["custom_field_input_1"]["value"]+"\" "+disabled+" autocomplete=\"off\">" ).show() ;
							++index ;
						}
						else if ( key == "custom_field_input_2" )
						{
							var disabled = ( show_divs["custom_field_input_2"]["value"] ) ? "disabled" : "" ;
							$('#div_field_'+index).html( "<div style=\"margin-bottom: 3px;\">"+show_divs["custom_field_input_2"]["title"]+show_divs["custom_field_input_2"]["optional"]+"</div><input type=\"input\" class=\"input_text\" id=\"custom_field_input_2\" name=\"custom_field_input_2\" maxlength=\"70\" onKeyPress=\"check_mobile_view('custom_field_input_2', 1);return noquotestags(event);\" onBlur=\"check_mobile_view('custom_field_input_2', 0)\" value=\""+show_divs["custom_field_input_2"]["value"]+"\" "+disabled+" autocomplete=\"off\">" ).show() ;
							++index ;
						}
						else if ( key == "custom_field_input_3" )
						{
							var disabled = ( show_divs["custom_field_input_3"]["value"] ) ? "disabled" : "" ;
							$('#div_field_'+index).html( "<div style=\"margin-bottom: 3px;\">"+show_divs["custom_field_input_3"]["title"]+show_divs["custom_field_input_3"]["optional"]+"</div><input type=\"input\" class=\"input_text\" id=\"custom_field_input_3\" name=\"custom_field_input_3\" maxlength=\"70\" onKeyPress=\"check_mobile_view('custom_field_input_3', 1);return noquotestags(event);\" onBlur=\"check_mobile_view('custom_field_input_3', 0)\" value=\""+show_divs["custom_field_input_3"]["value"]+"\" "+disabled+" autocomplete=\"off\">" ).show() ;
							++index ;
						}
						else if ( key == "vsubject" )
						{
							$('#div_field_'+index).html( "<div style=\"margin-bottom: 3px;\">"+LANG_MERGED["TXT_SUBJECT"]+"</div><input type=\"input\" class=\"input_text\" id=\"vsubject\" name=\"vsubject\" maxlength=\"125\" value=\"<?php echo ( $vsubject ) ? $vsubject : "" ; ?>\" onKeyPress=\"check_mobile_view('vsubject', 1);return noquotestags(event);\" onBlur=\"check_mobile_view('vsubject', 0)\" autocomplete=\"off\">" ).show() ;
							++index ;
						}
						else if ( ( key == "vquestion" ) && !show_divs["vquestion"]["optional"] )
						{
							$('#div_field_9').html( "<div style=\"margin-bottom: 3px;\">"+LANG_MERGED["TXT_QUESTION"]+" "+show_divs["vquestion"]["optional"]+"</div><textarea class=\"input_text\" id=\"vquestion\" name=\"vquestion\" rows=\"3\" wrap=\"virtual\" style=\"resize: vertical;\" onKeyPress=\"check_mobile_view('vquestion', 1)\" onBlur=\"check_mobile_view('vquestion', 0)\" <?php echo ( isset( $VALS["AUTOCORRECT_V"] ) && !$VALS["AUTOCORRECT_V"] ) ? "autocomplete='off' autocorrect='off'" : "" ; ?>></textarea>" ).show() ;
							var temp_var = $('<textarea />').html( "<?php echo isset( $requestinfo["question"] ) ? Util_Format_ConvertQuotes( preg_replace( "/(\r\n)|(\n)|(\r)/", "<br>", $requestinfo["question"] ) ) : Util_Format_ConvertQuotes( preg_replace( "/(\r\n)|(\n)|(\r)/", "<br>", $vquestion ) ) ; ?>" ).text() ; setTimeout( function() { $('#vquestion').val( temp_var.replace(/<br>/g, "\r\n") ) ; }, 300 ) ;
						}
					}
				}
			}
		} init_divs_pre() ;
		if ( phplive_ios ) { setTimeout( function(){ init_divs_pre() ; } ) } // fix animate stick bug
	}

	function display_window()
	{
		//var animate_duration = <?php echo ( isset( $VALS["EMBED_ANIMATE"] ) && ( $VALS["EMBED_ANIMATE"] == "off" ) ) ? 500 : 0 ; ?> ;
		var animate_duration = 0 ;
		$('#request_body_wrapper_wrapper').animate({
			opacity: 1
		}, animate_duration, function() {
			//setTimeout( function(){ $('#span_loading').fadeOut("fast") ; }, 300 ) ;
		});
	}

	function close_online_pics()
	{
		$('#div_online_pics').fadeOut( "fast" ).promise( ).done(function( ) {
			/*
			$('#request_body').animate({
				scrollTop: 0
			}, 'slow');
			*/
		}) ;
	}

	function fetch_online_pics( thedeptid )
	{
		var json_data = new Object ;
		var unique = unixtime( ) ;

		$.ajax({
		type: "POST",
		url: base_url_full+"/ajax/actions.php",
		data: "action=fetch_online_pics&deptid="+thedeptid+"&"+unique,
		success: function(data){
			try {
				eval(data) ;
			} catch(err) {
				do_alert( 0, err ) ;
				return false ;
			}

			var json_length = json_data.profile_pics.length ;
			if ( json_data.status && json_length )
			{
				$("#div_online_pics").find('*').each( function(){
					var div_name = this.id ;
					if ( div_name.indexOf("td_pic_") != -1 )
						$(this).hide() ;
				} );

				var pic_string = "" ;
				for ( var c = 0; c < json_length; ++c )
				{
					var name = decodeURIComponent( json_data.profile_pics[c]["name"] ) ;
					var pic = decodeURIComponent( json_data.profile_pics[c]["pic"] ) ;
					$('#td_pic_'+c).html( "<img src=\""+pic+"\" width=\"50\" height=\"50\" border=\"0\" alt=\"\" title=\""+name+"\" alt=\""+name+"\" style=\"border-radius: 50%;\">" ).fadeIn("fast") ;
				}

				if ( json_length )
				{
					if ( typeof( global_div_online_pics_scrolltop ) == "undefined" )
					{
						global_div_online_pics_scrolltop = ( $('#chat_text_header_sub').length ) ? $('#chat_text_header_sub').offset().top - 10 : 0 ;
					}
					$('#div_online_pics').fadeIn( "fast" ).promise( ).done(function( ) {
						$('#LANG_TXT_ONLINE').html( LANG_MERGED["TXT_ONLINE"] ) ;
					}) ;
				}
				else
					close_online_pics() ;
			}
			else
				close_online_pics() ;
		}, error:function (xhr, ajaxOptions, thrownError){ } });
	}

	function start_chat()
	{
		if ( preview )
		{
			do_alert( 0, "Chat is not available for interface preview." ) ;
			return false ;
		}
		else if ( !total_depts )
		{
			$('#pre_chat_no_depts').fadeOut("fast").fadeIn("fast").fadeOut("fast").fadeIn("fast").fadeOut("fast").fadeIn("fast").fadeOut("fast").fadeIn("fast") ;
			return false ;
		}

		var email_form_passed = check_form(0) ;
		if ( email_form_passed )
		{
			var unique = unixtime( ) ;
			var deptid = $('#deptid').val( ) ;
			var vemail = encodeURIComponent( $('#vemail').val() ) ;
			var custom_field_value_1 = ( typeof( $('#custom_field_input_1').val( ) ) != "undefined" ) ? $('#custom_field_input_1').val( ) : "" ;
			var custom_field_value_2 = ( typeof( $('#custom_field_input_2').val( ) ) != "undefined" ) ? $('#custom_field_input_2').val( ) : "" ;
			var custom_field_value_3 = ( typeof( $('#custom_field_input_3').val( ) ) != "undefined" ) ? $('#custom_field_input_3').val( ) : "" ;
			var custom_field_value_4 = ( typeof( $('#custom_field_input_0').val( ) ) != "undefined" ) ? $('#custom_field_input_0').val( ) : "" ;

			var custom_fields6_array = ( ( typeof( dept_customs[deptid] ) != "undefined" ) && ( typeof( dept_customs[deptid][6] ) != "undefined" ) ) ? dept_customs[deptid][6].split( "," ) : Array() ;
			var title = ( typeof( custom_fields6_array[0] ) != "undefined" ) ? custom_fields6_array[0] : "" ; // title of dropdown

			var custom_extra = ( typeof( dept_customs[deptid] ) != "undefined" ) ? encodeURIComponent( title )+"-_-"+encodeURIComponent( custom_field_value_4 )+"-cus-"+encodeURIComponent( dept_customs[deptid][0] )+"-_-"+encodeURIComponent( custom_field_value_1 )+"-cus-"+encodeURIComponent( dept_customs[deptid][2] )+"-_-"+encodeURIComponent( custom_field_value_2 )+"-cus-"+encodeURIComponent( dept_customs[deptid][4] )+"-_-"+encodeURIComponent( custom_field_value_3 )+"-cus-" : "" ;
			var custom = encodeURIComponent( "<?php echo ( $custom ) ? "{$custom}-cus-" : "" ; ?>" ) + custom_extra ;
			$('#custom').val( custom ) ;

			// override files just in case it is populated
			if ( !$('#tr_files').is( ":visible" ) )
			{
				if ( $('#scr_data').length )
					$('#scr_data').val( "" ) ;
				if ( $('#attachment').length )
					$('#attachment').val( "" ) ;
			}
			$('#theform').submit( ) ;
		}
	}

	function send_email_doit( theattach_file )
	{
		var json_data = new Object ;
		var unique = unixtime( ) ;
		var deptid = $('#deptid').val( ) ;
		var vname = $('#vname').val( ) ;
		var vemail = encodeURIComponent( $('#vemail').val() ) ;
		var vsubject = encodeURIComponent( $('#vsubject').val( ) ) ;
		var vquestion = encodeURIComponent( $('#vquestion').val( ) ) ;
		var onpage = encodeURIComponent( "<?php echo $onpage ?>" ).replace( /http/g, "hphp" ) ;
		var custom_field_value_1 = ( typeof( $('#custom_field_input_1').val( ) ) != "undefined" ) ? $('#custom_field_input_1').val( ) : "" ;
		var custom_field_value_2 = ( typeof( $('#custom_field_input_2').val( ) ) != "undefined" ) ? $('#custom_field_input_2').val( ) : "" ;
		var custom_field_value_3 = ( typeof( $('#custom_field_input_3').val( ) ) != "undefined" ) ? $('#custom_field_input_3').val( ) : "" ;
		var custom_field_value_4 = ( typeof( $('#custom_field_input_0').val( ) ) != "undefined" ) ? $('#custom_field_input_0').val( ) : "" ;

		var custom_fields6_array = ( ( typeof( dept_customs[deptid] ) != "undefined" ) && ( typeof( dept_customs[deptid][6] ) != "undefined" ) ) ? dept_customs[deptid][6].split( "," ) : Array() ;
		var title = ( typeof( custom_fields6_array[0] ) != "undefined" ) ? custom_fields6_array[0] : "" ; // title of dropdown

		var custom_extra = ( typeof( dept_customs[deptid] ) != "undefined" ) ? encodeURIComponent( title )+"-_-"+encodeURIComponent( custom_field_value_4 )+"-cus-"+encodeURIComponent( dept_customs[deptid][0] )+"-_-"+encodeURIComponent( custom_field_value_1 )+"-cus-"+encodeURIComponent( dept_customs[deptid][2] )+"-_-"+encodeURIComponent( custom_field_value_2 )+"-cus-"+encodeURIComponent( dept_customs[deptid][4] )+"-_-"+encodeURIComponent( custom_field_value_3 )+"-cus-" : "" ;
		var custom = encodeURIComponent( "<?php echo ( $custom ) ? $custom : "" ; ?>" ) + custom_extra ;
		var emarket_val_1 = ( $('#emarket_val_1').is(':checked') ) ? 1 : 0 ;
		var emarket_val_0 = ( $('#emarket_val_0').is(':checked') ) ? 1 : 0 ;
		var emarket_val = ( ( emarket_val_1 || emarket_val_0 ) && emarket_val_1 ) ? 1 : 0 ;
		if ( !emarket_isreq && !emarket_val_1 && !emarket_val_0 ) { emarket_val = -1 ; }
		var emarketid = $('#emarketid').val() ;
		var scr_data = ( $('#scr_data').length ) ? $('#scr_data').val() : "" ;
		var attach_token = $('#attach_token').val() ;
		if ( attach_token )
		{
			$('#span_attachment_icon').hide() ;
			$('#span_attachment_loading').show() ;
			// IE7/8 takes multiple clicks
			$('#form_attach').submit() ;
		}
		else
		{
			setTimeout( function(){ $('#span_attachment_loading').hide() ; }, 500 ) ;
			var btn_text = $('#chat_button_start').html() ;
			var btn_text_sending = '<img src="themes/<?php echo $theme ?>/loading_fb.gif" width="16" height="11" border="0" alt=""> '+btn_text ;
			$('#chat_button_start').html(btn_text_sending).attr( "disabled", true ) ;

			$.ajax({
			type: "POST",
			url: base_url_full+"/phplive_m.php",
			data: "action=send_email&ces=<?php echo $ces ?>&deptid="+deptid+"&token="+phplive_browser_token+"&vname="+vname+"&vemail="+vemail+"&custom="+custom+"&vsubject="+vsubject+"&vquestion="+vquestion+"&onpage="+onpage+"&emarketid="+emarketid+"&emarket_val="+emarket_val+"&vclick=<?php echo $vclick ?>&scr_data="+scr_data+"&file="+phplive_base64.encode( theattach_file )+"&chat=<?php echo $chat ?>&unique="+unique,
			success: function(data){
				try {
					eval(data) ;
				} catch(err) {
					do_alert( 0, err ) ;
					return false ;
				}

				if ( json_data.status )
				{
					do_alert( 1, LANG_MERGED["CHAT_JS_EMAIL_SENT"] ) ;
					$('#chat_button_start').attr( "disabled", true ) ;
					$('#chat_button_start').html( "<img src=\"./themes/<?php echo $theme ?>/alert_good.png\" width=\"16\" height=\"16\" border=\"0\" alt=\"\"> "+LANG_MERGED["CHAT_JS_EMAIL_SENT"] ) ;

					$('#img_attach_delete').hide() ;
					$('#img_screenshot_delete').hide() ;
				}
				else
				{
					do_alert( 0, json_data.error ) ;
					$('#chat_button_start').attr( "disabled", false ) ;
					$('#chat_button_start').html( LANG_MERGED["CHAT_BTN_EMAIL"] ) ;
				}
			},
			error:function (xhr, ajaxOptions, thrownError){
				do_alert( 0, "Error sending email.  Please refresh the page and try again." ) ;
			} });
		}
	}

	function scroll_to_form()
	{
		$('#request_body').animate({
			scrollTop: global_div_online_pics_scrolltop
		}, 'slow') ;
	}
	<?php if ( $addon_marquee ) { include_once( "./addons/marquee/js/marquee.js.php" ) ; } ?>

//-->
</script>
<!-- need to place at end due to variable usage on event listen (not function) -->
<script data-cfasync="false" type="text/javascript" src="./js/global_fin.js?<?php echo filemtime ( "./js/global_fin.js" ) ; ?>"></script>
<?php if ( $VARS_INI_UPLOAD && is_file( "$CONF[DOCUMENT_ROOT]/addons/file_attach/file_attach.php" ) ) { include_once( "$CONF[DOCUMENT_ROOT]/addons/file_attach/file_attach.php" ) ; } ?>

</body>
</html>
<?php
	if ( isset( $dbh ) && isset( $dbh['con'] ) )
		database_mysql_close( $dbh ) ;
?>