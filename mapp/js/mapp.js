/********************************************
* Mapp functions
********************************************/

function init_mapp_set_arn( theplatform, thearn, themapp )
{
	$('#platform').val(theplatform) ;
	$('#arn').val(thearn) ;
	$('#auto').val(1) ;
	$('#mapp').val(themapp) ;
	if ( mapp_login ) { setTimeout( function(){ $('#theform').submit() ; }, 500 ) ; } ;
}

var chat_sound_mapp ;
function init_mapp_pause()
{
	if ( typeof( isop ) == "undefined" ) { return false ; }

	var json_data = new Object ;
	var unique = unixtime() ;

	mapp_resume_retry = 0 ; // reset to 0 on each pause action so the resume process retries if needed

	if ( chat_sound ) { chat_sound = 0 ; chat_sound_mapp = 1 ; } // Android device fix so it doesn't play twice in background
	var confirm = ( typeof( mapp_c ) != "undefined" ) ? mapp_c : 0 ;

	$('#img_mapp_spinner').removeClass("info_error") ;

	// slight delay so it is not visible during pause
	setTimeout( function(){ $('#div_mapp_spinner').show().center() ; }, 200 ) ;

	if ( typeof( st_requesting ) != "undefined" )
		clearTimeout( st_requesting ) ;

	$.ajax({
	type: "POST",
	url: base_url+"/mapp/ajax/mapp_actions.php",
	data: "action=pause&opid="+isop+"&confirm="+confirm+"&unique="+unique+"&",
	success: function(data){
		try {
			eval(data) ;
		} catch(err) {
			// the system has a periodic fallback check at script level
		}

		if ( json_data.status )
		{
			//
		}
	},
	error:function (xhr, ajaxOptions, thrownError){
		// the system has a periodic fallback check at script level
	} });
}

var st_mapp_resume ;
var mapp_resume_retry = 0 ;
function init_mapp_resume()
{
	if ( typeof( isop ) == "undefined" ) { return false ; }
	var json_data = new Object ;
	var unique = unixtime() ;

	if ( chat_sound_mapp )
	{
		chat_sound = 1 ;
	}

	$.ajax({
	type: "POST",
	url: base_url+"/mapp/ajax/mapp_actions.php",
	data: "action=resume&opid="+isop+"&unique="+unique+"&",
	success: function(data){
		if ( typeof( st_mapp_resume ) != "undefined" ) { clearInterval( st_mapp_resume ) ; st_mapp_resume = undeefined ; }
		try {
			eval(data) ;
			if ( typeof( st_requesting ) != "undefined" ) { clearTimeout( st_requesting ) ; st_requesting = undeefined ; }
			st_requesting = setTimeout( function(){ requesting() ; }, 3000 ) ; // slight delay for safe measure before fetch data (iOS mainly)
		} catch(err) {
			if ( mapp_resume_retry < 5 )
			{
				++mapp_resume_retry ;
				setTimeout( function(){ init_mapp_resume() ; }, 1000 ) ;
			}
			else
			{
				do_alert( 0, "Could not connect to the server.  Please try closing and re-opening the Mobile App." ) ;
				mapp_resume_retry = 0 ;
			}
		}

		if ( json_data.status )
		{
			//
		}
	},
	error:function (xhr, ajaxOptions, thrownError){
		$('#img_mapp_spinner').addClass('info_error') ; // visual indication network error

		if ( !$('#div_mapp_spinner').is(':visible') )
			$('#div_mapp_spinner').show().center() ;

		if ( mapp_resume_retry < 30 )
		{
			if ( typeof( st_mapp_resume ) != "undefined" ) { clearInterval( st_mapp_resume ) ; st_mapp_resume = undeefined ; }
			++mapp_resume_retry ;
			st_mapp_resume = setTimeout( function(){ init_mapp_resume() ; }, 2000 ) ;
		}
		else
		{
			do_alert( 0, "Could not connect to the server.  Please try closing and re-opening the Mobile App." ) ;
			mapp_resume_retry = 0 ;
		}
	} });
}

function init_mapp_console()
{
	toggle_slider(0) ;
	$('#icons_slider').hide() ;
	$('#chat_data').hide() ;
	$('#chat_printer').hide() ;
	$('#chat_panel').hide() ;
	$('#chat_footer').hide() ;
	$('#chat_switchboard').css({'top': -1000}) ; // needs to be visible for clone
	$('#chat_vname').hide() ;
	$('#chat_vistyping_wrapper').hide() ;
	$('#chat_vistyping').hide() ;
	$('#info_disconnect').hide() ;
	$('#chat_vtimer').css({'top': 0}) ;
	$('#chat_footer_mapp').show() ;
	$('#chat_info_header').hide() ;
	$('#chat_info_menu_list').hide() ;

	$('#chat_vtimer').hide() ;
	//$('#chat_vtimer_wrapper').detach().appendTo('#chat_text_login') ;
}

