 <?php

    include_once '../Class/autoload.php';
    include_once '../config.php';
    include_once '../functions.php';

    $hideChecked = ($cfg['hide_offline'] == 1) ? 'checked' : '';

    include_once '../header.php'
?>

<div class="container">
    <div class="row">
        <div class="col-sm-6">
            <?php 
                $total_activity = GetActivityCount();
                $sql= "SELECT user_id, hits, last_visit, time_created, user_level FROM tf_users WHERE user_id=".$db->qstr($cfg["user"]);
                list($user_id, $hits, $last_visit, $time_created, $user_level) = $db->GetRow($sql);
    
                $user_type = _NORMALUSER;
                if (IsSuperAdmin()) {
                    $user_type = _SUPERADMIN;
                } else if (IsAdmin()) {
                    $user_type = _ADMINISTRATOR;
                }
    
                $user_activity = GetActivityCount($cfg["user"]);
                if ($user_activity == 0) {
                    $user_percent = 0;
                } else {
                    $user_percent = number_format(($user_activity/$total_activity)*100);
                }
            ?>
            <fieldset class="form-group bd-example" style="margin-right:-12px;margin-left:-15px;">
                <table class="table table-striped">
                <tr>
                    <td style="text-align:right"><?php echo $cfg["user"] ?> <?php echo _JOINED ?>:&nbsp;</td>
                    <td><strong><?php echo date(_DATETIMEFORMAT, $time_created) ?></strong></td>
                </tr>
                <tr>
                    <td style="text-align:right"><?php echo _UPLOADPARTICIPATION ?>:&nbsp;</td>
                    <td><progress class="progress progress-success" value="<?php echo $user_percent ?>" max="100" style="margin-bottom:0px"><?php echo $user_percent*2 ?>%</progress></td>
                </tr>
                <tr>
                    <td style="text-align:right"><?php echo _UPLOADS ?>:&nbsp;</td>
                    <td><strong><?php echo $user_activity ?></strong></td>
                </tr>
                <tr>
                    <td style="text-align:right"><?php echo _PERCENTPARTICIPATION ?>:&nbsp;</td>
                    <td><strong><?php echo $user_percent ?>%</strong></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center"><div style="text-align:center" class="tiny">(<?php echo _PARTICIPATIONSTATEMENT. " " . $settings->get('days_to_keep') . " "._DAYS ?>)</div></td>
                </tr>
                <tr>
                    <td style="text-align:right"><?php echo _TOTALPAGEVIEWS ?>:&nbsp;</td>
                    <td><strong><?php echo $hits ?></strong></td>
                </tr>
                <tr>
                    <td style="text-align:right"><?php echo _USERTYPE ?>:&nbsp;</td>
                    <td><strong><?php echo $user_type ?></strong></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center">[ <a href="../profile.php?op=showCookies">Cookie Management</a> ]</td>
                </tr>
                </table>
            </fieldset>
        </div>
        
        <div class="col-sm-6">
            <fieldset class="form-group bd-example" style="margin-left:-12px;margin-right:-15px">
                <form id="formProfile" name="theForm" action="../profile.php?op=updateProfile" method="post">
                    <table class="table table-striped">
                        <tr>
                            <td style="text-align:right"><?php echo _USER ?>:</td>
                            <td><input disabled type="Text" value="<?php echo $cfg["user"] ?>" class="form-control"></td>
                        </tr>
                        <tr>
                            <td style="text-align:right"><?php echo _NEWPASSWORD ?>:</td>
                            <td><input name="pass1" id="pass1" type="Password" value="" class="form-control"></td>
                        </tr>
                        <tr>
                            <td style="text-align:right"><?php echo _CONFIRMPASSWORD ?>:</td>
                            <td><input name="pass2" id="pass2" type="Password" value="" class="form-control"></td>
                        </tr>
                        <tr>
                            <td style="text-align:right"><?php echo _THEME ?>:</td>
                            <td>
                                <select name="theme" class="form-control">
                                    <?php
                                        $themes = GetThemes();
                                        foreach ($themes AS $theme) {
                                            $selected = ($cfg['theme'] == $theme) ? 'selected' : '';
                                            echo "<option value=\"".$theme."\" ".$selected.">".$theme."</option>";
                                        }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align:right"><?php echo _LANGUAGE ?>:</td>
                            <td>
                                <select name="language" class="form-control">
                                    <?php
                                        $languages = GetLanguages();
                                        foreach ($languages AS $language) {
                                            $selected = ($cfg['language_file'] == $language) ? 'selected' : '';
                                            echo "<option value=\"".$language."\" ".$selected.">".GetLanguageFromFile($language)."</option>";
                                        }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><input name="hideOffline" type="Checkbox" value="1" <?php echo $hideChecked ?> /> <?php echo _HIDEOFFLINEUSERS ?></td>
                        </tr>
                        <tr>
                            <td style="text-align:center" colspan="2"><input id="submitProfile" type="button" value="<?php echo _UPDATE ?>" class="btn btn-primary" /></td>
                        </tr>
                    </table>
                </form>
            </fieldset>
        </div>
    </div>
</div>

<script> 

$(document).ready(function() {

    $("#submitProfile").click(function() {
        var msg = ""
        if ($('#pass1').val() != "" || $('#pass2').val() != "") {
             if ($('#pass1').val().length <= 5 || $('#pass2').val().length <= 5) {
                 msg = msg + "* <?php echo _PASSWORDLENGTH ?>\n";
                 $('#pass1').focus();
             }
             
             if ($('#pass1').val() != $('#pass2').val()) {
                 msg = msg + "* <?php echo _PASSWORDNOTMATCH ?>\n";
                 $('#pass1').val("");
                 $('#pass2').val("");
                 $('#pass1').focus();
             }
         }

         if (msg != "") {
             alert("<?php echo _PLEASECHECKFOLLOWING ?>:\n\n" + msg);
             return false;
         } else {
            $('#formProfile').submit();
            return true;
         }
    });

});
</script>