<?php
	$alttext_array = ( isset( $VALS["alttext"] ) && $VALS["alttext"] ) ? unserialize( $VALS["alttext"] ) : Array() ;
	$alttext_array_dept = Array() ; $alttext_using_global = 0 ;
	if ( isset( $alttext_array[$deptid] ) )
		$alttext_array_dept = $alttext_array[$deptid] ;
	else if ( $deptid && isset( $alttext_array[0] ) )
	{
		$alttext_using_global = 1 ;
		$alttext_array_dept = $alttext_array[0] ;
	} array_walk( $alttext_array_dept, "Util_Format_base64_decode_array" ) ;
	$title_margin_top = ( $win_style == "modern" ) ? "0px" : "4px" ;
?>
	<div id="chat_embed_header" style="<?php echo ( $embed ) ? "" : "display: none;" ; ?> height: 32px; padding: 5px;">
		<div id="embed_win_minimize" style='float: left; width: 50px; height: 32px; cursor: pointer;' onClick="parent_send_message('minimize', <?php echo $deptid ?>)"><img src="./themes/<?php echo $theme ?>/win_min.png?<?php echo $VERSION ?>" width="26" height="26" border=0 style="padding: 3px;" alt="<?php echo isset( $alttext_array_dept["emminimize"] ) ? $alttext_array_dept["emminimize"] : "" ; ?>" title="<?php echo isset( $alttext_array_dept["emminimize"] ) ? $alttext_array_dept["emminimize"] : "" ; ?>"></div>
		<div id="embed_win_maximize" style='display: none; float: left; width: 50px; height: 32px;'><img src="./themes/<?php echo $theme ?>/win_max.png?<?php echo $VERSION ?>" width="26" height="26" border=0 style="padding: 3px;" id="embed_win_maximize_img" alt="<?php echo isset( $alttext_array_dept["emmaximize"] ) ? $alttext_array_dept["emmaximize"] : "" ; ?>" title="<?php echo isset( $alttext_array_dept["emmaximize"] ) ? $alttext_array_dept["emmaximize"] : "" ; ?>"></div>
		<div id="embed_win_popout" style='display: none; float: left; width: 50px; height: 32px; cursor: pointer;' onClick="parent_send_message('popout', <?php echo $deptid ?>)"><img src="./themes/<?php echo $theme ?>/win_pop.png?<?php echo $VERSION ?>" width="26" height="26" border=0 style="padding: 3px;" alt="<?php echo isset( $alttext_array_dept["empopout"] ) ? $alttext_array_dept["empopout"] : "" ; ?>" title="<?php echo isset( $alttext_array_dept["empopout"] ) ? $alttext_array_dept["empopout"] : "" ; ?>"></div>
		<div id="embed_win_close" style='display: none; float: left; width: 50px; height: 32px; cursor: pointer;' onClick="parent_send_message('close', <?php echo $deptid ?>)"><img src="./themes/<?php echo $theme ?>/win_close.png?<?php echo $VERSION ?>" width="26" height="26" border=0 style="padding: 3px;" alt="<?php echo isset( $alttext_array_dept["emclose"] ) ? $alttext_array_dept["emclose"] : "" ; ?>" title="<?php echo isset( $alttext_array_dept["emclose"] ) ? $alttext_array_dept["emclose"] : "" ; ?>"></div>
		<div id="chat_embed_title" style='float: left; display: inline-block; margin-top: <?php echo $title_margin_top ?>; opacity: 0; filter: alpha(opacity=0);'><span id="LANG_TXT_LIVECHAT"></span></div>
		<div style='clear: both;'></div>
	</div>