function reset_mapp_div_height()
{
	var document_height = $(document).height() - 65 ;
	$('#canned_container').css({'height': document_height}).show() ;
}

function populate_mapp_chats()
{
	init_iframe( 'iframe_mapp_chats' ) ;
	$('#chat_extra_body_mapp_chats').show() ;

	// dynamic position of the switchboard, right below extra close
	var switchboard_top = 80 ;

	$('#chat_switchboard').css({'top': switchboard_top}) ;
	setTimeout( function(){
		document.getElementById('iframe_mapp_chats').contentWindow.reset_mapp_div_height() ;
		document.getElementById('iframe_mapp_chats').contentWindow.display_chats() ;
	}, 100 ) ;
}

function populate_mapp_traffic()
{
	$('#iframe_mapp_traffic').attr('src', base_url+"/mapp/mapp_traffic.php?"+unixtime() ).ready(function() {
		init_iframe( 'iframe_mapp_traffic' ) ;
	});
	$('#chat_extra_body_mapp_traffic').show() ;
}

function populate_mapp_themes()
{
	$('#iframe_mapp_themes').attr('src', base_url+"/mapp/mapp_themes.php?"+unixtime() ).ready(function() {
		init_iframe( 'iframe_mapp_themes' ) ;
	});
	$('#chat_extra_body_mapp_themes').show() ;
}

function populate_mapp_prefs()
{
	$('#iframe_mapp_prefs').attr('src', base_url+"/mapp/mapp_prefs.php?"+unixtime() ).ready(function() {
		init_iframe( 'iframe_mapp_prefs' ) ;
	});
	$('#chat_extra_body_mapp_prefs').show() ;
}

function populate_mapp_sounds()
{
	$('#iframe_mapp_sounds').attr('src', base_url+"/mapp/mapp_themes.php?jump=sounds&"+unixtime() ).ready(function() {
		init_iframe( 'iframe_mapp_sounds' ) ;
	});
	$('#chat_extra_body_mapp_sounds').show() ;
}

function populate_mapp_operators()
{
	$('#iframe_mapp_operators').attr('src', base_url+"/ops/op_op2op.php?mapp=1&"+unixtime() ).ready(function() {
		init_iframe( 'iframe_mapp_operators' ) ;
	});
	$('#chat_extra_body_mapp_operators').show() ;
}

function populate_mapp_message_board()
{
	$('#iframe_mapp_message_board').attr('src', base_url+"/addons/message_board/inc_iframe.php?mapp=1&"+unixtime() ).ready(function() {
		init_iframe( 'iframe_mapp_message_board' ) ;
	});
	$('#chat_extra_body_mapp_message_board').show() ;
}

function populate_mapp_trans()
{
	$('#iframe_mapp_trans').attr('src', base_url+"/mapp/mapp_trans.php?"+unixtime() ).ready(function() {
		init_iframe( 'iframe_mapp_trans' ) ;
	});
	$('#chat_extra_body_mapp_trans').show() ;
}

function init_reload_mapp_traffic()
{
	$('#iframe_mapp_traffic').attr('src', base_url+"/mapp/mapp_traffic.php?action=reload&"+unixtime() ).ready(function() {
		//
	});
}


function init_reload_mapp_trans()
{
	$('#iframe_mapp_trans').attr('src', base_url+"/mapp/mapp_trans.php?action=reload&"+unixtime() ).ready(function() {
		//
	});
}

function init_reload_mapp_cans()
{
	$('#iframe_mapp_cans').attr('src', base_url+"/mapp/mapp_canned.php?action=reload&"+unixtime() ).ready(function() {
		//
	});
}

function populate_mapp_power()
{
	$('#iframe_mapp_power').attr('src', base_url+"/mapp/mapp_power.php?"+unixtime() ).ready(function() {
		init_iframe( 'iframe_mapp_power' ) ;
	});
	$('#chat_extra_body_mapp_power').show() ;
}

function populate_mapp_vinfo()
{
	init_iframe( 'iframe_mapp_vinfo' ) ;
	$('#chat_extra_body_mapp_vinfo').show() ;
	setTimeout( function(){
		document.getElementById('iframe_mapp_vinfo').contentWindow.reset_mapp_div_height() ;
		document.getElementById('iframe_mapp_vinfo').contentWindow.populate_vinfo(ces) ;
	}, 100 ) ;
}

function populate_mapp_ops()
{
	document.getElementById('iframe_mapp_vinfo').contentWindow.populate_ops() ;
}

