/* Front Side */
function ValidateMailNewsletter() {
	var x = document.forms["mailnewsletterform"]["mnemail"].value;
    var atpos = x.indexOf("@");
    var dotpos = x.lastIndexOf(".");
    if (atpos<1 || dotpos<atpos+2 || dotpos+2>=x.length) {
        alert("Not a valid e-mail address");
        return false;
    }
}


/* Admin Side */
jQuery.noConflict();
jQuery(document).ready(function($){
	    
    $("#checkAll").click(function () {
        if ($("#checkAll").is(':checked')) {
            $("input[type=checkbox]").each(function () {
                $(this).attr("checked", true);
            });
        } else {
            $("input[type=checkbox]").each(function () {
                $(this).attr("checked", false);
            });
        }
    });

});

