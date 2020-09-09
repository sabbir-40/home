<?php
	/***************************************/
	//
	//
	// PHP Live! Support
	//
	// https://www.phplivesupport.com
	//
	/***************************************/
	// STANDARD header for Setup
	if ( !is_file( "../web/config.php" ) ){ HEADER("location: ../setup/install.php") ; exit ; }
	include_once( "../web/config.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Format.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Error.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/".Util_Format_Sanatize($CONF["SQLTYPE"], "ln") ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Security.php" ) ;
	if ( !$opinfo = Util_Security_AuthOp( $dbh ) ){ ErrorHandler( 602, "Invalid operator session or session has expired.", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	// STANDARD header end
	/****************************************/

	include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get.php" ) ;

	$action = Util_Format_Sanatize( Util_Format_GetVar( "action" ), "ln" ) ;
	$jump = Util_Format_Sanatize( Util_Format_GetVar( "jump" ), "ln" ) ; if ( !$jump ) { $jump = "themes" ; }
	$error = "" ;

	$op_sounds = ( isset( $VALS["op_sounds"] ) && $VALS["op_sounds"] ) ? unserialize( $VALS["op_sounds"] ) : Array() ;
	if ( isset( $op_sounds[$opinfo["opID"]] ) ) { $op_sounds_vals = $op_sounds[$opinfo["opID"]] ; $opinfo["sound1"] = $op_sounds_vals[0] ; $opinfo["sound2"] = $op_sounds_vals[1] ; } else { $opinfo["sound1"] = "default" ; $opinfo["sound2"] = "default" ; }

	if ( $action === "update_theme" )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/update.php" ) ;
		$theme = Util_Format_Sanatize( Util_Format_GetVar( "theme" ), "ln" ) ;

		if ( !Ops_update_OpValue( $dbh, $opinfo["opID"], "theme", $theme ) )
			$error = "Error in updating theme." ;
		else
			$opinfo["theme"] = $theme ;
	}
	else if ( $action === "update_sound" )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Vals.php" ) ;
		$sound1 = Util_Format_Sanatize( Util_Format_GetVar( "sound1" ), "ln" ) ;
		$sound2 = Util_Format_Sanatize( Util_Format_GetVar( "sound2" ), "ln" ) ;

		$op_sounds[$opinfo["opID"]] = Array( $sound1, $sound2 ) ;
		Util_Vals_WriteToFile( "op_sounds", serialize( $op_sounds ) ) ;
		$opinfo["sound1"] = $sound1 ; $opinfo["sound2"] = $sound2 ;
		
		$jump = "sounds" ;
	}
	else
		$error = "invalid action" ;

	$push_repeat = 0 ;
	$mapp_array = ( isset( $VALS["MAPP"] ) && $VALS["MAPP"] ) ? unserialize( $VALS["MAPP"] ) : Array() ;
	if ( isset( $mapp_array[$opinfo["opID"]] ) )
		$push_repeat = isset( $mapp_array[$opinfo["opID"]]["r"]  ) ? intVal( $mapp_array[$opinfo["opID"]]["r"] ) : 0 ;

	$theme = $opinfo["theme"] ;
	if ( !is_file( "$CONF[DOCUMENT_ROOT]/themes/$theme/style.css" ) || isset( $THEMES_EXCLUDE[$theme] ) ) { $theme = "default" ; }
?>
<?php include_once( "../inc_doctype.php" ) ?>
<head>
<title> Operator </title>

<meta name="description" content="v.<?php echo $VERSION ?>">
<meta name="keywords" content="<?php echo md5( $KEY ) ?>">
<meta name="robots" content="all,index,follow">
<meta http-equiv="content-type" content="text/html; CHARSET=utf-8"> 
<?php include_once( "../inc_meta_dev.php" ) ; ?>

<link rel="Stylesheet" href="../themes/<?php echo $theme ?>/style.css?<?php echo filemtime ( "../themes/$theme/style.css" ) ; ?>">
<link rel="Stylesheet" href="../mapp/css/mapp.css?<?php echo $VERSION ?>">
<script data-cfasync="false" type="text/javascript" src="../mapp/js/mapp.js?<?php echo $VERSION ?>"></script>
<script data-cfasync="false" type="text/javascript" src="../js/global.js?<?php echo $VERSION ?>"></script>
<script data-cfasync="false" type="text/javascript" src="../js/framework.js?<?php echo $VERSION ?>"></script>
<script data-cfasync="false" type="text/javascript" src="../js/modernizr.js?<?php echo $VERSION ?>"></script>

<script data-cfasync="false" type="text/javascript">
<!--
	"use strict" ;
	var base_url = ".." ;
	var mobile = 1 ;
	var mapp = 1 ;
	var sound_volume = parent.sound_volume ;
	var push_repeat = <?php echo $push_repeat ?> ;

	var theme = "<?php echo $theme ?>" ;
	var audio_supported = HTML5_audio_support() ;
	var mp3_support = ( typeof( audio_supported["mp3"] ) != "undefined" ) ? 1 : 0 ;

	$(document).ready(function()
	{
		reset_mapp_div_height() ;

		toggle_menu_info( "<?php echo $jump ?>" ) ;

		<?php if ( $action && !$error ): ?>do_alert( 1, "Update Success" ) ;<?php endif ; ?>

		if ( ( typeof( parent.isop ) != "undefined" ) && ( ( "<?php echo $action ?>" == "update_theme" ) || ( "<?php echo $action ?>" == "update_sound" ) ) )
		{
			// need to refresh the console because mobile does not process real-time theme change
			parent.refresh_console(0) ;
		}

		if ( parent.chat_sound ) { $('#r_sound_1').prop('checked', true) ; }
		else { $('#r_sound_0').prop('checked', true) ; }

		parent.init_extra_loaded() ;
	});

	function scroll_top()
	{
		$('#canned_container').animate({
			scrollTop: 0
		}, 200);
	}

	function toggle_menu_info( themenu )
	{
		var divs = Array( "themes", "sounds", "repeat" ) ;

		for ( var c = 0; c < divs.length; ++c )
		{
			$('#div_settings_'+divs[c]).hide() ;
			$('#menu_settings_'+divs[c]).removeClass('menu_traffic_info_focus').addClass('menu_traffic_info') ;
		}

		demo_sound1(0) ;
		if ( themenu == "sounds" ) { toggle_sound_form( parent.chat_sound ) ; }

		$('#div_settings_'+themenu).show() ;
		$('#menu_settings_'+themenu).removeClass('menu_traffic_info').addClass('menu_traffic_info_focus') ;
	}

	function confirm_theme( thetheme, thethumb )
	{
		if ( theme != thetheme )
		{
			$('#theme_'+thetheme).prop('checked', true) ;
			$('#div_theme_thumb').html( "<div style=\"background: url( "+thethumb+" ); background-position: top left; width: 85px; height: 54px; border-radius: 5px;\">&nbsp;</div>") ;
			$('#div_confirm').show() ;
		}
	}

	function update_theme( thetheme )
	{
		location.href = 'mapp_themes.php?action=update_theme&theme='+thetheme ;
	}

	function update_theme_pre( theflag )
	{
		if ( theflag )
		{
			var theme = $('input:radio[name=theme]:checked').val() ;
			update_theme( theme ) ;
		}
		else
		{
			$('#theme_<?php echo $theme ?>').prop('checked', true) ;
			$('#div_confirm').hide() ;
		}
	}

	function demo_sound1( theflag )
	{
		var sound = $('#sound1').val() ;

		clear_sound('new_request') ;
		if ( theflag )
			play_sound(1, 'new_request', 'new_request_'+sound) ;
	}

	function demo_sound2()
	{
		var sound = $('#sound2').val() ;

		clear_sound('new_request') ;
		play_sound(0, 'new_text', 'new_text_'+sound) ;
	}

	function update_sound_doit()
	{
		var sound1 = $('#sound1').val() ;
		var sound2 = $('#sound2').val() ;

		location.href = 'mapp_themes.php?action=update_sound&sound1='+sound1+'&sound2='+sound2 ;
	}

	function update_sound( thevalue )
	{
		if ( thevalue != parent.chat_sound )
		{
			var json_data = new Object ;

			$.ajax({
				type: "POST",
				url: "../ajax/chat_actions_op_ext.php",
				data: "action=console_sound_mapp&value="+thevalue+"&"+unixtime(),
				success: function(data){
					eval(data) ;

					if ( json_data.status )
					{
						parent.chat_sound = thevalue ;
						if ( thevalue ) { $('#r_sound_1').prop('checked', true) ; }
						else { $('#r_sound_0').prop('checked', true) ; }

						toggle_sound_form( thevalue ) ;
						parent.refresh_console(0) ;
					}
					else
						do_alert( 0, "[m] Error: Could not update value.  Please try again." ) ;
				}
			});
		}
	}

	function toggle_sound_form( thevalue )
	{
		if ( thevalue ) { $('#div_settings_sounds_form').show() ; }
		else { $('#div_settings_sounds_form').hide() ; }
	}

	function update_push_repeat( thevalue )
	{
		if ( thevalue != push_repeat )
		{
			var json_data = new Object ;

			$.ajax({
				type: "POST",
				url: "../ajax/chat_actions_op_ext.php",
				data: "action=push_repeat_mapp&value="+thevalue+"&"+unixtime(),
				success: function(data){
					eval(data) ;

					if ( json_data.status )
					{
						push_repeat = thevalue ;
						if ( thevalue ) { $('#r_push_repeat_1').prop('checked', true) ; }
						else { $('#r_push_repeat_0').prop('checked', true) ; }

						do_alert( 1, "Update Success" ) ;
					}
					else
						do_alert( 0, "[m] Error: Could not update repeat value.  Please try again." ) ;
				}
			});
		}
	}
//-->
</script>
</head>
<body style="-webkit-text-size-adjust: 100%;">

<div id="canned_container" style="padding: 15px; padding-top: 25px; height: 200px; overflow: auto;">

	<div style="">
		<div id="menu_settings_themes" class="menu_traffic_info_focus" onClick="toggle_menu_info('themes')">Themes</div>
		<div id="menu_settings_sounds" class="menu_traffic_info" onClick="toggle_menu_info('sounds')">Sounds</div>
		<div id="menu_settings_repeat" class="menu_traffic_info" onClick="toggle_menu_info('repeat')">Push Repeat</div>
		<div style="clear: both;"></div>
	</div>

	<div style="margin-top: 25px;">
		<div id="div_settings_themes" style="display: none; padding-bottom: 50px;">
			<form>
			<table cellspacing=0 cellpadding=2 border=0 width="100%" style="">
			<tr>
				<td>
					<?php
						$dir_themes = opendir( "$CONF[DOCUMENT_ROOT]/themes/" ) ;

						$themes = Array() ;
						while ( $theme_temp = readdir( $dir_themes ) )
							$themes[] = $theme_temp ;
						closedir( $dir_themes ) ;

						sort( $themes, SORT_STRING ) ;
						for ( $c = 0; $c < count( $themes ); ++$c )
						{
							$theme_temp = $themes[$c] ;
							$theme_display = ( strlen( $theme_temp ) > 9 ) ? substr( $theme_temp, 0, 8 )."..." : $theme_temp ;

							$checked = "" ;
							if ( $theme_temp == $theme )
								$checked = "checked" ;

							$path_thumb = ( is_file( "../themes/$theme_temp/thumb.png" ) ) ? "../themes/$theme_temp/thumb.png" : "../pics/screens/thumb_theme_blank.png" ;

							if ( preg_match( "/[a-z]/i", $theme_temp ) && !preg_match( "/^\./", $theme_temp ) && ( $theme_temp != "initiate" ) && !isset( $THEMES_EXCLUDE[$theme_temp] ) )
								print "<div class=\"li_mapp round\" style=\"width: 85px; margin-bottom: 15px;\"><div style=\"background: url( $path_thumb ); background-position: top left; height: 54px; border-radius: 5px; cursor: pointer;\" onClick=\"confirm_theme('$theme_temp', '$path_thumb')\"><input type=\"radio\" name=\"theme\" id=\"theme_$theme_temp\" value=\"$theme_temp\" $checked> <span class=\"info_mapp_neutral\" style=\"text-shadow: none;\">$theme_display</span></div></div>" ;
						}
					?>
					<div style="clear: both;"></div>
				</td>
			</tr>
			</table>
			</form>
		</div>
		<div id="div_settings_sounds" style="display: none; padding-bottom: 50px;">

			<div style="">
				<div>
					<div class="info_mapp_good" style="float: left; width: 100px; cursor: pointer;" onClick="update_sound(1)"><input type="radio" name="r_sound" id="r_sound_1" value=1> Sound On</div>
					<div class="info_mapp_error" style="float: left; margin-left: 10px; width: 100px; cursor: pointer;" onClick="update_sound(0)"><input type="radio" name="r_sound" id="r_sound_0" value=0> Sound Off</div>
				</div>
				<div style="clear: both;"></div>
			</div>

			<div id="div_settings_sounds_form" style="display: none; margin-top: 35px;">
				<form>
				New chat request:
				<div>
					<table cellspacing=0 cellpadding=2 border=0 style="">
					<tr>
						<td class="chat_info_td_traffic"><div style=""><select name="sound1" id="sound1" style="" onChange="demo_sound1(1)">
							<?php
								$dir_sounds = opendir( "$CONF[DOCUMENT_ROOT]/media/" ) ;

								$sounds = $sounds_filter = Array() ;
								while ( $sound = readdir( $dir_sounds ) )
									$sounds[] = $sound ;
								closedir( $dir_sounds ) ;
								
								sort( $sounds, SORT_STRING ) ;
								for ( $c = 0; $c < count( $sounds ); ++$c )
								{
									$sound = $sounds[$c] ;

									if ( preg_match( "/[a-z]/i", $sound ) && preg_match( "/^new_request_/i", $sound ) )
									{
										$sound_temp = preg_replace( "/(new_request_)|(.swf)|(.mp3)/", "", $sound ) ;
										if ( !isset( $sounds_filter[$sound_temp] ) )
										{
											$sounds_filter[$sound_temp] = 1 ;
											$sound_display = ucwords( preg_replace( "/_/", " ", $sound_temp ) ) ;
											$selected = "" ;
											if ( $opinfo["sound1"] == $sound_temp )
												$selected = "selected" ;

											print "<option value=\"$sound_temp\" $selected>$sound_display</option>" ;
										}
									}
								}
							?>
							</select></div>
						</td>
						<td class="chat_info_td_traffic"><span style="cursor: pointer;" onClick="demo_sound1(1)" class="info_neutral">play sound</span></td>
						<td class="chat_info_td_traffic"><span style="cursor: pointer;" onClick="demo_sound1(0)" class="info_neutral">stop sound</span></td>
					</tr>
					</table>
				</div>

				<div style="margin-top: 25px;">New chat response:</div>
				<div>
					<table cellspacing=0 cellpadding=2 border=0 style="">
					<tr>
						<td class="chat_info_td_traffic"><div style="padding-top: 5px;"><select name="sound2" id="sound2" style="" onChange="demo_sound2()">
							<?php
								$dir_sounds = opendir( "$CONF[DOCUMENT_ROOT]/media/" ) ;

								$sounds = $sounds_filter = Array() ;
								while ( $sound = readdir( $dir_sounds ) )
									$sounds[] = $sound ;
								closedir( $dir_sounds ) ;

								sort( $sounds, SORT_STRING ) ;
								for ( $c = 0; $c < count( $sounds ); ++$c )
								{
									$sound = $sounds[$c] ;

									if ( preg_match( "/[a-z]/i", $sound ) && preg_match( "/^new_text_/i", $sound ) )
									{
										$sound_temp = preg_replace( "/(new_text_)|(.swf)|(.mp3)/", "", $sound ) ;
										if ( !isset( $sounds_filter[$sound_temp] ) && ( $sound_temp != "default" ) && ( $sound_temp != "return" ) && ( $sound_temp != "sound_check" ) )
										{
											$sounds_filter[$sound_temp] = 1 ;
											$sound_display = ucwords( preg_replace( "/_/", " ", $sound_temp ) ) ;
											$selected = "" ;
											if ( $opinfo["sound2"] == $sound_temp )
												$selected = "selected" ;

											print "<option value=\"$sound_temp\" $selected>$sound_display</option>" ;
										}
									}
								}
							?>
							</select></div>
						</td>
						<td class="chat_info_td_traffic" style="padding-left: 15px;"><span style="cursor: pointer;" onClick="demo_sound2()" class="info_neutral">play sound</span></td>
						<td class="chat_info_td_traffic">&nbsp;</td>
					</tr>
					</table>
				</div>

				<div style="padding-top: 35px;"><button type="button" onClick="update_sound_doit()" class="input_op_button" style="padding: 10px;">Update Sound Alerts</button></div>
				</form>
			</div>

		</div>
		<div id="div_settings_repeat" style="display: none; padding-bottom: 50px;">
			Send repeat push notification until the chat request has been accepted or declined or the chat request times out.  The push repeat is processed every 5-10 seconds interval.
			<div style="margin-top: 15px;" class="info_neutral">
				<table cellspacing=0 cellpadding=0 border=0>
				<tr>
					<td width="16"><img src="../pics/icons/warning.png" width="16" height="16" border="0" alt=""></td>
					<td style="padding-left: 5px;">One time push notification will alert for operator-to-operator chat invites and transferred chats, but push repeat will not be available for those two situations.  <b>Push repeat feature is for visitor new chat requests.</b></td>
				</tr>
				</table>
			</div>
			<div style="margin-top: 15px;">
				<div class="info_mapp_good" style="float: left; width: 100px; cursor: pointer;" onClick="update_push_repeat(1)"><input type="radio" name="r_push_repeat" id="r_push_repeat_1" value=1 <?php echo ( $push_repeat ) ? "checked" : "" ; ?> > On</div>
				<div class="info_mapp_error" style="float: left; margin-left: 10px; width: 100px; cursor: pointer;" onClick="update_push_repeat(0)"><input type="radio" name="r_push_repeat" id="r_push_repeat_0" value=0 <?php echo ( !$push_repeat ) ? "checked" : "" ; ?> > Off</div>
			</div>
			<div style="clear: both;"></div>
		</div>
	</div>

</div>

<div id="div_confirm" style="display: none; position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; background: url( ../pics/bg_trans_white.png ) repeat; overflow: hidden; z-index: 20;">
	<div id="div_confirm_body" class="info_info" style="position: relative; width: 350px; margin: 0 auto; top: 100px;">
		<div class="info_box" style="padding: 25px;">
			<table cellspacing=0 cellpadding=0 border=0>
			<tr>
				<td><div id="div_theme_thumb" class="li_mapp round" style="width: 85px; height: 54px;"></div><div class="clear:both;"></div></td>
				<td style="padding-left: 15px;">
					<div id="confirm_title">Select this theme?</div>
					<div style="margin-top: 15px;"><button type="button" onClick="update_theme_pre(1)" class="input_op_button" class="btn">Yes</button> &nbsp; &nbsp; &nbsp; &nbsp; <span style="text-decoration: underline; cursor: pointer;" onClick="update_theme_pre(0)">cancel</span></div>
				</td>
			</tr>
			</table>
		</div>
	</div>
</div>

<div id="sounds" style="display: none; position: absolute; width: 1px; height: 1px; overflow: hidden; opacity:0.0; filter:alpha(opacity=0);">
	<span id="div_sounds_new_request"></span>
	<span id="div_sounds_new_text"></span>
	<audio id='div_sounds_audio_new_request'></audio>
	<audio id='div_sounds_audio_new_text'></audio>
</div>

<?php include_once( "./inc_scrolltop.php" ) ; ?>

</body>
</html>
<?php
	if ( isset( $dbh ) && $dbh['con'] )
		database_mysql_close( $dbh ) ;
?>