function populate_mapp_trans_vinfo()
{
	document.getElementById('iframe_mapp_vinfo').contentWindow.populate_trans() ;
}

function populate_mapp_cans()
{
	$('#iframe_mapp_cans').attr('src', base_url+"/mapp/mapp_canned.php?"+unixtime() ).ready(function() {
		init_iframe( 'iframe_mapp_cans' ) ;
	});
	$('#chat_extra_body_mapp_cans').show() ;
}

function toggle_mapp_menu_prefs( theforce )
{
	toggle_last_response(1) ;
	if ( $('#div_menu_prefs').is(':visible') || theforce )
	{
		$('#div_menu_prefs').hide() ;
		toggle_mapp_icon( "mapp_prefs", 0 ) ;
	}
	else
	{
		close_extra( extra ) ;
		$('#div_menu_prefs').show() ;
		toggle_mapp_icon( "mapp_prefs", 1 ) ;
	}
}

function toggle_mapp_icon( thediv, theflag )
{
	if ( theflag )
	{
		if ( thediv == "mapp_chats" ) { $('#mapp_icon_chats').css({'border': '1px solid #FFFFFF'}).attr( "src", "../mapp/pics/menu_chats_focus.png?"+cache_v ) ; }
		else if ( thediv == "mapp_cans" ) { $('#mapp_icon_cans').css({'border': '1px solid #FFFFFF'}).attr( "src", "../mapp/pics/menu_cans_focus.png?"+cache_v ) ; }
		else if ( thediv == "mapp_power" ) { $('#mapp_icon_power').css({'border': '1px solid #FFFFFF'}).attr( "src", "../mapp/pics/menu_power_focus.png?"+cache_v ) ; }
		else if ( thediv == "mapp_prefs" ) { $('#mapp_icon_prefs').css({'border': '1px solid #FFFFFF'}).attr( "src", "../mapp/pics/menu_prefs_focus.png?"+cache_v ) ; }
		else if ( thediv == "mapp_traffic" ) { $('#mapp_icon_traffic').css({'border': '1px solid #FFFFFF'}) ; }
	}
	else
	{
		if ( thediv == "mapp_chats" ) { $('#mapp_icon_chats').css({'border': '1px solid #939B9F'}).attr( "src", "../mapp/pics/menu_chats.png?"+cache_v ) ; }
		else if ( thediv == "mapp_cans" ) { $('#mapp_icon_cans').css({'border': '1px solid #939B9F'}).attr( "src", "../mapp/pics/menu_cans.png?"+cache_v ) ; }
		else if ( thediv == "mapp_power" ) { $('#mapp_icon_power').css({'border': '1px solid #939B9F'}).attr( "src", "../mapp/pics/menu_power.png?"+cache_v ) ; }
		else if ( thediv == "mapp_prefs" ) { $('#mapp_icon_prefs').css({'border': '1px solid #939B9F'}).attr( "src", "../mapp/pics/menu_prefs.png?"+cache_v ) ; }
		else if ( thediv == "mapp_traffic" ) { $('#mapp_icon_traffic').css({'border': '1px solid #939B9F'}) ; }
	}
}

function update_mapp_network( theflag )
{
	if ( typeof( document.getElementById('iframe_mapp_power').contentWindow.update_network_img ) != "undefined" )
	{
		document.getElementById('iframe_mapp_power').contentWindow.update_network_img( theflag ) ;
	}
}

function update_mapp_network_log( thecounter, thestring )
{
	if ( typeof( document.getElementById('iframe_mapp_power').contentWindow.update_network_log ) != "undefined" )
	{
		document.getElementById('iframe_mapp_power').contentWindow.update_network_log( thecounter, thestring ) ;
	}
}

function reconnect_mapp()
{
	stopped = 0 ;
	$('#reconnect_status').html( "Operator console disconnected.  Reconnecting... <img src=\"../pics/loading_fb.gif\" width=\"16\" height=\"11\" border=\"0\" alt=\"\">" ) ;
	clear_sound( "new_request" ) ;
	reconnect_counter = 0 ;
	reconnect_doit() ;
}

var mapp_spinner_check_processing = 0 ;
function spinner_check()
{
	var unique = unixtime() ;
	var spinner_check_expired = unique - ( VARS_JS_REQUESTING * 2 ) ; // limit throttle of restart process

	if ( !stopped && !reconnect && ( parseInt( timestamp_st_requesting ) < ( unique - ( VARS_JS_REQUESTING * 3 ) ) ) && ( mapp_spinner_check_processing < spinner_check_expired ) )
	{
		mapp_spinner_check_processing = unique ;
		$('#img_mapp_spinner').addClass('info_error') ;

		write_debug( "Mapp requesting process restarted.", "" ) ;
		restart_requesting("restart") ;
	}
}