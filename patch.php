<?php
	/***************************************/
	//
	//
	// PHP Live! Support
	//
	// https://www.phplivesupport.com
	//
	/***************************************/
	if ( !is_file( "./web/config.php" ) ){ HEADER("location: ./setup/install.php") ; exit ; }
	include_once( "./web/config.php" ) ;

	if ( !isset( $CONF['SQLTYPE'] ) ) { $CONF['SQLTYPE'] = "SQL.php" ; }
	else if ( $CONF['SQLTYPE'] == "mysql" ) { $CONF['SQLTYPE'] = "SQL.php" ; }

	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Format.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Error.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Vals.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/".Util_Format_Sanatize($CONF["SQLTYPE"], "ln") ) ;

	$from = Util_Format_Sanatize( Util_Format_GetVar( "from" ), "ln" ) ;
	$embed = Util_Format_Sanatize( Util_Format_GetVar( "embed" ), "n" ) ;
	$page_origin = Util_Format_Sanatize( rawurldecode( Util_Format_GetVar( "pgo" ) ), "url" ) ;
	$patch = Util_Format_Sanatize( Util_Format_GetVar( "patch" ), "n" ) ;
	$patch_c = Util_Format_Sanatize( Util_Format_GetVar( "patch_c" ), "n" ) ;
	$patched = 0 ;
	$loopy = Util_Format_Sanatize( Util_Format_GetVar( "loopy" ), "n" ) ;
	$query = isset( $_SERVER["QUERY_STRING"] ) ? Util_Format_Sanatize( $_SERVER["QUERY_STRING"], "query" ) : "" ;

	// basic check for permissions and gather ini settings
	$ini_open_basedir = ini_get("open_basedir") ;
	$ini_safe_mode = ini_get("safe_mode") ;
	$safe_mode = preg_match( "/on/i", $ini_safe_mode ) ? 1 : 0 ;

	if ( !is_file( "$CONF[DOCUMENT_ROOT]/blank.php" ) )
	{ ErrorHandler( 612, "\$CONF[DOCUMENT_ROOT] variable in config.php is invalid.", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	else if ( !is_writeable( "$CONF[CONF_ROOT]/" ) )
	{ ErrorHandler( 609, "Permission denied on web/ directory. ($ini_open_basedir, $ini_safe_mode, $safe_mode)", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	else if ( !is_writeable( "$CONF[CONF_ROOT]/config.php" ) )
	{ ErrorHandler( 609, "Permission denied on web/config.php directory. ($ini_open_basedir, $ini_safe_mode, $safe_mode)", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	else if ( !is_writeable( "$CONF[CONF_ROOT]/VERSION.php" ) )
	{ ErrorHandler( 609, "Permission denied on web/VERSION.php file. ($ini_open_basedir, $ini_safe_mode, $safe_mode)", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	else if ( !is_dir( "$CONF[CONF_ROOT]/patches/" ) )
	{ ErrorHandler( 610, "Patches directory web/patches/ not found. ($ini_open_basedir, $ini_safe_mode, $safe_mode)", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	else if ( !is_writeable( "$CONF[CONF_ROOT]/patches/" ) )
	{ ErrorHandler( 609, "Permission denied on web/patches/ directory. ($ini_open_basedir, $ini_safe_mode, $safe_mode)", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	else if ( !is_writeable( $CONF["CHAT_IO_DIR"] ) )
	{ ErrorHandler( 609, "Permission denied on web/chat_sessions directory. ($ini_open_basedir, $ini_safe_mode, $safe_mode)", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	else if ( !is_writeable( $CONF["TYPE_IO_DIR"] ) )
	{ ErrorHandler( 609, "Permission denied on web/chat_initiate directory. ($ini_open_basedir, $ini_safe_mode, $safe_mode)", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	else if ( !is_writeable( "$CONF[CONF_ROOT]/vals.php" ) )
	{ ErrorHandler( 609, "Permission denied on web/vals.php file. ($ini_open_basedir, $ini_safe_mode, $safe_mode)", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }
	if ( !is_dir( "$CONF[ATTACH_DIR]" ) ) { mkdir( "$CONF[ATTACH_DIR]", 0777 ) ; }

	if ( $from == "chat" )
		$url = "phplive.php?patched=1&".$query ;
	else if ( $from == "embed" )
		$url = "phplive_embed.php?patched=1&".$query ;
	else if ( $from == "setup" )
		$url = "setup/index.php?patched=1&".$query ;
	else
		$url = "index.php?patched=1&".$query ;

	if ( !is_writeable( "$CONF[ATTACH_DIR]/" ) )
	{ ErrorHandler( 609, "Permission denied on web/file_attach/ directory. ($ini_open_basedir, $ini_safe_mode, $safe_mode)", $PHPLIVE_FULLURL, 0, Array() ) ; exit ; }

	$patch_init = true ;
	$patches_array = Array() ;
	include( "$CONF[DOCUMENT_ROOT]/API/Patches/Util_Patches.php" ) ;
	ksort( $patches_array ) ; $total_patches = count( $patches_array ) ;
	if ( isset( $CONF_EXTEND ) ) { $VARS_MYSQL_THROTTLE_PAUSE = 60 ; $VARS_PATCH_INTERVAL_PATCHES_MAX = 20 ; }
	else { $VARS_MYSQL_THROTTLE_PAUSE = 25 ; $VARS_PATCH_INTERVAL_PATCHES_MAX = 3 ; }
	if ( $patch )
	{
		if ( $patch_c <= $patch_v )
		{
			$patch_init = false ;
			$charset_string = ( database_mysql_old( $dbh ) ) ? "" : "CHARACTER SET utf8 COLLATE utf8_general_ci" ;

			$patch_start = $patch_counter = $patch_c ;
			for ( $c = $patch_c; $c < ( $patch_start + $VARS_PATCH_INTERVAL_PATCHES_MAX ); ++$c )
			{
				if ( isset( $patches_array[$c] ) )
				{
					$patches_array[$c] = true ;
					++$patch_counter ;
				}
			}
			if ( $patch_counter == $patch_c ) { $patch_counter +=1 ; }

			include( "$CONF[DOCUMENT_ROOT]/API/Patches/Util_Patches.php" ) ;

			$qc = isset( $dbh['qc'] ) ? $dbh['qc'] : 0 ;
			$json_data = "json_data = { \"status\": 0, \"patch_c\": $patch_counter, \"qc\": $qc };" ;
		}
		else
		{
			$json_data = "json_data = { \"status\": 1 };" ;
		}

		if ( isset( $dbh ) && isset( $dbh['con'] ) ) { database_mysql_close( $dbh ) ; }
		HEADER('Content-Type: text/plain; charset=utf-8') ;
		print $json_data ; exit ;
	}
	else
	{
		$index_file = "$CONF[DOCUMENT_ROOT]/files/index.php" ;
		if ( is_dir( $CONF["CONF_ROOT"] ) && !is_file( "$CONF[CONF_ROOT]/index.php" ) ) { @copy( $index_file, "$CONF[CONF_ROOT]/index.php" ) ; }
		if ( is_dir( $CONF["CHAT_IO_DIR"] ) && !is_file( "$CONF[CHAT_IO_DIR]/index.php" ) ) { @copy( $index_file, "$CONF[CHAT_IO_DIR]/index.php" ) ; }
		if ( is_dir( $CONF["TYPE_IO_DIR"] ) && !is_file( "$CONF[TYPE_IO_DIR]/index.php" ) ) { @copy( $index_file, "$CONF[TYPE_IO_DIR]/index.php" ) ; }
		if ( is_dir( $CONF["ATTACH_DIR"] ) && !is_file( "$CONF[ATTACH_DIR]/index.php" ) ) { @copy( $index_file, "$CONF[ATTACH_DIR]/index.php" ) ; }
		if ( is_dir( $CONF["EXPORT_DIR"] ) &&  !is_file( "$CONF[EXPORT_DIR]/index.php" ) ) { @copy( $index_file, "$CONF[EXPORT_DIR]/index.php" ) ; }
		if ( !is_dir( "./web/patches" ) ) { mkdir( "./web/patches", 0777 ) ; }

		foreach( $patches_array as $this_patch => $flag )
		{
			if ( !$flag )
			{
				$first_patch = $this_patch ;
				break 1 ;
			}
		}
	}
	FUNCTION Util_Functions_itr_is_serialized( $value, &$result = null )
	{
		// FUNCTION SOURCE:
		// https://gist.github.com/cs278/217091
		if (!is_string($value)) { return false; } if ($value === 'b:0;') { $result = false; return true; } $length = strlen($value); $end = ''; switch ($value[0]) { case 's': if ($value[$length - 2] !== '"') { return false; } case 'b': case 'i': case 'd': $end .= ';'; case 'a': case 'O': $end .= '}'; if ($value[1] !== ':') { return false; } switch ($value[2]) { case 0: case 1: case 2: case 3: case 4: case 5: case 6: case 7: case 8: case 9: break; default: return false; } case 'N': $end .= ';'; if ($value[$length - 1] !== $end[0]) { return false; } break; default: return false; } if (($result = @unserialize($value)) === false) { $result = null; return false; } return true;
	}
?>
<?php include_once( "./inc_doctype.php" ) ?>
<head>
<title> PHP Live! Patch (<?php echo $patched ?>) </title>

<meta name="description" content="PHP Live! Support <?php echo $VERSION ?>">
<meta name="keywords" content="powered by: PHP Live!  www.phplivesupport.com">
<meta name="robots" content="all,index,follow">
<meta http-equiv="content-type" content="text/html; CHARSET=utf-8">
<meta name="viewport" content="user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1, width=device-width, height=device-height" />
<?php include_once( "./inc_meta_dev.php" ) ; ?>

<link rel="Stylesheet" href="./css/setup.css?<?php echo $VERSION ?>">
<style>
#image_loading{
	box-shadow: 0 0 0 0 #4490A7;
	transform: scale(1);
	animation: pulse 1s infinite;
}
@keyframes pulse {
	0% {
		transform: scale(0.95);
		box-shadow: 0 0 0 0 #4A9CB5;
	}

	70% {
		transform: scale(1);
		box-shadow: 0 0 0 10px #51ABC6;
	}

	100% {
		transform: scale(0.95);
		box-shadow: 0 0 0 0 #51ABC6;
	}
}
</style>
<script data-cfasync="false" type="text/javascript" src="./js/global.js?<?php echo filemtime ( "./js/global.js" ) ; ?>"></script>
<script data-cfasync="false" type="text/javascript" src="./js/framework.js?<?php echo $VERSION ?>"></script>

<script data-cfasync="false" type="text/javascript">
<!--
	var patch_c = <?php echo ( isset( $first_patch ) ) ? $first_patch : $patch_v ; ?> ;
	var loader_c ;
	var dev = 0 ;
	var dev_c = 0 ;
	var total_qc = 0 ;
	var si_loader ;

	var patch_interval = 5000 ;

	$(document).ready(function()
	{
		$("html").css({'background': '#F4F6F8'}) ; $("body").css({'background': '#F4F6F8'}) ;

		$.ajaxSetup({ cache: false }) ;

		auto_patch() ;
		init_loader_image() ;
	});

	function init_loader_image()
	{
		si_loader = setInterval( function(){
			loader_c_temp = Math.floor(Math.random() * 3) + 1 ;
			if ( loader_c_temp != loader_c )
			{
				loader_c = loader_c_temp ;
				if ( loader_c == 1 )
				{
					$('#image_loading').attr( "src", "./pics/loading_patch_1.gif" ) ;
				}
				else if ( loader_c == 2 )
				{
					$('#image_loading').attr( "src", "./pics/loading_patch_2.gif" ) ;
				}
				else
				{
					$('#image_loading').attr( "src", "./pics/loading_patch_3.gif" ) ;
				}
			}
		}, 1000 ) ;
	}

	function auto_patch()
	{
		var json_data = new Object ;
		var unique = unixtime() ;

		if ( dev )
		{
			++dev_c ; patch_c = dev_c ;
			var percent = Math.round( ( patch_c/100 )*100 ) ;
			$('#status').html( percent ) ;
			total_qc += Math.floor(Math.random() * (4 - 1)) + 1 ;
			$('#process_id').html( pad( patch_c, 3 ) ) ; $('#process_db').html( pad( total_qc, 3 ) ) ;
			patch_interval = 1000 ;
			setTimeout( function(){ patch_c += 1 ; auto_patch() ; }, patch_interval ) ;
		}
		else
		{
			$.ajax({
			type: "POST",
			url: "./patch.php",
			data: "patch=1&patch_c="+patch_c+"&unique="+unique,
			success: function(data){
				try {
					eval(data) ;
				} catch(err) {
					$('#div_error_output').html( data + "<br><br>Refresh the page and try again." ).show() ;
					return false ;
				}

				patch_c = json_data.patch_c ;

				if ( json_data.status )
				{
					$('#status').html( 100 ) ;
					$('#div_processing').hide() ;
					$('#process').html( total_qc ) ;
					$('#loading').hide() ;
					$('#div_stats').hide() ;
					$('#div_success').show() ;

					clearInterval( si_loader ) ;

					if ( "<?php echo $from ?>" == "chat" )
					{
						setTimeout( function(){ do_redirect() ; }, 3000 ) ;
					}
					else
					{
						setTimeout( function(){ do_redirect() ; }, <?php echo ( isset( $FAST_PATCH ) && $FAST_PATCH ) ? 1000 : 45000 ; ?> ) ;
					}
				}
				else
				{
					var percent = Math.round( ( patch_c/<?php echo $patch_v ?> )*100 ) ; if ( percent > 100 ) { percent = 100 ; }
					$('#status').html( percent ) ;
					total_qc += parseInt( json_data.qc ) ;

					$('#process_id').html( pad( patch_c-1, 3 ) ) ; $('#process_db').html( pad( total_qc, 3 ) + " : " + json_data.qc ) ;

					if ( patch_interval < 1000 ) { patch_interval = 1000 ; }
					setTimeout( function(){ auto_patch() ; }, patch_interval ) ;
				}
			},
			error:function (xhr, ajaxOptions, thrownError){
				do_alert( 0, "Error patch "+patch_c+" process.  Refresh the page and try again." ) ;
			} });
		}
	}

	function do_redirect()
	{
		location.href = "<?php echo $url ?>" ;
	}
//-->
</script>
</head>
<body style="overflow: hidden;">

<div style="width: 310px; margin: 0 auto; margin-top: 55px; padding: 10px;">

	<div id="div_configure" style="text-align: center;">
		<div style=""><?php if ( isset( $CONF["KEY"] ) && ( $CONF["KEY"] == md5($KEY."_key_phplive") ) ): ?><?php else: ?><img src="pics/logo.png" border="0" alt=""><?php endif ; ?></div>
		<div style="margin-top: 15px;">
			<div id="div_configuring" style="text-shadow: none; padding: 15px;" class="info_white round_bottom_none">
				Updating and patching your system.
				<div style="margin-top: 15px;">Just a moment please...</div>
			</div>
			<div class="info_blue round_top_none" style="padding: 15px;">
				<center>
				<div id="loading">
					<img src="pics/loading_patch_1.gif" width="38" height="38" border="0" alt="" style="background: #FBFBFB; border-radius: 50%; padding: 2px; box-shadow: 2px 2px 15px rgba(0, 0, 0, 0.2);" id="image_loading">
				</div>
				<div style="margin-top: 15px; font-size: 24px; font-weight: bold;"><span id="status">1</span>%</div>
				</center>
			</div>
		</div>
		<div id="div_stats" style="display: none; margin-top: 10px; text-align: center; opacity:0.7; filter:alpha(opacity=7);" class="info_white">
			<div><small>Process ID: <span id="process_id">0</span> &nbsp; &nbsp; Patched: <span id="process_db">0</span></small></div>
		</div>

		<div id="div_success" style="display: none; margin-top: 15px;">
			<button type="button" class="btn" onClick="do_redirect()" style="width: 100%;">Continue</button>
		</div>
	</div>

</div>

<div id="div_error_output" class="info_error" style="display: none; width: 600px; margin: 0 auto; margin-top: 25px;"></div>
</div>

<!-- [winapp=4] -->

</body>
</html>
<?php
	if ( isset( $dbh ) && isset( $dbh['con'] ) )
		database_mysql_close( $dbh ) ;
?>