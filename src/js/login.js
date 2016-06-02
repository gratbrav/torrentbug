/**
 * Login Form
 */
$(document).ready(function() {

    $("#submit").click(function() {
        var msg = "";
        var pass = $("#password").val();
        var user = $("#user").val();

        if (user.length < 1) {
            msg = msg + "* Username is required\n";
            $("#user").focus();
        }

        if (pass.length < 1) {
            msg = msg + "* Password is required\n";
            if (user.length > 0) {
                $("#password").focus();
            }
        }

        if (msg != "") {
            alert("Check the following:\n\n" + msg);
            return false;
        }
    });

});