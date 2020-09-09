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
	"use strict" ;
	var global_ces ;
	var global_ces_select ;
	var chat = new Object ;

	var stars = parent.stars ;
	var isop = parent.isop ;
	var theme = parent.theme ;

	$(document).ready(function()
	{
		reset_mapp_div_height() ;

		if ( parent.tag ) { $('#tr_tag').show() ; }

		toggle_menu_info( "info" ) ;
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

	function toggle_menu_info( themenu )
	{
		var divs = Array( "info", "transcripts", "transfer", "spam" ) ;

		for ( var c = 0; c < divs.length; ++c )
		{
			$('#div_info_'+divs[c]).hide() ;
			$('#menu_info_'+divs[c]).removeClass('menu_traffic_info_focus').addClass('menu_traffic_info') ;
		}

		if ( themenu == "transfer" )
		{
			if ( chat["op2op"] || chat["group_chat"] ) { $('#div_info_transfer').html( "Chat transfer is not available for this session." ) ; }
			else { parent.populate_ops(1) ; }
		}

		$('#div_info_'+themenu).show() ;
		$('#menu_info_'+themenu).removeClass('menu_traffic_info').addClass('menu_traffic_info_focus') ;
	}

	function populate_vinfo( theces )
	{
		if ( parent.chats[theces] != "undefined" )
		{
			global_ces = theces ;
			chat = parent.chats[theces] ;

			var spam_block_string = ( !chat["op2op"] ) ? " &nbsp;&nbsp; <button type=\"button\" class=\"input_op_button\" onClick=\"parent.spam_block(1, '"+chat["ip"]+"')\">Spam Block</button>" : "" ;

			$('#req_dept').html( parent.$('#req_dept').html() ) ;
			$('#req_email').html( parent.$('#req_email').html() ) ;
			$('#req_request').html( parent.$('#req_request').html() ) ;
			$('#req_onpage').html( parent.$('#req_onpage').html() ) ;
			$('#req_refer').html( parent.$('#req_refer').html() ) ;
			$('#req_market').html( parent.$('#req_market').html() ) ;
			$('#req_resolution').html( parent.$('#req_resolution').html() ) ;
			$('#req_ip').html( parent.$('#req_ip').html() ) ;
			$('#req_custom').html( parent.$('#req_custom').html() ) ;
			if ( parent.$('#tr_files').length && parent.$('#tr_files').is( ":visible" ) )
			{
				$('#tr_files').show() ; $('#req_files').html( parent.$('#req_files').html() ) ;
			}
			else
			{
				$('#tr_files').hide() ; $('#req_files').empty() ;
			}
			$('#req_ces').html( parent.$('#req_ces').html() ) ;

			if ( !parent.chats[theces]["tag"] )
				$('#req_tag').html( parent.$('#req_tag').html() ) ;

			parent.populate_transcripts(1) ;
			init_external_url() ;
			// for now switch off duplicate
			if ( !chat["op2op"] && <?php echo $geoip ?> && 0 ) { fetch_geo( chat["ip"] ) ; }
		}
		toggle_menu_info( "info" ) ;
		parent.init_extra_loaded() ;
	}

	function fetch_geo( theip )
	{
		var unique = unixtime() ;
		var json_data = new Object ;

		$.ajax({
		type: "POST",
		url: "../wapis/geoip.php",
		data: "akey=<?php echo $CONF['API_KEY'] ?>&f=csv&ip="+theip+"&"+unique,
		success: function(data){
			// unknown,Location Unknown,-,-,28.613459424004,-40.4296875
			var geo_data = data.split(",") ;
			var country = ( typeof( geo_data[0] ) != "undefined" ) ? geo_data[0].toLowerCase()+".gif" : "unknown.gif" ;
			var country_name = ( typeof( geo_data[1] ) != "undefined" ) ? geo_data[1] : "unknown" ;

			$('#req_ip').append( " &nbsp; <img src=\"../pics/maps/"+country+"\" alt=\""+country_name+"\" title=\""+country_name+"\">" ) ;
		},
		error:function (xhr, ajaxOptions, thrownError){
			do_alert( 0, "Error loading Geo data.  Please refresh the console and try again." ) ;
		} });
	}

	function populate_trans()
	{
		var transcripts_string = "" ;
		for ( var c = 0; c < parent.mapp_obj["transcripts"].length; ++c )
		{
			var transcript = parent.mapp_obj["transcripts"][c] ;
			var rating = ( parseInt( transcript["rating"] ) ) ? "<tr><td>Rating</td><td style=\"\">"+stars[transcript["rating"]]+"</td></tr>" : "" ;

			transcripts_string += " \
				<div class=\"info_neutral\" id='table_"+transcript["ces"]+"' style=\"padding: 10px; margin-bottom: 5px;\"> \
					<table cellspacing=0 cellpadding=2 border=0> \
					<tr> \
						<td>Operator</td> \
						<td style=\"\"><b>"+transcript["operator"]+"</b></td> \
					</tr> "+rating+" \
					<tr> \
						<td>Created</td> \
						<td style=\"\"><b>"+transcript["created"]+"</b> &nbsp; ("+transcript["duration"]+")</td> \
					</tr> \
					<tr> \
						<td><button type=\"button\" onClick=\"open_transcript('"+transcript["ces"]+"')\">select</button></td> \
					</tr> \
					</table> \
				</div> \
			" ;
		}

		if ( chat["op2op"] ) { transcripts_string = "Transcripts not available for this session." ; }
		else if ( ( typeof( parent.mapp_obj["transcripts"].length ) == "undefined" ) || ( parent.mapp_obj["transcripts"].length == 0 ) )
			transcripts_string = "<div class=\"info_neutral\">Blank results.</div>" ;

		$('#div_chats_trans_list').html( transcripts_string ) ;
	}

	function open_transcript( theces )
	{
		var div_width = $('#canned_container').width() - 10 ;
		var div_height = $('#canned_container').height() - 10 ;
		var url = "../ops/op_trans_view.php?ces="+theces+"&id=<?php echo $opinfo["opID"] ?>&auth=operator&back=1&mapp=1&"+unixtime() ;

		if ( global_ces_select != theces )
		{
			$('#table_'+theces).addClass('info_focus') ;
			if ( typeof( global_ces_select ) != "undefined" )
				$('#table_'+global_ces_select).removeClass('info_focus') ;
			global_ces_select = theces ;
		}

		$('#div_cans').hide() ;
		$('#iframe_transcript').css({'height': div_height}).attr( 'src', url ).load(function (){
			$('#div_cans_iframe').show() ;
			setTimeout( function(){
				document.getElementById('iframe_transcript').contentWindow.init_chat_body_height(div_width, div_height) ;
			}, 200 ) ;
		});
	}

	function close_transcript( theces )
	{
		$('#div_cans_iframe').hide() ;
		$('#div_cans').show() ;

		var div_pos = $('#table_'+theces).position() ;
		var scroll_to = div_pos.top - 50 ;

		$('#canned_container').scroll() ;
		$('#canned_container').animate({
			scrollTop: scroll_to
		}, 200) ;
	}

	function populate_ops()
	{
		var ces = parent.ces ;
		var chats = parent.chats ;
		var nchats = parent.nchats ;
		var departments = ( typeof( parent.mapp_obj["ops"] ) != "undefined" ) ? parent.mapp_obj["ops"] : Array() ;

		var ops_string = "" ;
		var json_length = departments.length ;
		for ( var c = 0; c < json_length; ++c )
		{
			var total_dept_ops = departments[c].operators.length ;

			var btn_transfer_department = ( total_dept_ops ) ? "&nbsp;<button type=\"button\" onClick=\"parent.transfer_chat( this, "+departments[c]["deptid"]+",'"+departments[c]["name"]+"', "+departments[c]["rtype"]+", 0, 0, 1);$(this).attr('disabled', 'true');\">transfer</button>" : "" ;
			ops_string += "<div class=\"chat_info_td_h\"><b>"+departments[c]["name"]+" %%btn_transfer_department%%</b></div>" ;

			var transfer_op_indept = 0 ; var transfer_ops_online = 0 ;
			for ( var c2 = 0; c2 < total_dept_ops; ++c2 )
			{
				var status = "offline" ;
				var status_bullet = "online_grey.png" ;
				var chatting_with = ( nchats ) ? " chatting with "+departments[c].operators[c2]["requests"]+" visitors" : "" ;
				var btn_transfer = "" ;

				if ( departments[c].operators[c2]["status"] )
				{
					status = "online" ;

					status_bullet= "online_green.png" ;
					btn_transfer = "<button type=\"button\" onClick=\"parent.transfer_chat( this, "+departments[c]["deptid"]+",'"+departments[c]["name"]+"', "+departments[c]["rtype"]+", "+departments[c].operators[c2]["opid"]+",'"+departments[c].operators[c2]["name"]+"', 1);$(this).attr('disabled', 'true');\">transfer</button>" ;
				}

				if ( departments[c].operators[c2]["opid"] == isop )
				{
					transfer_op_indept = 1 ; // check to see if operator is in department
					ops_string += "<div class=\"chat_info_td\" style=\"padding-left: 15px;\"><img src=\"../themes/"+theme+"/"+status_bullet+"\" width=\"12\" height=\"12\" border=\"0\"> <b>(You)</b> are "+status+chatting_with+"</div>" ;
				}
				else
				{
					if ( status == "online" ) { ++transfer_ops_online ; }
					ops_string += "<div class=\"chat_info_td\" style=\"padding-left: 15px;\"><img src=\"../themes/"+theme+"/"+status_bullet+"\" width=\"12\" height=\"12\" border=\"0\"> "+btn_transfer+" "+departments[c].operators[c2]["name"]+" is "+status+chatting_with+"</div>" ;
				}
			}

			if ( transfer_ops_online > 1 )
				ops_string = ops_string.replace( /%%btn_transfer_department%%/, btn_transfer_department ) ;
			else
				ops_string = ops_string.replace( /%%btn_transfer_department%%/, "" ) ;
		}
		ops_string += "<div class=\"chat_info_end\"></div>" ;
		$('#div_info_transfer').html( ops_string ) ;
	}

	function update_tag( thetagid )
	{
		var ces = parent.ces ;
		var chats = parent.chats ;

		var unique = unixtime() ;
		var json_data = new Object ;

		$('#tagid').attr('disabled', true) ;

		if ( ( typeof( ces ) != "undefined" ) && chats[ces]["status"] )
		{
			if ( chats[ces]["tag"] != thetagid )
			{
				$.ajax({
				type: "POST",
				url: "../ajax/chat_actions_op_tag.php",
				data: "action=tag&ces="+ces+"&tagid="+thetagid+"&unique="+unique+"&",
				success: function(data){
					eval( data ) ;

					if ( json_data.status )
					{
						chats[ces]["tag"] = thetagid ;
						$('#req_tag_saved').fadeIn("fast").delay(3000).fadeOut( "fast", function() {
							$('#tagid').attr('disabled', false) ;
						});
					}
					else
					{
						do_alert( 0, json_data.error ) ;
						$('#tagid').attr('disabled', false).val(0) ;
					}
				},
				error:function (xhr, ajaxOptions, thrownError){
					do_alert( 0, "Error processing tag.  Please refresh the console and try again." ) ;
				} });
			}
			else
			{
				$('#req_tag_saved').fadeIn("fast").delay(3000).fadeOut( "fast", function() {
					$('#tagid').attr('disabled', false) ;
				});
			}
		}
		else
		{
			$('#tagid').attr('disabled', false).val(0) ;
			do_alert( 0, "A chat session must be active." ) ;
		}
	}
//-->
</script>
</head>
<body style="-webkit-text-size-adjust: 100%;">

<div id="canned_container" style="padding: 15px; padding-top: 25px; height: 200px; overflow: auto;">
	<div id="div_cans">
		<div style="">
			<div id="menu_info_info" class="menu_traffic_info_focus" onClick="toggle_menu_info('info')">Visitor Info</div>
			<div id="menu_info_transcripts" class="menu_traffic_info" onClick="toggle_menu_info('transcripts')">Transcripts</div>
			<div id="menu_info_transfer" class="menu_traffic_info" onClick="toggle_menu_info('transfer')">Transfer</div>
			<div style="clear: both;"></div>
		</div>

		<div style="margin-top: 25px;">
			<div id="div_info_info" style="display: none; padding-bottom: 50px;">
				<table cellspacing=0 cellpadding=0 border=0>
				<tr><td class="chat_info_td_h"><b>Department</b></td><td width="100%" class="chat_info_td"> <span id="req_dept"></span></td></tr>
				<tr><td class="chat_info_td_h" nowrap><b>Visitor Email</b></td><td class="chat_info_td"> <span id="req_email"></span></td></tr>
				<tr><td class="chat_info_td_h" nowrap><b>Chat Request</b></td><td class="chat_info_td"> <span id="req_request"></span></td></tr>
				<tr><td class="chat_info_td_h" nowrap><b>Clicked From</b></td><td class="chat_info_td"> <span id="req_onpage"></span></td></tr>
				<tr><td class="chat_info_td_h"><b>Refer URL</b></td><td class="chat_info_td"> <span id="req_refer"></span></td></tr>
				<tr><td class="chat_info_td_h"><b>Marketing</b></td><td class="chat_info_td"> <span id="req_market"></span></td></tr>
				<tr><td nowrap class="chat_info_td_h"><b>Resolution</b></td><td class="chat_info_td"> <span id="req_resolution"></span></td></tr>
				<?php if ( $opinfo["viewip"] ): ?><tr><td nowrap class="chat_info_td_h" nowrap><b>IP Address</b></td><td class="chat_info_td"> <span id="req_ip"></span></td></tr><?php endif ; ?>
				<tr><td nowrap class="chat_info_td_h" nowrap><b>Custom Fields</b></td><td class="chat_info_td"><div id="req_custom" style="max-height: 80px; overflow-y: auto; overflow-x: hidden;"></div></td></tr>
				<tr id="tr_files" style="display: none;"><td nowrap class="chat_info_td_h" nowrap><b>Files</b></td><td class="chat_info_td"><div id="req_files" style="max-height: 100px; overflow-y: auto;"></div></td></tr>
				<tr id="tr_tag" style="display: none;"><td nowrap class="chat_info_td_h"><b>Tag</b></td><td class="chat_info_td"> <span id="req_tag"></span> &nbsp; <span id="req_tag_saved" class="info_good" style="display: none;">saved</span></td></tr>
				<tr><td class="chat_info_td_h" style="opacity: 0.5; filter: alpha(opacity=50);"><b>Chat ID</b></td><td class="chat_info_td" style="opacity: 0.5; filter: alpha(opacity=50);"> <span id="req_ces"></span></td></tr>
				</table>
			</div>

			<div id="div_info_transcripts" style="display: none; padding-bottom: 50px;">
				<div id="div_chats_trans_list"></div>
			</div>

			<div id="div_info_transfer" style="display: none; padding-bottom: 50px;">
			</div>

			<div id="div_info_spam" style="display: none;">
			</div>
		</div>
	</div>
	<div id="div_cans_iframe" style="display: none;"><iframe id="iframe_transcript" name="iframe_transcript" style="width: 100%; border: 0px; height: 10px; border-radius: 5px;" src="about:blank" scrolling="no" frameBorder="0"></iframe></div>
</div>

</body>
</html>
<?php
	if ( isset( $dbh ) && $dbh['con'] )
		database_mysql_close( $dbh ) ;
?>
