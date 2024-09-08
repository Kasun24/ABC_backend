<!doctype html>
<html lang="en-EN">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <meta name="description" content="Reset Password Email Template.">
    <style type="text/css">
        a:hover {
            text-decoration: underline !important;
        }
    </style>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
    <!--100% body table-->
    <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8"
        style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: 'Open Sans', sans-serif;">
        <tr>
            <td>
                <table style="background-color: #f2f3f8; max-width:670px;  margin:0 auto;" width="100%" border="0"
                    align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="height:80px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="height:20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>
                            <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                                style="max-width:670px;background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                                <tr>
                                    <td style="height:40px;">&nbsp;</td>
                                </tr>
                                <tr valign="top">
                                    <td bgcolor="#ffffff">
                                        <font color="#333333" face="Verdana" size="1" style="font-size:11px;line-height:16px"><br>
                                            <br>
                                            <b>{{__('lang.dear')}} <?= $name ?></b><br>
                                        </font>
                                    </td>
                                </tr>
                                <tr>
                                    <font color="#333333" face="Verdana" size="1" style="font-size:11px;line-height:16px">
                                        <br>
                                        {{__('lang.order_reset_email_content_1')}} ABC {{__('lang.order_reset_email_content_2')}}
                                        <br> <br>
                                        {{__('lang.order_reset_email_content_3')}}
                                        <br><br>
                                        <strong style="font-size: 15px;"><?= $code; ?></strong>
                                        <br>
                                    </font>
                                </tr>
                                <tr>
                                    <td style="height:40px;">&nbsp;</td>
                                </tr>
                                <?php
                                // $setting = App\Models\Setting::find(1);
                                ?>
                                {{-- <tr>
                                    <td style="height:40px;">
                                        <div style="background-color: #f7f7f7; display: flex; border-top: 1px solid #fdfdfd;font-size: 14px;font-weight: bold; font-family: sans-serif;">
                                            <div style="width: 33.3%; padding: 20px; text-align: center; border-right: 1px solid #9e9e9e79;"><a style="color: black; text-decoration: none" href="<?= $setting->web_domain ?>/?target=impressum">Impressum</a></div>
                                            <div style="width: 33.3%; padding: 20px; text-align: center; border-right: 1px solid #9e9e9e79;"><a style="color: black; text-decoration: none" href="<?= $setting->web_domain ?>/?target=pnp">Datenschutz</a></div>
                                            <div style="width: 33.3%; padding: 20px; text-align: center;"><a style="color: black; text-decoration: none;" href="<?= $setting->web_domain ?>/?target=tnc">AGB</a></div>
                                        </div>
                                    </td>
                                </tr> --}}
                            </table>
                        </td>
                    <tr>
                        <td style="height:20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="height:80px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <!--/100% body table-->
</body>

</html>