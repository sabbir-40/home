<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="robots" content="noindex, nofollow">
<?php if ( isset( $CONF["BASE_URL"] ) && isset( $CONF_EXTEND ) && is_file( "$CONF[CONF_ROOT]/favicon.ico" ) ): ?>
<link rel="shortcut icon" href="<?php echo $CONF["BASE_URL"] ?>/web/<?php echo $CONF_EXTEND ?>/favicon.ico">
<?php else: ?>
<link rel="shortcut icon" href="<?php echo $CONF["BASE_URL"] ?>/favicon.ico">
<?php endif ; ?>
<style>
.cw{ font-style: italic; opacity:0.8; filter:alpha(opacity=80); color: inherit !important; background: transparent !important; }
.round_top_none{ border-top-left-radius: 0px 0px; border-top-right-radius: 0px 0px; }
.round_bottom_none{ border-bottom-left-radius: 0px 0px; border-bottom-right-radius: 0px 0px; }
</style>
