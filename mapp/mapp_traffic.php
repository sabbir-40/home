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
	$error = "" ;
	if ( !isset( $CONF['foot_log'] ) ) { $CONF['foot_log'] = "on" ; }
	if ( !isset( $CONF['icon_check'] ) ) { $CONF['icon_check'] = "on" ; }

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
<script data-cfasync="false" type="text/javascript" src="../js/jquery_md5.js?<?php echo $VERSION ?>"></script>

<script data-cfasync="false" type="text/javascript">
<!--
	var loaded = 1 ;
	var secondtime = 0 ;

	var st_refresh ;

	$(document).ready(function()
	{
		$.ajaxSetup({ cache: false }) ;

		populate_traffic() ;

		reset_mapp_div_height() ;

		<?php if ( $action === "reload" ): ?>do_alert( 1, "Refresh Success" ) ;<?php endif ; ?>

		parent.init_extra_loaded() ;
	});

	function init_external_url()
	{
		$("a").click(function(){
			var temp_url = $(this).attr( "href" ) ;
			if ( !temp_url.match( /javascript/i ) )
			{
				parent.external_url = temp_url ;
				return false ;
			}
		});
	}

	function populate_traffic()
	{
		var json_data = new Object ;
		var unique = unixtime() ;
		var footprints = new Object ;

		if ( parent.automatic_offline_active )
		{
			$('#canned_container').html( "<div class='chat_info_td_traffic'>Traffic Monitor is not available during offline hours.</div>" ) ;
			return false ;
		}

		$.ajax({
		type: "GET",
		url: "../ajax/chat_actions_op_itr_traffic.php",
		data: "action=traffic&unique="+unique,
		success: function(data){
			eval( data ) ;

			if ( json_data.status )
			{
				var vis_exist = 0 ;
				var traffic_string = "" ;

				var json_length = json_data.traffics.length ;
				for ( var c = 0; c < json_length; ++c )
				{
					var vis_token = json_data.traffics[c]["vis_token"] ;
					var market_name = ( ( typeof( parent.markets[json_data.traffics[c]["marketid"]] ) != "undefined" ) && ( typeof( parent.markets[json_data.traffics[c]["marketid"]]["name"] ) != "undefined" ) ) ? parent.markets[json_data.traffics[c]["marketid"]]["name"] : "" ;
					var market_color = ( ( typeof( parent.markets[json_data.traffics[c]["marketid"]] ) != "undefined" ) && ( typeof( parent.markets[json_data.traffics[c]["marketid"]]["color"] ) != "undefined" ) ) ? "style=\"background: #"+parent.markets[json_data.traffics[c]["marketid"]]["color"]+"\"" : "" ;
					var market_td = ( parent.total_markets ) ? "<tr><td>Campaign</td><td "+market_color+"><b>"+market_name+"</b></td></tr>" : "" ;

					var viewmap = ( <?php echo $geoip ?> ) ? "<img src=\"../pics/maps/"+json_data.traffics[c]["country"].toLowerCase()+".gif\" width=\"18\" height=\"12\" border=0 id=\"map_"+json_data.traffics[c]["vis_token"]+"\"> &nbsp; " : "" ;
					viewmap += "Region: "+json_data.traffics[c]["region"] ;
					var viewip = ( parent.viewip ) ? json_data.traffics[c]["ip"] : "" ;

					var url_raw = json_data.traffics[c]["onpage"] ;
					if ( url_raw == "livechatimagelink" )
						url_raw = "JavaScript:void(0)" ;

					/*
					json_data.traffics[c]["t_footprints"]
					json_data.traffics[c]["t_initiates"]
					json_data.traffics[c]["onpage"]
					json_data.traffics[c]["title"]
					json_data.traffics[c]["refer_raw"]
					json_data.traffics[c]["refer_snap"]
					*/

					traffic_string += "<div class='info_neutral' id='table_"+vis_token+"' style='padding: 10px; margin-bottom: 1px;'>"+
							"<table cellspacing=0 cellpadding=4 border=0>"+
							"<tr>"+
								"<td nowrap>On Page</td>"+
								"<td><a href='"+url_raw+"'><b>"+json_data.traffics[c]["title"]+"</b></a></td>"+
							"</tr>"+
							"<tr>"+
								"<td>Duration</td>"+
								"<td><b>"+json_data.traffics[c]["duration"]+"</b></td>"+
							"</tr>"+
							"<tr>"+
								"<td>Platform</td>"+
								"<td><b>"+viewip+"</b> &nbsp; <img src='../themes/<?php echo $theme ?>/os/"+json_data.traffics[c]["os"]+".png' border=0 alt='"+json_data.traffics[c]["os"]+"' title='"+json_data.traffics[c]["os"]+"' alt='"+json_data.traffics[c]["os"]+"' width='14' height='14'> &nbsp; <img src='../themes/<?php echo $theme ?>/browsers/"+json_data.traffics[c]["browser"]+".png' border=0 alt='"+json_data.traffics[c]["browser"]+"' title='"+json_data.traffics[c]["browser"]+"' alt='"+json_data.traffics[c]["browser"]+"' width='14' height='14'></td>"+
							"</tr>"+
							"<tr>"+
								"<td>Location</td>"+
								"<td><b>"+viewmap+"</b></td>"+
							"</tr>"+market_td+
							"</table>"+
						"</div>" ;

					footprints[vis_token] = 1 ;
				}
				if ( json_data.traffics.length != 0 ) { traffic_string += "<div style=\"padding: 50px;\">&nbsp;</div>" ; }
				else { traffic_string = "No website traffic at this time." ; }

				$('#canned_container').html( traffic_string ) ;
				init_external_url() ;
				
				if ( secondtime )
					do_alert( 1, "Refresh Success" ) ;

				++secondtime ;

				if ( typeof( st_refresh ) != "undefined" ) { clearTimeout( st_refresh ) ; st_refresh = undeefined ; }
				st_refresh = setTimeout( function(){ populate_traffic() ; }, 60 * 1000 ) ;
			}
			else { do_alert( 0, "Error loading traffic monitor.  Please refresh the console and try again." ) ; }
		},
		error:function (xhr, ajaxOptions, thrownError){
			do_alert( 0, "Error loading traffic monitor.  Please refresh the console and try again." ) ;
		} });
	}
//-->
</script>
</head>
<body style="-webkit-text-size-adjust: 100%;">

<div id="canned_container" style="padding: 15px; padding-top: 25px; height: 200px; overflow: auto;"></div>

</body>
</html>
<?php
	if ( isset( $dbh ) && $dbh['con'] )
		database_mysql_close( $dbh ) ;
?>
