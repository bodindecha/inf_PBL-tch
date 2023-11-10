<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);
	require($dirPWroot."resource/hpe/init_ps.php");
	require_once($dirPWroot."resource/php/core/reload_settings.php");
	require_once($dirPWroot."resource/php/core/config.php"); require($dirPWroot."resource/php/core/db_connect.php");
	require($dirPWroot."resource/php/core/getip.php");
	$return = array();
	function escapeSQL($input) {
		global $db;
		return $db -> real_escape_string($input);
	} // Constants
	$fileCfg = array(
		"mindmap"		=> "Mindmap",
		"IS1-1"			=> "ใบงาน IS1-1",
		"IS1-2"			=> "ใบงาน IS1-2",
		"IS1-3"			=> "ใบงาน IS1-3",
		"report-1"		=> "รายงานโครงงานบทที่ 1",
		"report-2"		=> "รายงานโครงงานบทที่ 2",
		"report-3"		=> "รายงานโครงงานบทที่ 3",
		"report-4"		=> "รายงานโครงงานบทที่ 4",
		"report-5"		 => "รายงานโครงงานบทที่ 5",
		"report-all"	=> "รายงานฉบับสมบูรณ์",
		"abstract"		=> "Abstract",
		"poster"		=> "Poster"
	); $fileExts = array("png", "jpg", "jpeg", "heic", "heif", "gif", "pdf");
	// Variables
	$self = $_SESSION["auth"]["user"] ?? null; # $year = $_SESSION["stif"]["t_year"];
	# $grade = $_SESSION["auth"]["info"]["grade"]; $room = $_SESSION["auth"]["info"]["room"];
	$code = escapeSQL(trim($_REQUEST["code"] ?? ""));
	$file = escapeSQL($_REQUEST["file"]); $filePos = array_search($file, array_keys($fileCfg));
	// Execute
	if (empty($code)) $redirect = "/error/902"; else
	if (empty($self)) $redirect = "/$my_url"; else {
		$get = $db -> query("SELECT year,code,grade,fileStatus,fileType FROM PBL_group WHERE code='$code'");
		if (!$get) $redirect = "/error/905";
		else if (!$get -> num_rows)
			$redirect = "/error/902";
		else {
			$read = $get -> fetch_array(MYSQLI_ASSOC);
			$code = $read["code"];
			if (intval($read["fileStatus"])&pow(2, $filePos)) {
				$extension = explode(";", $read["fileType"])[$filePos];
				$grade = $read["grade"];
				$year = $read["year"];
				$path = "resource/upload/PBL/$year/$file/$grade/$code.$extension";
				$finder = $dirPWroot.$path;
				$redirect = (file_exists($finder) ? "/resource/file/viewer?furl=".urlencode($path)."&name=$code%20-%20$fileCfg[$file]" : "/error/900");
			} else $redirect = "/error/900";
		}
	} $db -> close();
	header("Location: $redirect"); exit(0);
?>