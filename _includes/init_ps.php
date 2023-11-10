<?php
	session_start(); ob_start();
	$my_url = ($_SERVER["REQUEST_URI"]=="/")?"":"?return_url=".urlencode(ltrim($_SERVER["REQUEST_URI"], "/")); // str_replace("#", "%23", "");
	if (preg_match("/^(((s|t)\/)?|\?return_url=(s|t)(%2F)?|account\/sign-in(-v\d+)?)$/", $my_url)) $my_url = "";
	if (!isset($dirPWroot)) $dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);

	// Permission checks
	function has_perm($what, $mods = true) {
		if (!(isset($_SESSION["auth"]) && $_SESSION["auth"]["type"]=="t")) return false;
		$mods = ($mods && $_SESSION["auth"]["level"]>=75); $perm = (in_array("*", $_SESSION["auth"]["perm"]) || in_array($what, $_SESSION["auth"]["perm"]));
		return ($perm || $mods);
	}

	// Redirection for authorized persons
	if ($normalized_control ?? true) {
		$url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
		$isPublicPage = preg_match("/^\/d\/(sandbox\/.*|css|font)$/", $url);
		// Not robot
		if (!preg_match('/(FBA(N|V)|facebookexternalhit|Line|line(-poker)?)/', $_SERVER["HTTP_USER_AGENT"])) {
			$usr = "user\/(\d{5}|[A-Z0-9a-z._]{3,30})";
			$require_sso = false; if (!isset($_SESSION["auth"]) && isset($_COOKIE["bdSSOv1a"]) && $_COOKIE["bdSSOv1a"]<>"") $require_sso = true;
			// Require basic authen
			else if (!isset($_SESSION["auth"]) && preg_match("/^\/((s|t|m|d|project)\/.*|service\/(app\/file-share\/|(4\/TianTcl\/)?dark-reg\/.*)|account\/(complete|my)|p\/manual\/.+)$/", $url)) {
				if (!$isPublicPage) header("Location: /account/sign-in$my_url");
			} else if (isset($_SESSION["auth"]["type"])) {
				if ($_SESSION["auth"]["req_CP"] && !preg_match("/^\/(account\/complete(\?return_url=.+)?)$/", $url)) {
					if (!preg_match("/^\/(e\/enroll\/.*)$/", $url)) header("Location: /account/complete$my_url");
				} else if (preg_match("/^\/account\/sign-in(-v\d+)?$/", $url)) header("Location: /".$_SESSION["auth"]["type"]."/");
				else if (!preg_match("/^\/(project\/.+|e\/.*|(p|account|resource|service|)\/.+|archive(d\/\d{10})?|$usr(\/(edit|avatar))?|go)$/", $url) && !$isPublicPage) {
					// Not all authened zone
					if ($_SESSION["auth"]["type"]=="s" && !preg_match("/^\/(s)\/.*$/", $url)) $redirect = true; // isStd
					else if ($_SESSION["auth"]["type"]=="t") { // isTch
						$is_mod = $_SESSION["auth"]["level"] >= 75; $is_dev = has_perm("dev");
						if (!$is_dev && !$is_mod && !preg_match("/^\/t\/.*$/", $url)) $redirect = true;
						else if (!$is_dev && $is_mod && !preg_match("/^\/(m|t)\/.*$/", $url)) $redirect = true;
						else if ($is_dev && !$is_mod && !preg_match("/^\/(d|t)\/.*$/", $url)) $redirect = true;
						else if ($is_dev && $is_mod && !preg_match("/^\/(d|m|t)\/.*$/", $url)) $redirect = true;
					} if (isset($redirect)) {
						if (isset($_GET["return_url"])) header("Location: /account/sign-in".$_GET["return_url"]);
						else header("Location: /".$_SESSION["auth"]["type"]."/");
					}
				}
			}
		}
	} if (!isset($require_sso)) $require_sso = false;
	$my_url = "account/sign-in$my_url";

	// App cookie settings
	/* $exptimeout = strval(time()+31536000);
	if (!isset($_COOKIE["set_theme"])) setcookie("set_theme", "light", $exptimeout, "/");
	if (!isset($_COOKIE["set_lang"])) setcookie("set_lang", "th", $exptimeout, "/"); */
	
	// Private pages
	function is_private($set = true) {
		if (!$set || ($set && isset($_SESSION["auth"]))) return false;
		else return true;
	}
?>