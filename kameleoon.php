<?php
/*
Plugin Name: Kameleoon
Plugin URI: http://www.kameleoon.com
Description: Kameleoon allows you to endlessly redesign your WordPress theme, creating beautiful and professional designs without knowing CSS or HTML. Design your blog directly from your browser!
Tags: design theme template style wysiwyg themes webdesign
Version: 1.1
Author: Kameleoon
Author URI: http://www.kameleoon.com/

Copyright 2010 Shoopz (email: code@kameleoon.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

define("KAMELEOON_DOMAIN", "kameleoon.com");
define("KAMELEOON_AUTHENTICATION_KEY", "60s8jd4bpo");

if (file_exists(WP_PLUGIN_DIR . "/kameleoon/config.php"))
{
	include_once("config.php");
}

function insertKameleoonScript()
{
	echo '<script src="http://static.' . KAMELEOON_DOMAIN . '/kameleoon.js" type="text/javascript"></script>' . "\n";
	echo '<script type="text/javascript">Kameleoon.loadSiteProfile("' . get_option("kameleoonSiteCode") . '"';
	echo ");</script>\n";
}

add_action("wp_head", "insertKameleoonScript");
add_action("wp_meta", "insertKameleoonBanner");
add_action("admin_footer", "kameleoonAddEditionLink");
add_action("admin_menu", "kameleoonCreateMenu");
add_action("wp_ajax_authenticate", "authenticateKameleoon");
add_action("wp_ajax_createUser", "createKameleoonWordPressUser");

function insertKameleoonBanner()
{
	echo '<a href="http://www.kameleoon.com/" alt="With Kameleoon, create a professional web design for your site, directly from your browser. Free, easy integration, no CSS knowledge needed." ><img src="http://images.kameleoon.com/banners/kameleoon-default.png" height="40px;" style="margin-top: 10px;" alt="Customize and skin any webpage quickly with Kameleoon, a tool to author beautiful web designs and templates." /> </a>';
}

function kameleoonAddEditionLink()
{
	echo '<style>';
	echo '#kameleoonLink { margin-left: 30px; color: #aaaaaa !important; font-size: 13px; }' . "\n" . '#kameleoonLink:hover { color: white !important; }';
	echo '#toplevel_page_kameleoon-kameleoon .wp-menu-image { background-image: url("../wp-content/plugins/kameleoon/images/icon.png"); background-repeat: no-repeat; background-position: 5px 5px; width: 32px !important;}' . "\n";
	echo '#toplevel_page_kameleoon-kameleoon:hover .wp-menu-image, #toplevel_page_kameleoon-kameleoon.current .wp-menu-image { background-image: url("../wp-content/plugins/kameleoon/images/icon-active.png"); background-repeat: no-repeat; background-position: 5px 5px; width: 32px;}' . "\n";
	echo '#toplevel_page_kameleoon-kameleoon img { display:none;}' . "\n";
	echo 'div.KameleoonErrorMessage { background-color: #ffffcc; border: 1px solid #efb4b4; padding: 15px;}' . "\n";
	echo '</style>';
	$kameleoonSiteCode = get_option("kameleoonSiteCode");
	if (empty($kameleoonSiteCode))
	{
		echo '<script type="text/javascript">jQuery("#site-heading").append("<a href=\"admin.php?page=kameleoon/kameleoon.php\" id=\"kameleoonLink\">';
	}
	else
	{
		if (get_option("kameleoonAutoLogin"))
		{
			echo '<script type="text/javascript">jQuery("#site-heading").append("<a href=\"admin-ajax.php?action=authenticate\" id=\"kameleoonLink\">';
		}
		else
		{
			echo '<script type="text/javascript">jQuery("#site-heading").append("<a href=\"' . get_bloginfo("url") . '?kameleoon=true\" id=\"kameleoonLink\">';
		}
	}
	echo 'Edit your blog\'s design with Kameleoon<img style=\"position: relative; top: 2px; left: 6px;\" src=\"../wp-content/plugins/kameleoon/images/icon.png\" /></a>");</script>';
}

function kameleoonCreateMenu()
{
	add_menu_page("Kameleoon Plugin Settings", "Kameleoon", "administrator", __FILE__, "kameleoonSettingsPage");
	add_action("admin_init", "registerKameleoonSettings");
}

function registerKameleoonSettings()
{
	register_setting("kameleoon-settings-group", "kameleoonSiteCode", "checkKameleoonSiteCode");
	register_setting("kameleoon-settings-group", "kameleoonUserName", "checkKameleoonUserName");
	register_setting("kameleoon-settings-group", "kameleoonPassword", "checkKameleoonPassword");
	register_setting("kameleoon-settings-group", "kameleoonAutoLogin");
}

function checkKameleoonSiteCode($value)
{
	if (empty($value))
	{
		return get_option("kameleoonSiteCode");
	}
	else
	{
		return $value;
	}
}

function checkKameleoonUserName($value)
{
	if (empty($value))
	{
		return get_option("kameleoonUserName");
	}
	else
	{
		return $value;
	}
}

function checkKameleoonPassword($value)
{
	if (empty($value))
	{
		return get_option("kameleoonPassword");
	}
	else
	{
		return $value;
	}
}

function createKameleoonWordPressUser()
{
	$handle = curl_init("http://www." . KAMELEOON_DOMAIN . "/api");
	$parameters = array("task" => "create", "authenticationKey" => KAMELEOON_AUTHENTICATION_KEY, "object" => "site", "url" => get_bloginfo("url"), "name" => "WordPress plugin", "userName" => "wordpress");
	if (defined("KAMELEOON_PARTNER"))
	{
		$parameters["metadata.site.partner"] = KAMELEOON_PARTNER;
	}
	$email = get_option("admin_email");
	if (isset($email))
	{
		$parameters["metadata.member.email"] = $email;
	}

	curl_setopt($handle, CURLOPT_POST, TRUE);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($handle, CURLOPT_HTTPHEADER, array("Expect:"));
	curl_setopt($handle, CURLOPT_POSTFIELDS, $parameters);

	$result = curl_exec($handle);
	curl_close($handle);

	if (0 === strpos($result, "OK/"))
	{
		$result = substr($result, 3);
		parse_str($result);

		update_option("kameleoonSiteCode", $siteCode);
		update_option("kameleoonUserName", "wordpress");
		update_option("kameleoonPassword", $password);
		update_option("kameleoonAutoLogin", TRUE);

		header("Location: admin.php?page=kameleoon/kameleoon.php");
	}
	else
	{
		header("Location: admin.php?page=kameleoon/kameleoon.php&errorMessage=" . urlencode($result));
	}
}

function authenticateKameleoon()
{
	$handle = curl_init("http://www." . KAMELEOON_DOMAIN . "/api");
	$parameters = array("task" => "login", "authenticationKey" => KAMELEOON_AUTHENTICATION_KEY, "siteCode" => get_option("kameleoonSiteCode"), "userName" => get_option("kameleoonUserName"), "password" => get_option("kameleoonPassword"));

	curl_setopt($handle, CURLOPT_POST, TRUE);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($handle, CURLOPT_HTTPHEADER, array("Expect:"));
	curl_setopt($handle, CURLOPT_POSTFIELDS, $parameters);

	$result = curl_exec($handle);
	curl_close($handle);

	if (0 == strpos($result, "OK/"))
	{
		$result = substr($result, 3);
		parse_str($result);
		setcookie("kameleoonTemporaryAuthenticationToken", $temporaryAuthenticationToken, time() + 60 * 10, "/");
		setcookie("kameleoonSiteCode", get_option("kameleoonSiteCode"), 0, "/");
		setcookie("kameleoonMemberCode", "0", 0, "/");
	}

	header("Location: " . get_bloginfo("url") . "?kameleoon=true");
}

function kameleoonSettingsPage()
{
?>

<style type="text/css" media="screen">
.Twitter {
	background: url("http://images.kameleoon-dev.net/common/site/resources/twitter-button.png") bottom left no-repeat;
	color: #ffffff !important;
	border: solid 1px #5472ad;
	padding: 0px 10px 0px 34px;
	height: 25px;
	margin-right: 30px;
	cursor: pointer;
}
.Twitter:hover {
	background: url("http://images.kameleoon-dev.net/common/site/resources/twitter-button.png") top left no-repeat;
	border: solid 1px #445c8d;
}
.Facebook {
	background: url("http://images.kameleoon-dev.net/common/site/resources/facebook-button.png") bottom left no-repeat;
	color: #ffffff !important;
	border: solid 1px #5472ad;
	padding: 0px 10px 0px 34px;
	height: 25px;
	cursor: pointer;
}

.Facebook:hover {
	background: url("http://images.kameleoon-dev.net/common/site/resources/facebook-button.png") top left no-repeat;
	border: solid 1px #445c8d;
}</style>

<div class="wrap">
<h2>Kameleoon</h2>

<?php
if (! empty($_GET["errorMessage"]))
{
	echo '<div class="KameleoonErrorMessage" >' . $_GET["errorMessage"] . '</div>';
}
if ("returnStatusUpdate" == $_GET["action"])
{
	if ("twitter" == $_GET["media"])
	{
		echo '<div class="wrap" style="margin-bottom: 15px;">Thank you for helping to promote Kameleoon on Twitter!</div>';
	}
	if ("facebook" == $_GET["media"])
	{
		echo '<div class="wrap" style="margin-bottom: 15px;">Thank you for helping to promote Kameleoon on Facebook!</div>';
	}
}
?>

<?php
$kameleoonSiteCode = get_option("kameleoonSiteCode");
if (empty($kameleoonSiteCode))
{
?>

<div style="margin-bottom: 15px;">Your blog is not configured for use with Kameleoon yet.</div>

<div style="float: left; width: 40%; border-right: solid 1px #464646; padding-right: 15px; margin-right: 15px; margin-bottom: 10px;" >
	<div style="margin-bottom: 15px;">The easiest way to get started is to click the button below. It will register a free Kameleoon account on our servers automatically. You can then start modifying your blog's design.</div>

	<form style="text-align: center; margin-bottom: 25px;" method="post" action="admin-ajax.php?action=createUser">
		<input type="submit" class="button-primary" value="Create Account Instantly" />
	</form>
</div>

	<div style="margin-bottom: 15px;">Alternatively, if you already have a Kameleoon account (possibly because you manually registered it on our website), enter the corresponding site code below:</div>
	<form method="post" action="options.php">
		<?php settings_fields("kameleoon-settings-group"); ?>
		<table style="width: 310px; clear: none; margin-top: 0px; float: left;" class="form-table">
			<tr valign="top">
				<td scope="row">Kameleoon Site Code</td>
				<td><input style="width: 110px;" type="text" name="kameleoonSiteCode" value="" /></td>
			</tr>
		</table>
		<input type="hidden" name="kameleoonAutoLogin" value="true" />
		<input type="submit" style="margin-top: 8px;" class="button-primary" value="<?php _e('Use this site code') ?>" />
	</form>

<?php
}
else
{
?>



<div style="float: left; width: 40%; border-right: solid 1px #464646; padding-right: 15px; margin-right: 15px; margin-bottom: 10px;" >
	<div style="margin-bottom: 10px;">Kameleoon is ready to be used on your WordPress blog!
		<a href="<?php if (get_option("kameleoonAutoLogin")) { echo 'admin-ajax.php?action=authenticate';} else echo get_bloginfo("url") . '?kameleoon=true' ; ?>">Just click here</a>
		to start modifying your blog's design (there's also a permanent link on the top of the page).
	</div>

	<form method="post" action="options.php">
		<?php settings_fields("kameleoon-settings-group"); ?>

<div style="margin-bottom: 15px; overflow: hidden;">
		<table style="float: left; width: 310px; clear: none; margin-top: 0px;" class="form-table">
			<colgroup width="160" />
			<colgroup />
			<tr valign="top">
				<td scope="row">Kameleoon Site Code</td>
				<td><input style="width: 110px;" type="text" name="kameleoonSiteCode" <?php if ("wordpress" == get_option("kameleoonUserName")) { ?>disabled="disabled"<?php } ?>
						value="<?php echo get_option("kameleoonSiteCode"); ?>" /></td>
			</tr>
			<tr valign="top">
				<td scope="row">Kameleoon User Name</td>
				<td><input style="width: 110px;" type="text" name="kameleoonUserName" <?php if ("wordpress" == get_option("kameleoonUserName")) { ?>disabled="disabled"<?php } ?>
						value="<?php echo get_option("kameleoonUserName"); ?>" /></td>
			</tr>
			<tr valign="top">
				<td scope="row">Kameleoon Password</td>
				<td><input style="width: 110px;" type="text" name="kameleoonPassword" <?php if ("wordpress" == get_option("kameleoonUserName")) { ?>disabled="disabled"<?php } ?>
					value="<?php echo get_option("kameleoonPassword"); ?>" /></td>
			</tr>
		</table>

		<?php if ("wordpress" == get_option("kameleoonUserName")) { ?>
			<div style="float: left; overflow: hidden; padding: 8px; font-size: 10px; border: solid 1px grey; position: relative; margin-top: 20px; min-width: 150px; max-width: 30%;">Your account was created automatically by the plugin, so these settings cannot be modified.</div>
		<?php } ?>
</div>
			<div style="clear: both;">
				<input type="checkbox" name="kameleoonAutoLogin" value="true" <?php if (get_option("kameleoonAutoLogin")) { ?>checked="checked"<?php } ?> />
				<label for="kameleoonAutoLogin">Automatically log me into Kameleoon using the above credentials (recommended)</label>
				<p class="submit" style="text-align: center; padding-bottom: 2px;">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			</div>
	</form>
</div>

<div style="min-width: 300px;">
	<div style="margin-bottom: 15px;">If you like Kameleoon, please help promoting it by clicking on the following links:</div>
	<form style="margin-bottom: 15px; text-align: center;" method="post" action="http://www.<?php echo KAMELEOON_DOMAIN; ?>/connect/updateStatus" target="_blank">
		<textarea style="width: 52%; max-height: 100px; margin: 25px 0px 15px 0px;" name="status">I am using the Kameleoon WordPress plugin on my blog at <?php echo get_bloginfo("url"); ?>. Web design made easy!</textarea>
		<input type="hidden" name="callbackURL" value="<?php echo get_bloginfo("url") . "/wp-admin/admin.php?page=kameleoon/kameleoon.php&action=returnStatusUpdate"; ?>" />
		<input style="margin-right: 30px;" type="submit" class="Twitter" name="twitter" value="Update my Twitter Status" />
		<input type="submit" class="Facebook" name="facebook" value="Update my Facebook Status" />
	</form>

<div style="margin-top: 15px;">
	<a href="http://www.stumbleupon.com/url/www.kameleoon.com/"><img src="http://images.kameleoon.com/integrations/stumbleupon.png" alt="StumbleUpon" /></a>
	<a href="http://www.stumbleupon.com/url/www.kameleoon.com/">Promote on StumbleUpon</a>
</div>

	<div style="margin-top: 15px;">
		<a href="http://delicious.com/save" onclick="window.open('http://delicious.com/save?v=5&noui&jump=close&url=http://www.kameleoon.com/&title=Kameleoon', 'delicious','toolbar=no,width=550,height=550'); return false;"><img src="http://images.kameleoon.com/integrations/delicious.png" alt="Delicious" /></a>
		<a href="http://delicious.com/save" onclick="window.open('http://delicious.com/save?v=5&noui&jump=close&url=http://www.kameleoon.com/&title=Kameleoon', 'delicious','toolbar=no,width=550,height=550'); return false;">Bookmark this on Delicious</a>
	</div>

</div>

</div>
<?php } ?>

<div style="clear: both; margin-bottom: 15px; border-top: solid 1px #464646; padding-top: 10px; margin-top: 10px; margin-right: 20px; padding-right: 20px;">
	<p>Kameleoon is a web design tool that allows you to instantly modify the visual
	appearance of your blog (or other website). Thanks to its easy yet powerful GUI, you can author beautiful and professional looking themes - without knowing CSS or HTML.
	</p>
	<p>Kameleoon is compatible with all WordPress templates, which means you can install a template and further customize it to your tastes. However, <a href="http://www.kameleoon.com/ressources/wordpress">
	we maintain a collection of recommanded templates</a> (templates that have been analyzed and slightly modified by our team to guarantee you a full compatibility). Use one of these if you run into
	troubles with your own template (or contact us so we can port the template to Kameleoon).</p>
</div>

<div style="clear: both; margin-bottom: 25px;">Documentation is available at <a href="http://documentation.kameleoon.com">documentation.kameleoon.com.</a>

	To learn more about our service, go to <a href="http://www.kameleoon.com">www.kameleoon.com</a> or follow us on these popular sites:
</div>

<div style="text-align: center;">
	<a style="margin-right: 50px;" href="http://twitter.com/kameleoonrocks"><img src="http://www.twitterbuttons.com/images/lbn/twitterbutton-0206.gif" title="By: TwitterButtons.com" width="120" height="90" /></a>

	<script type="text/javascript" src="http://static.ak.connect.facebook.com/connect.php/en_US"></script><script type="text/javascript">FB.init("222314ed0b4b38929fff14bb5571c9fa");</script>
	<fb:fan profile_id="103656356337203" stream="0" connections="0" logobar="1" width="240"></fb:fan>
</div>

<p>Note: the free version of Kameleoon will automatically embed a Kameleoon banner in your blog.</p>

<?php } ?>