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
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Format.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_IP.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Error.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Vals.php" ) ;
	/* AUTO PATCH */
	$query = isset( $_SERVER["QUERY_STRING"] ) ? Util_Format_Sanatize( $_SERVER["QUERY_STRING"], "query" ) : "" ;
	if ( !is_file( "$CONF[CONF_ROOT]/patches/$patch_v" ) )
	{
		HEADER( "location: patch.php?from=index&".$query ) ; exit ;
	}
	include_once( "$CONF[DOCUMENT_ROOT]/API/".Util_Format_Sanatize($CONF["SQLTYPE"], "ln") ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/lang_packs/".Util_Format_Sanatize($CONF["lang"], "ln").".php" ) ;
	/////////////////////////////////////////////
	if ( is_file( "$CONF[CONF_ROOT]/addons.php" ) ) { include_once( "$CONF[CONF_ROOT]/addons.php" ) ; }
	if ( defined( "LANG_CHAT_WELCOME" ) || !isset( $LANG["CHAT_JS_CUSTOM_BLANK"] ) )
	{ ErrorHandler( 611, "Update to your custom language file is required ($CONF[lang]).  Copy an existing language file and create a new custom language file.", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	$perm_web = is_writable( "$CONF[CONF_ROOT]" ) ; $perm_conf = is_writeable( "$CONF[CONF_ROOT]/config.php" ) ; $perm_chats = is_writeable( $CONF["CHAT_IO_DIR"] ) ; $perm_initiate = is_writeable( $CONF["TYPE_IO_DIR"] ) ; $perm_patches = is_writeable( "$CONF[CONF_ROOT]/patches" ) ;
	if ( !$perm_web || !$perm_conf || !$perm_chats || !$perm_initiate || !$perm_patches )
	{ ErrorHandler( 609, "Crucial files or directories are not writeable by the system.  Permission denied.", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	$ldap_array = ( isset( $VALS_ADDONS ) && isset( $VALS_ADDONS["LDAP"] ) && $VALS_ADDONS["LDAP"] ) ? unserialize( base64_decode( $VALS_ADDONS["LDAP"] ) ) : Array() ;
	$addon_ldap = ( isset( $ldap_array["server"] ) && is_file( "$CONF[DOCUMENT_ROOT]/addons/ldap/ldap.php" ) ) ? 1 : 0 ;
	$url_parts = parse_url( $PHPLIVE_HOST ) ;
	$host_host = isset( $url_parts["path"] ) ? $url_parts["path"] : "" ;
	$base_url = $CONF["BASE_URL"] ;
	$url_parts = parse_url( $base_url ) ;
	$host_base = isset( $url_parts["host"] ) ? $url_parts["host"] : "" ;

	$action = Util_Format_Sanatize( Util_Format_GetVar( "action" ), "ln" ) ;
	$login = Util_Format_Sanatize( Util_Format_GetVar( "phplive_login" ), "eln" ) ;
	$password = Util_Format_Sanatize( Util_Format_GetVar( "phplive_password" ), "ln" ) ;
	$password_temp = Util_Format_Sanatize( Util_Format_GetVar( "phplive_password_temp" ), "ln" ) ;
	$from = Util_Format_Sanatize( Util_Format_GetVar( "from" ), "ln" ) ;
	$wp = ( Util_Format_Sanatize( Util_Format_GetVar( "wp" ), "n" ) ) ? Util_Format_Sanatize( Util_Format_GetVar( "wp" ), "n" ) : 0 ;
	$auto = Util_Format_Sanatize( Util_Format_GetVar( "auto" ), "n" ) ;  if ( !$auto && $wp ) { $auto = 1 ; }
	$mapp = Util_Format_Sanatize( Util_Format_GetVar( "mapp" ), "n" ) ;
	$platform = Util_Format_Sanatize( Util_Format_GetVar( "platform" ), "n" ) ;
	$arn = Util_Format_Sanatize( Util_Format_GetVar( "arn" ), "url" ) ;
	$menu = ( Util_Format_Sanatize( Util_Format_GetVar( "menu" ), "ln" ) == "sa" ) ? "sa" : "operator" ;
	$wpress = Util_Format_Sanatize( Util_Format_GetVar( "wpress" ), "n" ) ;
	$open_status = Util_Format_Sanatize( Util_Format_GetVar( "open_status" ), "n" ) ;
	$opid = Util_Format_Sanatize( Util_Format_GetVar( "opid" ), "n" ) ;
	$remember = Util_Format_Sanatize( Util_Format_GetVar( "remember" ), "n" ) ;
	$ext = Util_Format_Sanatize( Util_Format_GetVar( "ext" ), "ln" ) ;
	$pr = Util_Format_Sanatize( Util_Format_GetVar( "phplive_password_reset" ), "n" ) ;
	$lc = Util_Format_Sanatize( Util_Format_GetVar( "lc" ), "n" ) ;
	$token_pass = md5($CONF['DOCUMENT_ROOT'].$CONF['TIMEZONE']) ;
	LIST( $ip, $null ) = Util_IP_GetIP( "" ) ; $ses = "" ;

	if ( $opid ) { HEADER( "location: phplive.php?opid=$opid" ) ; exit ; }
	$agent = isset( $_SERVER["HTTP_USER_AGENT"] ) ? $_SERVER["HTTP_USER_AGENT"] : "&nbsp;" ;
	LIST( $os, $browser ) = Util_Format_GetOS( $agent ) ;
	$mobile = ( $os == 5 ) ? 1 : 0 ;

	$error = $reload = $auto_login_token = $auto_login_token_ses = "" ;
	if ( !isset( $CONF["screen"] ) ) { $CONF["screen"] = "same" ; }
	if ( $auto || $wp || $wpress || ( $query == "op" ) ) { $CONF["screen"] = "separate" ; }
	if ( !isset( $_COOKIE["cCk"] ) ) { Util_Format_SetCookie( "cCk", 1, $now+(60*60*24*90), "/", "", $PHPLIVE_SECURE ) ; }

	if ( !isset( $CONF["API_KEY"] ) )
	{
		$CONF["API_KEY"] = Util_Format_RandomString( 10 ) ;
		$error = ( Util_Vals_WriteToConfFile( "API_KEY", $CONF["API_KEY"] ) ) ? "" : "Could not write to config file." ;
	}
	if ( !isset( $CONF["SALT"] ) ) { $CONF["SALT"] = Util_Format_RandomString( 32 ) ; Util_Vals_WriteToConfFile( "SALT", $CONF["SALT"] ) ; }
	if ( !isset( $VALS['EMBED_OPINVITE_AUTO'] ) ) { Util_Vals_WriteToFile( "EMBED_OPINVITE_AUTO", "on" ) ; }
	if ( isset( $_COOKIE["cAT"] ) && preg_match( "/\.\./", $_COOKIE["cAT"] ) )
	{
		$auto_login_token_temp = Util_Format_Sanatize( $_COOKIE["cAT"], "ln" ) ;
		LIST( $auto_login_token, $auto_login_token_ses ) = explode( "..", $auto_login_token_temp ) ;
		$remember = 1 ;
	}
	// BETA version checks
	if ( is_file( "$CONF[CONF_ROOT]/patches/167" ) && !is_file( "$CONF[CONF_ROOT]/patches/167__" ) )
	{
		// BETA needs patched to latest
		@unlink( "$CONF[CONF_ROOT]/patches/167" ) ; HEADER( "location: patch.php" ) ; exit ;
	}
	if ( $action === "secure" )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Vars/update.php" ) ;

		// fix possible issues on HTTPS auto redirect environments
		if ( preg_match( "/^((http:)|(\/))/i", $CONF["BASE_URL"] ) )
		{
			$base_url = preg_match( "/^(http:)/i", $CONF["BASE_URL"] ) ? preg_replace( "/^http:/i", "https:", $CONF["BASE_URL"] ) : preg_replace( "/^\/\//i", "https://", $CONF["BASE_URL"] ) ;
			$CONF["BASE_URL"] = $base_url ; 
			Util_Vals_WriteToConfFile( "BASE_URL",  $base_url ) ;
			Vars_update_Var( $dbh, "code", 2 ) ; // code 2 is https
		}
	}
	else if ( $action === "update_auto_login" )
	{
		$value = Util_Format_Sanatize( Util_Format_GetVar( "value" ), "ln" ) ;
		if ( $value )
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get.php" ) ;

			$opinfo = Ops_get_OpInfoByID( $dbh, $_COOKIE["cO"] ) ;
			$auto_login_token = md5( "$opinfo[login]$opinfo[password]" )."..".$opinfo["ses"] ;
			Util_Format_SetCookie( "cAT", $auto_login_token, $now+(60*60*24*1095), "/", "", $PHPLIVE_SECURE ) ;
		}
		else { Util_Format_SetCookie( "cAT", FALSE, -1, "/", "", $PHPLIVE_SECURE ) ; }

		if ( isset( $dbh ) && isset( $dbh['con'] ) ) { database_mysql_close( $dbh ) ; }
		HEADER('Content-Type: text/plain; charset=utf-8') ;
		$json_data = "json_data = { \"status\": 1 };" ;
		print $json_data ; exit ;
	}
	else if ( $action === "submit" )
	{
		$menu = Util_Format_Sanatize( Util_Format_GetVar( "menu" ), "ln" ) ;

		if ( !isset( $_COOKIE["cCk"] ) )
			$error = "Please enable browser cookies." ;
		else if ( $menu == "sa" )
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get_ext.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/update_ext.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Footprints/get_ext.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Setup/get.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Setup/remove.php" ) ;

			//Setup_remove_ExpiredAdmins( $dbh ) ;
			$admininfo = Setup_get_InfoByLogin( $dbh, $login ) ;
			if ( isset( $admininfo["adminID"] ) && ( $password == md5( md5( "phplive".substr( md5( $CONF['SALT'].$admininfo["password"] ), 0, 6 ) ).$token_pass ) ) )
			{ $password = md5( $admininfo["password"].$token_pass ) ; $pr = 1 ; }
			if ( isset( $admininfo["adminID"] ) && ( $password === md5( $admininfo["password"].$token_pass ) ) )
			{
				$pr_query = "" ;
				if ( $pr )
				{
					Util_Format_SetCookie( "phplive_pr", md5( "phplive".substr( md5( $CONF['SALT'].$admininfo["password"] ), 6, 12 ) ), -1, "/", "", $PHPLIVE_SECURE ) ;
					$pr_query = "pr=1&" ;
				}
				else { Util_Format_SetCookie( "phplive_pr", "", -1, "/", "", $PHPLIVE_SECURE ) ; }
				$init_query = "" ;
				if ( !$admininfo["lastactive"] ) { $init_query = "init=1&" ; }

				$ses = md5( $now.$ip ) ;
				Ops_update_ext_AdminValue( $dbh, $admininfo["adminID"], "lastactive", $now ) ;
				Ops_update_ext_AdminValue( $dbh, $admininfo["adminID"], "ses", $ses ) ;
				Util_Format_SetCookie( "phpliveadminID", $admininfo['adminID'], -1, "/", "", $PHPLIVE_SECURE ) ;
				Util_Format_SetCookie( "phpliveadminSES", $ses, -1, "/", "", $PHPLIVE_SECURE ) ;

				database_mysql_close( $dbh ) ;
				HEADER( "location: setup/index.php?$pr_query$init_query$now" ) ; exit ;
			} else { $error = "Invalid login or password." ; }
		}
		else
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get_ext.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/update.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Functions_itr.php" ) ;
			if ( $addon_ldap )
			{
				include_once( "$CONF[DOCUMENT_ROOT]/addons/ldap/API/Util_LDAP.php" ) ;
				$password = rawurldecode( Util_Format_Sanatize( Util_Format_GetVar( "phplive_password_ldap" ), "" ) ) ;
				LIST( $opinfo, $password ) = Util_LDAP_login( $dbh, $token_pass, $login, $password ) ;
				if ( preg_match( "/Unable to bind to server/i", $password ) )
					$error = "LDAP server is temporarily down or is not responding." ;
				else if ( preg_match( "/Base DN/i", $password ) )
					$error = "LDAP server information is invalid.  Please contact the Setup Admin." ;
			}
			else { $opinfo = Ops_get_ext_OpInfoByLogin( $dbh, $login ) ; }
			if ( !$error && isset( $opinfo["opID"] ) && ( $password == md5( md5( "phplive".substr( md5( $CONF['SALT'].$opinfo["password"] ), 0, 6 ) ).$token_pass ) ) )
			{ $password = md5( $opinfo["password"].$token_pass ) ; $pr = 1 ; }
			if ( !$error && isset( $opinfo["opID"] ) && ( $password === md5( $opinfo["password"].$token_pass ) ) )
			{
				$pr_query = "" ;
				if ( $pr )
				{
					// phplive_pr = password reset flag
					Util_Format_SetCookie( "phplive_pr", md5( "phplive".substr( md5( $CONF['SALT'].$opinfo["password"] ), 6, 12 ) ), -1, "/", "", $PHPLIVE_SECURE ) ;
					$pr_query = "pr=1&" ;
				}
				else { Util_Format_SetCookie( "phplive_pr", "", -1, "/", "", $PHPLIVE_SECURE ) ; }

				$opid = $opinfo["opID"] ;
				if ( $mapp && !$opinfo["mapper"] )
				{
					$error = "Account does not have Mobile App access." ;
				}
				else if ( is_file( "$CONF[TYPE_IO_DIR]/$opid.locked" ) )
				{
					$error = "Account is inactive.  Please contact the Setup Admin for more information." ;
				}
				else if ( $opinfo["login"] == "phplivebot" )
				{
					$error = "$opinfo[name]: Login access not allowed.  Sorry about that." ;
				}
				else
				{
					$ses = md5( Util_Format_RandomString(25).$ip ) ;
					$opvars = Ops_get_OpVars( $dbh, $opid ) ;
					$op_sounds = ( isset( $VALS["op_sounds"] ) && $VALS["op_sounds"] ) ? unserialize( $VALS["op_sounds"] ) : Array() ;
					if ( isset( $op_sounds[$opid] ) ) { $op_sounds_vals = $op_sounds[$opid] ; $opinfo["sound1"] = $op_sounds_vals[0] ; $opinfo["sound2"] = $op_sounds_vals[1] ; } else { $opinfo["sound1"] = "default" ; $opinfo["sound2"] = "default" ; }
					if ( !isset( $opvars["sound"] ) )
					{
						include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/put.php" ) ;
						Ops_put_OpVars( $dbh, $opid ) ;
					}

					// only one instance of console window per browser type since system uses cookies
					if ( $auto_login_token_ses )
					{
						if ( $opinfo["ses"] != $auto_login_token_ses )
						{
							Util_Format_SetCookie( "cAT", FALSE, -1, "/", "", $PHPLIVE_SECURE ) ;
							database_mysql_close( $dbh ) ;
							HEADER( "location: logout.php?action=logout&dup=1&wp=$wp&auto=$auto&menu=operator&mapp=$mapp&wpress=$wpress&$now" ) ; exit ;
						}
						$auto_login_token = md5( "$opinfo[login]$opinfo[password]" )."..".$ses ;
						Util_Format_SetCookie( "cAT", $auto_login_token, $now+(60*60*24*1095), "/", "", $PHPLIVE_SECURE ) ;
						$remember = 1 ;
					}
					else if ( $remember )
					{
						$auto_login_token = md5( "$opinfo[login]$opinfo[password]" )."..".$ses ;
						Util_Format_SetCookie( "cAT", $auto_login_token, $now+(60*60*24*1095), "/", "", $PHPLIVE_SECURE ) ;
					} else { Util_Format_SetCookie( "cAT", FALSE, -1, "/", "", $PHPLIVE_SECURE ) ; }

					Ops_update_OpValues( $dbh, $opid, "ses", $ses, "lastactive", $now ) ;

					if ( $mapp )
					{
						$mapp_opid = $opid ;
						$mapp_array = ( isset( $VALS["MAPP"] ) && $VALS["MAPP"] ) ? unserialize( $VALS["MAPP"] ) : Array() ;
						if ( $arn && $platform && ( !isset( $mapp_array[$mapp_opid] ) || ( $mapp_array[$mapp_opid]["a"] != $arn ) || ( $mapp_array[$mapp_opid]["p"] != $platform ) ) )
						{
							$mapp_array[$mapp_opid]["a"] = "$arn" ;
							$mapp_array[$mapp_opid]["p"] = $platform ; 
							Util_Vals_WriteToFile( "MAPP", serialize( $mapp_array ) ) ;
						}
						if ( is_file( "$CONF[TYPE_IO_DIR]/$mapp_opid.mapp" ) )
						{
							@unlink( "$CONF[TYPE_IO_DIR]/$mapp_opid.mapp" ) ;
						}
						Ops_update_OpValue( $dbh, $opid, "mapp", $mapp_opid ) ;
						Util_Format_SetCookie( "cO", $opid, $now+(60*60*24*90), "/", "", $PHPLIVE_SECURE ) ;
						Util_Format_SetCookie( "cS", $ses, $now+(60*60*24*90), "/", "", $PHPLIVE_SECURE ) ;
					}
					else
					{
						Ops_update_OpValue( $dbh, $opid, "mapp", 0 ) ;
						Util_Format_SetCookie( "cO", $opid, -1, "/", "", $PHPLIVE_SECURE ) ;
						Util_Format_SetCookie( "cS", $ses, -1, "/", "", $PHPLIVE_SECURE ) ;
					}
					Util_Format_SetCookie( "cCk", NULL, -1, "/", "", $PHPLIVE_SECURE ) ;

					$ses_filename = $opid."_ses_$ses" ;
					$dir_files = glob( $CONF["TYPE_IO_DIR"]."/".$opid."_ses_*", GLOB_NOSORT ) ;
					$total_dir_files = count( $dir_files ) ;
					if ( $total_dir_files )
					{
						for ( $c = 0; $c < $total_dir_files; ++$c )
						{
							if ( $dir_files[$c] && is_file( $dir_files[$c] ) ) { @unlink( $dir_files[$c] ) ; }
						}
					}
					if ( !is_file( "$CONF[TYPE_IO_DIR]/$ses_filename.ses" ) ) { touch( "$CONF[TYPE_IO_DIR]/$ses_filename.ses" ) ; }
					Ops_update_OpResetOffStatus( $dbh, $opid ) ;
				}
			} else if ( !$error ) { $error = "Invalid login or password." ; }
		}
	}
	else if ( $action === "reset_password" )
	{
		if ( $ip && preg_match( "/$ip/", $VALS["CHAT_SPAM_IPS"] ) )
			$json_data = "json_data = { \"status\": 0, \"error\": \"Could not process request at this time.\" };" ;
		else
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Functions_itr.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Email.php" ) ;
	
			if ( $menu == "sa" )
			{
				include_once( "$CONF[DOCUMENT_ROOT]/API/Setup/get.php" ) ;
				include_once( "$CONF[DOCUMENT_ROOT]/API/Setup/update.php" ) ;

				$admininfo = Setup_get_InfoByLogin( $dbh, $login ) ;
				if ( isset( $admininfo["adminID"] ) )
				{
					if ( isset( $admininfo["error"] ) )
					{
						$json_data = "json_data = { \"status\": 0, \"error\": \"Multiple matched accounts found.\" };" ;
					}
					else if ( $admininfo["lastactive"] > ( $now-30 ) )
					{
						$time_left = $admininfo["lastactive"] - ( $now-30 ) ;
						$json_data = "json_data = { \"status\": 0, \"error\": \"Please try again in $time_left seconds.\" };" ;
					}
					else if ( $admininfo["status"] == -1 )
						$json_data = "json_data = { \"status\": 0, \"error\": \"Password reset is not available for this account.\" };" ;
					else
					{
						include_once( "$CONF[DOCUMENT_ROOT]/API/Depts/get.php" ) ;
						$departments = Depts_get_AllDepts( $dbh ) ;

						for ( $c = 0; $c < count( $departments ); ++$c )
						{
							$deptinfo = $departments[$c] ;
							if ( $deptinfo["smtp"] )
							{
								$smtp_array = unserialize( Util_Functions_itr_Decrypt( $CONF["SALT"], $deptinfo["smtp"] ) ) ;
								break 1 ;
							}
						}

						$password_new = "phplive".substr( md5( $CONF['SALT'].$admininfo["password"] ), 0, 6 ) ;
						$message = "You have requested a password reset to the Setup Admin area.\r\n\r\nYour new password is:\r\n\r\n$password_new\r\n\r\n" ;
						$error = Util_Email_SendEmail( "Setup Admin", $admininfo["email"], "Setup Admin", $admininfo["email"], "Setup Admin Password Reset", $message, "" ) ;

						$email_partial = string_mask( $admininfo["email"], 4, strlen( $admininfo["email"] ) ) ;
						if ( !$error )
						{
							Setup_update_SetupValue( $dbh, $admininfo["adminID"], "lastactive", $now ) ;
							$json_data = "json_data = { \"status\": 1, \"message\": \"Email sent! Check your email address ($email_partial).\" };" ;
						}
						else
							$json_data = "json_data = { \"status\": 0, \"error\": \"$error\" };" ;
					}
				} else { $json_data = "json_data = { \"status\": 0, \"error\": \"Setup Admin login ($login) is invalid.\" };" ; }
			}
			else
			{
				include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get_ext.php" ) ;
				include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/update.php" ) ;

				$opinfo = Ops_get_ext_OpInfoByLogin( $dbh, $login ) ;
				if ( isset( $opinfo["opID"] ) )
				{
					if ( $addon_ldap ) { $json_data = "json_data = { \"status\": 0, \"error\": \"LDAP is active.  Forgot password is not available.  Please contact the Setup Admin for more information.\" };" ; }
					else
					{
						$opid = $opinfo["opID"] ;
						if ( is_file( "$CONF[TYPE_IO_DIR]/$opid.locked" ) )
						{
							$json_data = "json_data = { \"status\": 0, \"error\": \"Account is inactive.  Please contact the Setup Admin for more information.\" };" ;
						}
						else if ( $opinfo["login"] == "phplivebot" )
						{
							$json_data = "json_data = { \"status\": 0, \"error\": \"$opinfo[name]: Login access not allowed.  Sorry about that.\" };" ;
						}
						else
						{
							if ( $opinfo["lastactive"] > ( $now-60 ) )
							{
								$time_left = $opinfo["lastactive"] - ( $now-60 ) ;
								$json_data = "json_data = { \"status\": 0, \"error\": \"Please try again in $time_left seconds.\" };" ;
							}
							else
							{
								include_once( "$CONF[DOCUMENT_ROOT]/API/Depts/get.php" ) ;
								$departments = Depts_get_OpDepts( $dbh, $opid ) ;

								for ( $c = 0; $c < count( $departments ); ++$c )
								{
									$deptinfo = $departments[$c] ;
									if ( $deptinfo["smtp"] )
									{
										$smtp_array = unserialize( Util_Functions_itr_Decrypt( $CONF["SALT"], $deptinfo["smtp"] ) ) ;
										break 1 ;
									}
								}

								$password_new = "phplive".substr( md5( $CONF['SALT'].$opinfo["password"] ), 0, 6 ) ;
								$message = "You have requested a password reset to your chat operator account.\r\n\r\nYour new password is:\r\n\r\n$password_new\r\n\r\n" ;
								$error = Util_Email_SendEmail( $opinfo["name"], $opinfo["email"], $opinfo["name"], $opinfo["email"], "Operator Password Reset", $message, "" ) ;

								$email_partial = string_mask( $opinfo["email"], 4, strlen( $opinfo["email"] ) ) ;
								if ( !$error )
								{
									Ops_update_OpValue( $dbh, $opid, "lastactive", $now ) ;
									$json_data = "json_data = { \"status\": 1, \"message\": \"Email sent! Check your email address ($email_partial).\" };" ;
								} else { $json_data = "json_data = { \"status\": 0, \"error\": \"$error\" };" ; }
							}
						}
					}
				}
				else { $json_data = "json_data = { \"status\": 0, \"error\": \"Operator login ($login) is invalid.\" };" ; }
			}
		}
		if ( isset( $dbh ) && isset( $dbh['con'] ) ) { database_mysql_close( $dbh ) ; }
		HEADER('Content-Type: text/plain; charset=utf-8') ;
		print $json_data ; exit ;
	}
	else
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Files.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/remove.php" ) ;
		Util_Files_CleanUploadDir() ;
		Util_Files_CleanExportDir() ;
		$online_offline_log_expire = 3 ; // years
		Ops_remove_CleanStats( $dbh, $online_offline_log_expire ) ;
	}

	$md5_password = "" ;
	if ( !$login && $auto_login_token && $auto_login_token_ses && ( $menu != "sa" ) )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get.php" ) ;

		$opinfo_ = Ops_get_OpInfoByToken( $dbh, $auto_login_token ) ;
		if ( isset( $opinfo_["opID"] ) )
		{
			$md5_password = md5( $opinfo_["password"].$token_pass ) ;
			$remember = 1 ;
		} else { Util_Format_SetCookie( "cAT", FALSE, -1, "/", "", $PHPLIVE_SECURE ) ; }
	}
	// main one included at chat_actions_op_status.php
	include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get_itr.php" ) ;
	if ( !Ops_get_itr_AnyOpsOnline( $dbh, 0 ) )
	{
		$dir_files = glob( $CONF["TYPE_IO_DIR"]."/*", GLOB_NOSORT ) ;
		$total_dir_files = count( $dir_files ) ;
		if ( $total_dir_files )
		{
			for ( $c = 0; $c < $total_dir_files; ++$c )
			{
				if ( $dir_files[$c] && is_file( $dir_files[$c] ) && !preg_match( "/\.ses$/", $dir_files[$c] ) && !preg_match( "/\.mapp$/", $dir_files[$c] ) && !preg_match( "/index\.php$/", $dir_files[$c] ) && !preg_match( "/\.locked$/", $dir_files[$c] ) ) { @unlink( $dir_files[$c] ) ; }
			}
		}
	}

	function string_mask( $string, $start, $end, $char_replace = '.' )
	{
		$middle = '' ;
		for ( $c = $start; $c < strlen( $string ); $c++ )
		{
			if ( $string[$c] == "@" ) { $middle .= "@" ; }
			else { $middle .= $char_replace ; }
		}
		return substr( $string, 0, $start ).$middle ;
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
<title> <?php if ( isset( $CONF["KEY"] ) && ( $CONF["KEY"] == md5($KEY."_key_phplive") ) ): ?>Live Chat Solution<?php else: ?>PHP Live! Support<?php endif ; ?> </title>

<meta name="description" content="v.<?php echo $VERSION ?>">
<meta name="keywords" content="<?php echo md5( $KEY ) ?>">
<meta name="robots" content="all,index,follow">
<meta http-equiv="content-type" content="text/html; CHARSET=utf-8">
<?php include_once( "./inc_meta_dev.php" ) ; ?>
<meta name="viewport" content="user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1, width=device-width, height=device-height" />

<link rel="Stylesheet" href="./css/setup.css?<?php echo $VERSION ?>">
<?php if ( $mapp ): ?><script data-cfasync="false" type="text/javascript" src="./mapp/js/mapp.js?<?php echo $VERSION ?>"></script><?php endif ; ?>
<script data-cfasync="false" type="text/javascript" src="./js/global.js?<?php echo $VERSION ?>"></script>
<script data-cfasync="false" type="text/javascript" src="./js/framework.js?<?php echo $VERSION ?>"></script>
<script data-cfasync="false" type="text/javascript" src="./js/jquery_md5.js?<?php echo $VERSION ?>"></script>
<script data-cfasync="false" type="text/javascript" src="./js/winapp.js?<?php echo $VERSION ?>"></script>
<script data-cfasync="false" type="text/javascript" src="./js/modernizr.js?<?php echo $VERSION ?>"></script>

<script data-cfasync="false" type="text/javascript">
<!--
	"use strict" ;
	//////////////////////////////////////////////////////////////
	/*
	var thisunixtime = parseInt(new Date().getTime().toString().substring(0, 10)) ;
	var expire = thisunixtime + (60*60*24*90) ;

	var name = "cCk" ;
	var value = 1 ;
	var cookie_string = name + "=" + value + "; path=/;" ;

	document.cookie = cookie_string ;
	*/
	//////////////////////////////////////////////////////////////
	var loaded = 1 ;
	var base_url = "." ;
	var embed = 0 ; var mapp = <?php echo $mapp ?> ;
	var screen_ = ( typeof( phplive_wp ) != "undefined" ) ? "separate" : "<?php echo $CONF["screen"] ?>" ;
	var global_menu ;
	var mobile = ( <?php echo $mobile ?> ) ? is_mobile() : 0 ;
	var mapp_login = 0 ; // this method to ensure mapp vars are set
	var mapp_build = 5 ; // exists-5 = Mobile App 5.0, 6 = ...
	var external_url = "" ;
	var forgot_attempts = 0 ;
	var wp = ( ( typeof( window.external ) != "undefined" ) && ('wp_total_visitors' in window.external) ) ? 1 : 0 ;

	var audio_supported = HTML5_audio_support() ;
	var mp3_support = ( typeof( audio_supported["mp3"] ) != "undefined" ) ? 1 : 0 ;
	if ( mobile && !<?php echo $mapp ?> && ( "<?php echo $menu ?>" != "sa" ) ) { location.href = "mapp/mapp_settings.php" ; }

	$(document).ready(function()
	{
		$("html").css({'background': '#F4F6F8'}) ; $("body").css({'background': '#F4F6F8'}) ;

		check_protocol() ;

		$("body").show() ;
		init_menu() ;

		toggle_menu( "<?php echo $menu ?>" ) ;
		wp_total_visitors(0) ;

		<?php
			if ( $action ) { print "update_open_status( $open_status ) ;" ; }

			if ( $md5_password )
			{
				print "
				$('#phplive_password').val( '$md5_password' ) ;
				$('#phplive_login').val( '$opinfo_[login]' ) ;
				$('#ses').val( '$auto_login_token_ses' ) ; mapp_login = 1 ; if ( !mobile ) { $('#theform').submit() ; }
				" ;
			}
			else if ( ( $action === "submit" ) && ( $menu == "operator" ) && !$error )
			{
				if ( $reload )
				{
					print "$('#div_reload').show() ;" ;
					print "setTimeout( function(){ $('#theform').submit() ; }, 15000 ) ;" ;
				}
				else
				{
					// play_sound( 0, \"login_op\", \"new_request_$opinfo[sound1]\" ) ;
					print "input_disable() ; $('#btn_login').attr('disabled', true).html('SIGN IN <img src=\"pics/loading_fb.gif\" width=\"16\" height=\"11\" border=\"0\" alt=\"\"> ') ; document.title = \"-- Chat Operator Login --\" ; " ;
					if ( ( $wp || $auto ) && !$pr_query )
						print "setTimeout( function(){ location.href='ops/operator.php?auto=$auto&wp=$wp&mapp=$mapp&$now' ; }, 1500 ) ;" ;
					else
						print "setTimeout( function(){ location.href='ops/index.php?auto=$auto&wp=$wp&$pr_query$now' ; }, 1500 ) ;" ;
				}
			}
			else if ( $password_temp )
			{
				print " $('#phplive_password_temp').val( \"$password_temp\" ) ; do_login() ; " ;
			}
			if ( $CONF["screen"] == "same" ) { print "if ( !wp ) { $('#div_menus').show() ; }" ; }
		?>
		if ( <?php echo $mapp ?> ) { init_external_url() ; }
		if ( wp ) { $('#chat_text_version').hide() ; $('#chat_text_powered').hide() ; }
		<?php if ( is_file( "./inc_login_extra.php" ) ){ include_once( "./inc_login_extra.php" ) ; } ?>

		<?php
			if ( preg_match( "/inactive/i", $error ) )
				print "$('#href_forgot_wrapper').hide() ; $('#div_alert_inactive').show() ; " ;
			else if ( $error )
				print "do_alert( 0, '$error' ) ; " ;
		?>

	});

	function init_remember_checkbox()
	{
		if ( $( "#remember" ).prop( "checked" ) )
		{
			$( "#remember" ).prop( "checked", false ) ;
		}
		else
		{
			$( "#remember" ).prop( "checked", true ) ;
		}
	}

	function init_external_url()
	{
		$("a").click(function(){
			var temp_url = $(this).attr( "href" ) ;
			if ( !temp_url.match( /javascript/i ) )
			{
				external_url = temp_url ;
				return false ;
			}
		});
	}

	function check_protocol()
	{
		var base_url = "<?php echo $base_url ?>" ;
		var url = window.location.href ;
		var base_url_https = ( base_url.match( /^https:/i ) ) ? 1 : 0 ;
		var base_url_toggle = ( base_url.match( /^\//i ) ) ? 1 : 0 ; // one slash for absolute path (/phplive)
		var url_https = ( url.match( /^https:/i ) ) ? 1 : 0 ;
		var path_relative = ( "<?php echo $host_base ?>" == "" ) ? 1 : 0 ;

		if ( base_url_https && !url_https && !base_url_toggle && !path_relative )
		{
			location.href = base_url+"/<?php echo ( $query ) ? "?auto=$auto&from=$from&mapp=$mapp&menu=$menu&wp=$wp&platform=$platform&arn=$arn" : "" ; ?>" ;
		}
		else if ( !base_url_https && url_https && !base_url_toggle && !path_relative )
		{
			base_url = base_url.replace( /^https:/g, "http:" ) ;
			location.href = base_url+"/<?php echo ( $query ) ? "?auto=$auto&from=$from&mapp=$mapp&menu=$menu&wp=$wp&platform=$platform&arn=$arn&action=secure" : "?action=secure" ; ?>" ;
		}
		else if ( url_https && base_url_toggle && !path_relative )
		{
			base_url = base_url.replace( /^\//g, "https:/" ) ;
			location.href = base_url+"/<?php echo ( $query ) ? "?auto=$auto&from=$from&mapp=$mapp&menu=$menu&wp=$wp&platform=$platform&arn=$arn&action=secure" : "?action=secure" ; ?>" ;
		}
		else
		{
			// double check URL match to limit cross-domain issues
			<?php if ( ( $lc < 2 ) && $host_host && $host_base && ( $host_host != $host_base ) ): ++$lc ; ?>

			if ( base_url_toggle )
				location.href = window.location.protocol+base_url+"/<?php echo ( $query ) ? "?auto=$auto&from=$from&mapp=$mapp&menu=$menu&wp=$wp&platform=$platform&arn=$arn&lc=$lc" : "?lc=$lc" ; ?>" ;
			else
				location.href = base_url+"/<?php echo ( $query ) ? "?auto=$auto&from=$from&mapp=$mapp&menu=$menu&wp=$wp&platform=$platform&arn=$arn&lc=$lc" : "?lc=$lc" ; ?>" ;

			<?php endif ; ?>
		} return true ;
	}

	function toggle_menu( themenu )
	{
		toggle_forgot(0) ;

		global_menu = themenu ;

		$('#div_forgot_error_op').hide() ;

		if ( themenu == "sa" )
		{
			$('#div_remember').hide() ;
			$('#btn_login_forgot').html( "Reset Setup Password" ) ;
			$('#href_forgot').html( "forgot setup admin password" ) ;
			$('#menu_operator').removeClass('info_menu_focus').addClass('info_menu_blank') ;
			$('#menu_sa').removeClass('info_menu_blank').addClass('info_menu_focus') ;
			$('#radio_login_sa').prop('checked', true) ;

			$('#div_info_operator').hide() ; $('#div_info_setup').show() ; $('#div_op_status').hide() ;
			if ( screen_ == "same" ) { }
			$('#copyright').show() ;
		}
		else
		{
			$('#div_remember').show() ;
			$('#btn_login_forgot').html( "Reset Operator Password" ) ;
			$('#href_forgot').html( "forgot operator password" ) ;
			$('#menu_sa').removeClass('info_menu_focus').addClass('info_menu_blank') ;
			$('#menu_operator').removeClass('info_menu_blank').addClass('info_menu_focus') ;
			$('#radio_login_operator').prop('checked', true) ;

			$('#div_info_setup').hide() ; $('#div_info_operator').show() ;  $('#div_op_status').show() ;
			if ( screen_ == "same" ) { $('#copyright').show() ; }
		}
		$('#phplive_login').val( "<?php echo ( $login ) ? $login : "" ?>" ) ;

		if ( !mapp && !mobile ) { $('#phplive_login').focus() ; }
		$('#menu').val( themenu ) ;
	}

	function do_login()
	{
		$('#div_alert_inactive').hide() ;

		if ( $('#phplive_login').val() == "" )
			do_alert( 0, "Blank login is invalid." ) ;
		else if ( $('#phplive_password_temp').val() == "" )
			do_alert( 0, "Blank password is invalid." ) ;
		else
		{
			var password = $('#phplive_password_temp').val().trim() ;
			var password_reset = ( password.indexOf("phplive") == 0 ) ? 1 : 0 ;
			var md5_password = phplive_md5( phplive_md5( password )+"<?php echo $token_pass ?>" ) ;
			if ( <?php echo $addon_ldap ?> ) { $('#phplive_password_ldap').val( encodeURIComponent( password ) ) ; }

			$('#phplive_password_temp').val( "" ) ;
			$('#phplive_password').val( md5_password ) ;
			$('#phplive_password_reset').val( password_reset ) ;
			$('#btn_login').css({'opacity': 0.5}).attr("disabled", true) ;
			$('#theform').submit() ;
		}
	}

	function do_forgot()
	{
		var json_data = new Object ;
		var unique = unixtime() ;
		var login = $('#phplive_login').val() ;

		if ( !login )
			do_alert( 0, "Please provide the Login." ) ;
		else
		{
			$('#div_forgot_error_op').hide() ;
			$('#btn_login_forgot').css({'opacity': 0.5}).attr("disabled", true) ;

			$.ajax({
			type: "POST",
			url: "./index.php",
			data: "action=reset_password&menu="+global_menu+"&phplive_login="+login+"&unique="+unique,
			success: function(data){
				eval( data ) ;

				if ( json_data.status )
				{
					forgot_attempts = 0 ;

					$('#email_partial').html( json_data.email_partial ) ;
					do_alert_div( ".", 1, json_data.message ) ;
				}
				else
				{
					++forgot_attempts ;
					if ( json_data.error.match( /inactive/ ) )
						do_alert_div( ".", 0, json_data.error ) ;
					else if( json_data.error.match( /LDAP/ ) )
						do_alert_div( ".", 0, json_data.error ) ;
					else
					{
						if ( ( forgot_attempts > 5 ) && ( global_menu != "sa" ) )
							$('#div_forgot_error_op').show() ;
						else
							do_alert( 0, json_data.error ) ;
					}
					setTimeout( function(){ $('#btn_login_forgot').css({'opacity': 1}).attr("disabled", false) ; }, 5000 ) ;
				}
			},
			error:function (xhr, ajaxOptions, thrownError){
				do_alert( 0, "Error processing reset password.  Please refresh the page and try again." ) ;
			} });
		}
	}

	function input_disable()
	{
		$("#theform :input").attr("disabled", true) ;
	}

	function input_text_listen( e )
	{
		var key = -1 ;
		var shift ;

		key = e.keyCode ;
		shift = e.shiftKey ;

		if ( !shift && ( ( key == 13 ) || ( key == 10 ) ) )
		{
			e.preventDefault ? e.preventDefault() : (e.returnValue = false) ;
			do_login() ;
		}
	}

	function toggle_forgot( theflag )
	{
		$('#btn_login_forgot').attr("disabled", false) ;
		$('#div_alert').hide() ; $('#div_alert_inactive').hide() ;
		if ( !mapp && !mobile ) { $('#phplive_login').focus() ; }
		
		if ( theflag )
		{
			$('#div_tr_password').hide() ;
			$('#div_btn_submit').hide() ;
			$('#div_btn_forgot').show() ;
			$('#div_op_status').hide() ;
			$('#div_remember').hide() ;
		}
		else
		{
			$('#div_tr_password').show() ;
			$('#div_btn_forgot').hide() ;
			$('#div_btn_submit').show() ;
			if ( global_menu == "sa" ) { $('#div_op_status').hide() ; }
			else { $('#div_op_status').show() ; $('#div_remember').show() ; }
		}
	}

	function update_open_status( theflag )
	{
		$('#open_status_'+theflag).prop('checked', true) ;
	}
//-->
</script>
</head>
<body style="display: none; overflow: auto;">

<div id="body" style="padding-bottom: 20px;">
	<div style="width: 100%; padding-top: 25px;">
		<div style="<?php echo ( $mapp ) ? "display: none;" : "" ?> width: 280px; margin: 0 auto;">
			<div style="font-size: 14px; color: #ABB7C2; text-align: center;" id="chat_text_version"><?php if ( isset( $CONF["KEY"] ) && ( $CONF["KEY"] == md5($KEY."_key_phplive") ) ): ?>Live Chat Solution<?php else: ?>PHP Live! Support<?php endif ; ?> v.<?php echo $VERSION ?></div>
		</div>

		<div style="width: 280px; height: 370px; margin: 0 auto; margin-top: 20px; padding: 10px; box-shadow: 5px 5px 25px #D9DDE0;" id="div_login" class="info_white">

			<div style="display: none; margin-bottom: 10px;" id="div_menus">
				<table cellspacing=0 cellpadding=0 border=0 width="100%">
				<tr>
					<td width="50%" style="padding-right: 5px; text-align: center;"><div class="info_neutral" id="menu_operator" onClick="toggle_menu('operator')" style="padding: 10px; cursor: pointer;"><input type="radio" name="radio_login" id="radio_login_operator"> Chat Operator</div></td>
					<td width="50%" style="padding-left: 5px; text-align: center;"><div class="info_neutral" id="menu_sa" onClick="toggle_menu('sa')" style="padding: 10px; cursor: pointer;"><input type="radio" name="radio_login" id="radio_login_sa"> Setup Admin</div></td>
				</tr>
				</table>
			</div>

			<form method="POST" action="index.php" id="theform">
			<input type="hidden" name="action" value="submit">
			<input type="hidden" name="auto" id="auto" value="<?php echo $auto ?>">
			<input type="hidden" name="wp" value="<?php echo $wp ?>">
			<input type="hidden" name="mapp" id="mapp" value="<?php echo $mapp ?>">
			<input type="hidden" name="platform" id="platform" value="">
			<input type="hidden" name="arn" id="arn" value="">
			<input type="hidden" name="menu" id="menu" value="">
			<input type="hidden" name="wpress" id="wpress" value="<?php echo $wpress ?>">
			<input type="hidden" name="phplive_password" id="phplive_password" value="">
			<input type="hidden" name="phplive_password_reset" id="phplive_password_reset" value="">
			<input type="hidden" name="phplive_password_ldap" id="phplive_password_ldap" value="">
			<table cellspacing=0 cellpadding=5 border=0 style="width: 100%;">
			<tr>
				<td colspan="2">
					<div style="padding-bottom: 10px;">
						<div id="div_info_operator" style="display: none; padding: 10px; text-align: center;" class="info_blue"><img src="pics/icons/user_big.png" width="16" height="16" border="0" alt=""> Chat Operator</div>
						<div id="div_info_setup" style="display: none; padding: 10px; text-align: center;" class="info_blue"><img src="pics/icons/settings_big.png" width="16" height="16" border="0" alt=""> Setup Admin</div>
					</div>
				</td>
			</tr>
			<tr>
				<td width="60"><span id="div_txt_login">Login</span></td>
				<td> <input type="text" class="input" name="phplive_login" id="phplive_login" size="15" maxlength="60" value="<?php echo ( $login ) ? $login : "" ?>" onKeyup="input_text_listen(event);" autocomplete="off" style="outline: none;"></td>
			</tr>
			<tr id="div_tr_password">
				<td width="60">Password</td>
				<td> <input type="password" class="input" name="phplive_password_temp" id="phplive_password_temp" size="15" value="<?php echo ( isset( $password ) && $reload ) ? $password : "" ; ?>" onKeyup="input_text_listen(event);" autocomplete="off" style="outline: none;"></td>
			</tr>
			<tr>
				<td colspan=2><div style="padding-top: 5px; text-align: center;" id="div_remember"><input type="checkbox" name="remember" id="remember" value="1" <?php echo ( $remember ) ? "checked" : "" ; ?>> <span onClick="init_remember_checkbox();" style="cursor: pointer;">(Remember me) For future sessions, login automatically on this <?php echo ( $mapp ) ? "device" : "browser" ; ?>.</span></div></td>
			</tr>
			<tr>
				<td colspan=3>
					<div id="div_alert_inactive" style="display: none;" class="info_error"><?php echo $error ?></div>
					<div id="div_btn_submit" style="padding-top: 15px;">
						<button type="button" id="btn_login" onClick="do_login()" class="btn round" style="width: 100%; height: 45px; padding: 6px; font-size: 14px; font-weight: bold; background: #8BCF92; border: 1px solid #7FBD85; color: #FFFFFF; cursor: pointer;">SIGN IN</button>
						<div style="margin-top: 25px; text-align: center;" id="href_forgot_wrapper"><a href="JavaScript:void(0)" onClick="toggle_forgot(1)" id="href_forgot"></a></div>
					</div>
					<div id="div_btn_forgot" style="display: none; margin-top: 10px;">
						<div class="info_error" style="display: none; margin-top: 5px; text-shadow: none;" id="div_forgot_error_op">Please contact the Setup Admin to reset your login credentials.</div>
						<div id="div_alert" style="margin-top: 5px; text-shadow: none;"></div>
						<div style="margin-top: 15px;">
							<div><button type="button" id="btn_login_forgot" onClick="do_forgot()" class="btn round" style="width: 100%; height: 45px; padding: 6px; font-size: 14px; font-weight: bold; background: #8BCF92; border: 1px solid #7FBD85; color: #FFFFFF; cursor: pointer;"></button></div>
							<div style="margin-top: 25px; text-align: center;"><a href="JavaScript:void(0)" onClick="toggle_forgot(0)">back to login</a></div>
						</div>
					</div>
				</td>
			</tr>
			</table>
			</form>

			<div id="div_sounds_login_op" style="width: 1px; height: 1px; overflow: hidden; opacity:0.0; filter: alpha(opacity=0);"></div>
			<audio id='div_sounds_audio_login_op'></audio>

		</div>
		<div style="padding-top: 15px;">
			<div id="chat_text_powered" style="width: 280px; margin: 0 auto; font-size: 10px; text-align: center; opacity:0.5; filter:alpha(opacity=50);">
				<?php if ( ( isset( $CONF["KEY"] ) && ( $CONF["KEY"] == md5($KEY."_key_phplive") ) ) || $mapp ): ?><?php else: ?>powered by <a href="https://www.phplivesupport.com/?plk=pi-23-78m-m" target="_blank" style="letter-spacing: .8px;">PHP Live!</a><?php endif ; ?>
			</div>
		</div>

	</div><?php if ( $mapp ) { include_once( "./mapp/inc_footer.php" ) ; } ?>
</div>

<div id="div_reload" style="display: none; position: absolute; top: 0px; left: 0px; width: 100%; height: 2000px; background: url( ./pics/bg_trans_white.png ) repeat; overflow: hidden; z-index: 20;">
	<div style="padding: 15px;">loading... <img src="pics/loading_fb.gif" width="16" height="11" border="0" alt=""></div>
</div>

<div id="div_password" style="display: none; position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; padding-top: 80px; z-index: 50; background: url(./pics/bg_trans_white.png) repeat;">
	<div class="info_info" style="width: 300px; height: 250px; margin: 0 auto; padding: 10px; text-shadow: 1px 1px #FFFFFF;">
		<div class="edit_title">Your new password for <span style="color: #ED933F;"><?php echo $login ?></span> is:</div>
		<div class="edit_title" style="margin-top: 25px; color: #53BA4B;"></div>
		<div style="margin-top: 25px;">Write the password down.  It will not be visible again once this window is closed.</div>
		<div style="margin-top: 25px;"><button type="button" onClick="$('#div_password').hide();" class="btn">Close Window and Login</button></div>
	</div>
</div>

</body>
</html>
<?php
	if ( isset( $dbh ) && isset( $dbh['con'] ) )
		database_mysql_close( $dbh ) ;
?>
