
jQuery(document).ready(function($) {
    if($('#rb_announcements_start_date').length) {
        $(function() {
            var pickerOpts = {
                dateFormat: "yy-mm-dd"
            };
            jQuery("#rb_announcements_start_date").datepicker(pickerOpts);
            jQuery("#rb_announcements_end_date").datepicker(pickerOpts);
        });
    }
	
	if($('#announcements').length) {
		if($.cookie('rb_announcements_active') == 'false') {
			$("#announcements").hide();
		};
		$("#close").click(function() {
			$("#announcements").slideUp("normal");
			$.cookie('rb_announcements_active', 'false', { expires: 2, path: '/'});
			return false;
		});
		$("body").prepend($("#announcements"));
		$('#announcements .rb_announcements_message').cycle('fade');
	}
});