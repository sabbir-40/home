<script data-cfasync="false" type="text/javascript">
<!--
	function toggle_send_trans( theforce_close )
	{
		if ( $('#send_transcript_box').is(':visible') || theforce_close )
			$('#send_transcript_box').fadeOut("fast") ;
		else
		{
			if ( !isop || mapp )
				$('#send_transcript_box').center() ;
			else
				position_send_trans() ;

			var top = parseInt( $('#send_transcript_box').css('top') ) ;
			var top_start = top + 100 ;

			$('#send_transcript_box').css({'top': top_start}) ;

			$('#send_transcript_box').fadeIn({queue: false, duration: 'fast'}) ;
			$('#send_transcript_box').animate({ top: top }, 'fast') ;

			var vemail = ( !isop ) ? cemail : chats[ces]["vemail"] ; if ( vemail == "null" ) { vemail = "" ; }

			// for now, operator vemail is blank to limit accidental sending of transcript
			if ( isop ) { vemail = "" ; }
			$('#vemail').val( vemail ) ;
		}
	}

	function position_send_trans()
	{
		var trans_pos = $("#chat_email").position() ;
		var height_trans_box = parseInt( $('#send_transcript_box').outerHeight() ) ;
		var trans_top = trans_pos.top - height_trans_box - 15 ;
		var trans_left = trans_pos.left  ;
		if ( !isop )
		{
			// center position
		}
		else
			$("#send_transcript_box").css({'top': trans_top, 'left': trans_left}) ;
	}

	function send_email( thedownload )
	{
		var vemail = $('#vemail').val().trim() ;

		if ( !vemail && !thedownload )
			do_alert( 0, "<?php echo $LANG["CHAT_JS_BLANK_EMAIL"] ?>" ) ;
		else if ( !check_email( vemail ) && !thedownload )
			do_alert( 0, "<?php echo $LANG["CHAT_JS_INVALID_EMAIL"] ?>" ) ;
		else
		{
			$('#btn_email').attr( "disabled", true ) ;
			$('#vemail').attr( "disabled", true ) ;

			var json_data = new Object ;
			var unique = unixtime() ;
			var vname = ( !isop ) ? cname : chats[ces]["vname"] ;
			var post_url = ( !isop ) ? "phplive_m.php" : "../phplive_m.php" ;
			var gid = <?php echo ( isset( $gid ) ) ? $gid : 0 ; ?> ;
			var browser_token = ( !isop ) ? phplive_browser_token : "" ;
			var vtoken = ( !isop ) ? "" : chats[ces]["vis_token"] ;

			$.ajax({
			type: "POST",
			url: post_url,
			data: "&action=send_email_trans&trans=1&ces="+ces+"&opid="+chats[ces]["opid"]+"&deptid="+chats[ces]["deptid"]+"&gid="+gid+"&token="+browser_token+"&vtoken="+vtoken+"&vname="+vname+"&vemail="+vemail+"&emarketid=<?php echo ( $VARS_ADDON_EMARKET_ENABLED && isset( $emarketid ) ) ? $emarketid : 0 ; ?>&download="+thedownload+"&"+unique,
			success: function(data){

				if ( thedownload )
				{
					$('#btn_email').attr( "disabled", false ) ;
					$('#vemail').attr( "disabled", false ) ;

					$('#link_download_doit').attr('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(data)) ;
					$('#link_download_doit')[0].click() ;
				}
				else
				{
					eval( data ) ;
					if ( json_data.status )
					{
						do_alert( 1, "<?php echo Util_Format_ConvertQuotes( urldecode( $LANG["CHAT_JS_EMAIL_SENT"] ) ) ?>" ) ;
						toggle_send_trans(1) ;
						setTimeout( function(){
							$('#btn_email').attr( "disabled", false ) ;
							$('#vemail').attr( "disabled", false ) ;
						}, 10000 ) ;
					}
					else
					{
						do_alert( 0, json_data.error ) ;
						$('#btn_email').attr( "disabled", false ) ;
						$('#vemail').attr( "disabled", false ) ;
					}
				}

			},
			error:function (xhr, ajaxOptions, thrownError){
				do_alert( 0, "Could not connect to server.  Please try again. [e551]" ) ;
				$('#btn_email').attr( "disabled", false ) ;
				$('#vemail').attr( "disabled", false ) ;
			} });
		}
	}
//-->
</script>
<div id="send_transcript_box" style="display: none; position: absolute; top: 0px; left: 0px; padding: 2px; width: 240px; height: 180px; overflow: auto; box-shadow: 0px 0px 25px rgba(0, 0, 0, 0.2); z-Index: 500;" class="info_content">
	<div style="text-align: center; cursor: pointer;" class="info_error" onClick="close_misc('all')"><img src="<?php echo $CONF["BASE_URL"] ?>/themes/<?php echo $theme ?>/close_extra.png" width="16" height="16" border="0"> <?php echo ( isset( $LANG["CHAT_CLOSE"] ) ) ? urldecode( $LANG["CHAT_CLOSE"] ) : "Close" ; ?></div>
	<div style="margin-top: 5px; padding: 10px;">
		<div><?php echo ( isset( $LANG["TXT_EMAIL"] ) ) ? urldecode( $LANG["TXT_EMAIL"] ) : "Email" ; ?>:<br><input type='text' class='input_text' style='width: 85%;' maxlength='160' id='vemail' name='vemail' value=''></div>
		<div style="margin-top: 15px;">
			<input type='button' id='btn_email' value='<?php echo ( isset( $LANG["CHAT_BTN_EMAIL_TRANS"] ) ) ? urldecode( $LANG["CHAT_BTN_EMAIL_TRANS"] ) : "Send Transcript" ; ?>' onClick='send_email(0)' class="input_op_button" style="padding: 10px;">
		</div>
	</div>
</div>