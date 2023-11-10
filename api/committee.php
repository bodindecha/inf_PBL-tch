<?php
	# $normal_params = false;
	$APP_RootDir = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/"));
	require_once($APP_RootDir."private/script/start/API.php");
	// Execute
	$year = $_SESSION["stif"]["t_year"];
	require_once($APP_RootDir."private/script/lib/TianTcl/various.php");
	define("PBL_ENC_KEY", "PBL-M4^/4g312");
	switch ($action) {
		case "list": {
			switch ($command) {
				case "control": {
					require($APP_RootDir."public_html/resource/php/core/config.php");
					$get = $APP_DB[0] -> query("SELECT a.cmteid,a.type,a.allow,a.isHead,b.namep,CONCAT(b.namefth, '  ', b.namelth) AS namefull FROM PBL_cmte a INNER JOIN user_t b ON a.tchr=b.namecode WHERE a.cmteid>0 AND a.year=$year ORDER BY a.type,b.namefth,b.namelth");
					if (!$get) errorMessage(3, "Unable to load comittee list.");
					else {
						$cmtes = array();
						if ($get -> num_rows) while ($read = $get -> fetch_assoc()) {
							array_push($cmtes, array(
								"impact" => $TCL -> encrypt("PBL-".$read["cmteid"]."cmte", PBL_ENC_KEY, 2),
								"name" => prefixcode2text($read["namep"])["th"].$read["namefull"],
								"branch" => pblcode2text($read["type"])["th"],
								"active" => $read["allow"] == "Y",
								"chief" => $read["isHead"] == "Y"
							));
						} successState(array("ifo" => $cmtes));
					}
				break; }
				default: errorMessage(1, "Invalid command"); break;
			}
		break; }
		case "mod": {
			switch ($command) {
				case "setStatus": {
					$user = escapeSQL(rtrim(ltrim($TCL -> decrypt($attr["target"], PBL_ENC_KEY, 2), "PBL-"), "cmte"));
					$status = $attr["state"]=="true" ? "Y" : "N";
					$field = escapeSQL($attr["field"]);
					$success = $APP_DB[0] -> query("UPDATE PBL_cmte SET $field='$status' WHERE cmteid=$user");
					if ($success) {
						syslog_a(null, "PBL", "edit", "cmte", "$user: $field=$status");
						successState();
					} else syslog_a(null, "PBL", "edit", "cmte", "$user: $field=$status", false, "", "InvalidQuery");
				break; }
				case "assign": {
					$type = escapeSQL($attr["type"]);
					$users = implode("','$type'),($year,'", explode(", ", base64_decode($attr["candidate"])));
					$logData = str_replace(" ", "", base64_decode($attr["candidate"]));
					$success = $APP_DB[0] -> query("INSERT INTO PBL_cmte (year,tchr,type) VALUES($year,'$users','$type')");
					if ($success) {
						syslog_a(null, "PBL", "new", "cmte", "$type: $logData");
						successState();
					} else {
						errorMessage(3, "There's an error. Please try again.");
						syslog_a(null, "PBL", "new", "cmte", "$type: $logData", false, "", "InvalidQuery");
					}
				break; }
				default: errorMessage(1, "Invalid command"); break;
			}
		break; }
		default: errorMessage(1, "Invalid type"); break;
	} $APP_DB[0] -> close();
	sendOutput();
?>