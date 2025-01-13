<?php
	# $normal_params = false;
	$APP_RootDir = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/"));
	require_once($APP_RootDir."private/script/start/API.php");
	// Execute
	$year = $_SESSION["stif"]["t_year"] ?? null;
	$isPBLmaster = has_perm("PBL");
	require_once($APP_RootDir."private/script/lib/TianTcl/various.php");
	define("PBL_ENC_KEY", "PBL-M4^/4g312");
	if (empty($APP_USER)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
	switch ($action) {
		case "list": {
			require($APP_RootDir."public_html/resource/php/core/config.php");
			switch ($command) {
				case "control": {
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
				case "names": {
					$result = array();
					function appendData($get_cmte, $readperm) {
						global $result, $TCL;
						if ($get_cmte -> num_rows) {
							$category = array();
							while ($read = $get_cmte -> fetch_assoc()) array_push($category, array(
								"impact" => $TCL -> encrypt("PBL-".$read["cmteid"]."cmte", PBL_ENC_KEY, 2),
								"user_reference" => $read["tchr"],
								"name" => prefixcode2text($read["namep"])["th"].$read["namefth"]."  ".$read["namelth"]
							)); if (count($category)) $result[$readperm] = $category;
						}
					} if ($isPBLmaster) foreach (str_split("ABCDEFGHIJKLM") as $readperm) {
						$get_cmte = $APP_DB[0] -> query("SELECT a.cmteid,a.tchr,b.namep,b.namefth,b.namelth FROM PBL_cmte a INNER JOIN user_t b ON b.namecode=a.tchr WHERE a.type='$readperm' AND a.year=$year AND a.allow='Y' ORDER BY b.namefth,b.namelth");
						appendData($get_cmte, $readperm);
					} else {
						$get_perm = $APP_DB[0] -> query("SELECT type FROM PBL_cmte WHERE tchr='$APP_USER' AND year=$year AND allow='Y' AND isHead='Y'");
						if ($get_perm -> num_rows) while ($readperm = $get_perm -> fetch_assoc()) {
							$get_cmte = $APP_DB[0] -> query("SELECT a.cmteid,a.tchr,b.namep,b.namefth,b.namelth FROM PBL_cmte a INNER JOIN user_t b ON b.namecode=a.tchr WHERE a.type='$readperm[type]' AND a.year=$year AND a.allow='Y' ORDER BY b.namefth,b.namelth");
							appendData($get_cmte, $readperm["type"]);
						} else if (!$get_perm) errorMessage(1, "You are not assigned as a head of any project type");
						else errorMessage(3, "Unable to read names");
					} if (count($result)) successState($result);
					else errorMessage(1, "ไม่มีกรรมการในสาขาที่ท่านได้รับมอบหมาย");
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
		case "assign": {
			switch ($command) {
				case "referee": {
					$cmte = escapeSQL(rtrim(ltrim($TCL -> decrypt($attr["committee"], PBL_ENC_KEY, 2), "PBL-"), "cmte"));
					$projects = explode("-", escapeSQL(base64_decode($attr["projects"])));
					$list = implode("', '", $projects);
					$stacked = implode(", ", $projects);
					if (!$isPBLmaster) {
						$get_perm = $APP_DB[0] -> query("SELECT a.cmteid FROM PBL_cmte a INNER JOIN PBL_group b ON b.code='$projects[0]' AND a.type=b.type WHERE a.tchr='$APP_USER' AND a.year=$year AND a.allow='Y' AND a.isHead='Y'");
						if (!$get_perm) {
							errorMessage(3, "Unable to check permission");
							syslog_a(null, "PBL", "assign", "grader", "$cmte: $stacked", false, "", "InvalidGetQuery");
						} else if (!$get_perm -> num_rows) {
							errorMessage(3, "You don't have permission to perform this action");
							syslog_a(null, "PBL", "assign", "grader", "$cmte: $stacked", false, "", "NoPermission");
						} else goto hasPerm1;
					} else {
						hasPerm1:
						$success = $APP_DB[0] -> query("UPDATE PBL_group SET lastupdate=lastupdate,grader=$cmte WHERE code IN ('$list')");
						if ($success) {
							successState(array(
								"referee" => $attr["committee"],
								"updated" => $projects
							)); syslog_a(null, "PBL", "assign", "grader", "$cmte: $stacked");
						} else syslog_a(null, "PBL", "assign", "grader", "$cmte: $stacked", false, "", "InvalidQuery");
					}
				break; }
				case "project": {
					$cmte = escapeSQL(rtrim(ltrim($TCL -> decrypt($attr["committee"], PBL_ENC_KEY, 2), "PBL-"), "cmte"));
					$projects = explode("-", escapeSQL(base64_decode($attr["projects"])));
					$list = implode("', '", $projects);
					$stacked = implode(", ", $projects);
					if (!$isPBLmaster) {
						$get_perm = $APP_DB[0] -> query("SELECT a.cmteid FROM PBL_cmte a INNER JOIN PBL_group b ON b.code='$projects[0]' AND a.type=b.type WHERE a.tchr='$APP_USER' AND a.year=$year AND a.allow='Y' AND a.isHead='Y'");
						if (!$get_perm) {
							errorMessage(3, "Unable to check permission");
							syslog_a(null, "PBL", "assign", "marker", "$cmte: $stacked", false, "", "InvalidGetQuery");
						} else if (!$get_perm -> num_rows) {
							errorMessage(3, "You don't have permission to perform this action");
							syslog_a(null, "PBL", "assign", "marker", "$cmte: $stacked", false, "", "NoPermission");
						} else goto hasPerm2;
					} else {
						hasPerm2:
						$findspot = $APP_DB[0] -> query("SELECT code,mbr1,mrker1,mrker2,mrker3,mrker4,mrker5 FROM PBL_group WHERE code IN ('$list')");
						if (!$findspot) {
							errorMessage(3, "Unable get information. Please try again.");
							syslog_a(null, "PBL", "assign", "marker", "$cmte: $stacked", false, "", "InvalidCheckQuery");
						} else if (!$findspot -> num_rows) {
							errorMessage(1, "None of the selected group is available for assigning");
							syslog_a(null, "PBL", "assign", "marker", "$cmte: $stacked", false, "", "NotFound");
						} else { // Check spot
							$proceed = array(); $refused = array(); $unexist = array(); $failed = array();
							while ($read = $findspot -> fetch_assoc()) {
								if (empty($read["mbr1"])) {
									array_push($unexist, $read["code"]);
									continue;
								} $spots = array();
								for ($index = 1; $index <= 5; $index++) $spots["mrker$index"] = $read["mrker$index"];
								$findDup = array_search($cmte, $spots);
								$myspot = array_search("", $spots);
								if ($findDup) array_push($refused, $read["code"]);
								else if (!$myspot) array_push($failed, $read["code"]);
								else {
									if (!array_key_exists($myspot, $proceed)) $proceed[$myspot] = array();
									array_push($proceed[$myspot], $read["code"]);
								}
							} $stackedR = implode(", ", $refused);
							$stackedU = implode(", ", $unexist);
							$stackedF = implode(", ", $failed);
							if (!empty($proceed)) {
								$list = implode("', '", array_merge(...array_values($proceed)));
								$stackedS = implode(", ", array_merge(...array_values($proceed)));
								$replace_query = "";
								foreach (array_keys($proceed) as $spot) {
									$proj_group = implode("', '", $proceed[$spot]);
									$replace_query .= ",$spot=(CASE WHEN code IN ('$proj_group') THEN $cmte ELSE $spot END)";
								} $success = $APP_DB[0] -> query("UPDATE PBL_group SET lastupdate=lastupdate$replace_query WHERE code IN ('$list')");
								/**
								 * S: Success
								 * R: Duplicate committee
								 * U: Unexisting project
								 * F: Full assignee
								 */
								if ($success) {
									successState(array(
										"referee" => $attr["committee"],
										"updated" => array_merge(...array_values($proceed))
									)); syslog_a(null, "PBL", "assign", "marker", "$cmte: S($stackedS) R($stackedR) U($stackedU) F($stackedF)");
									if (!empty($refused)) infoMessage(1, "Cannot assign committee to projects they're already assigned: $stackedR");
									if (!empty($unexist)) infoMessage(1, "Committee cannot be assigned to unexisting projects: $stackedU");
									if (!empty($failed)) infoMessage(2, "Committee cannot be assigned to projects that already have 5 assignees: $stackedF");
								} else {
									syslog_a(null, "PBL", "assign", "marker", "$cmte: S($stackedS) R($stackedR) U($stackedU) F($stackedF)", false, "", "InvalidQuery");
									if (!empty($refused)) infoMessage(1, "Cannot assign committee to projects they're already assigned: $stackedR");
									if (!empty($unexist)) errorMessage(1, "Committee cannot be assigned to unexisting projects: $stackedU");
									if (!empty($failed)) errorMessage(2, "Committee cannot be assigned to projects that already have 5 assignees: $stackedF");
								}
							} else {
								errorMessage(3, "Cannot assign committee to any selected project");
								syslog_a(null, "PBL", "assign", "marker", "$cmte: R($stackedR) U($stackedU) F($stackedF)", false, "", "Empty");
								if (!empty($refused)) infoMessage(1, "Cannot assign committee to projects they're already assigned: $stackedR");
								if (!empty($unexist)) errorMessage(1, "Committee cannot be assigned to unexisting projects: $stackedU");
								if (!empty($failed)) errorMessage(2, "Committee cannot be assigned to projects that already have 5 assignees: $stackedF");
							}
						}
					}
				break; }
				default: errorMessage(1, "Invalid command"); break;
			}
		break; }
		case "revoke": {
			switch ($command) {
				case "referee": {
					$project = escapeSQL($attr);
					if (!$isPBLmaster) {
						$get_perm = $APP_DB[0] -> query("SELECT a.cmteid FROM PBL_cmte a INNER JOIN PBL_group b ON b.code='$project' AND a.type=b.type WHERE a.tchr='$APP_USER' AND a.year=$year AND a.allow='Y' AND a.isHead='Y'");
						if (!$get_perm) {
							errorMessage(3, "Unable to check permission");
							syslog_a(null, "PBL", "revoke", "grader", $project, false, "", "InvalidGetQuery");
						} else if (!$get_perm -> num_rows) {
							errorMessage(3, "You don't have permission to perform this action");
							syslog_a(null, "PBL", "revoke", "grader", $project, false, "", "NoPermission");
						} else goto hasPerm3;
					} else {
						hasPerm3:
						$success = $APP_DB[0] -> query("UPDATE PBL_group SET lastupdate=lastupdate,grader=NULL WHERE code='$project'");
						if ($success) {
							successState();
							syslog_a(null, "PBL", "revoke", "grader", $project);
						} else syslog_a(null, "PBL", "revoke", "grader", $project, false, "", "InvalidQuery");
					}
				break; }
				case "project": {
					$project = escapeSQL($attr["project"]);
					$cmte = escapeSQL(rtrim(ltrim($TCL -> decrypt($attr["impact"], PBL_ENC_KEY, 2), "PBL-"), "cmte"));
					if (!$isPBLmaster) {
						$get_perm = $APP_DB[0] -> query("SELECT a.cmteid FROM PBL_cmte a INNER JOIN PBL_group b ON b.code='$project' AND a.type=b.type WHERE a.tchr='$APP_USER' AND a.year=$year AND a.allow='Y' AND a.isHead='Y'");
						if (!$get_perm) {
							errorMessage(3, "Unable to check permission");
							syslog_a(null, "PBL", "revoke", "marker", "$project: $cmte", false, "", "InvalidGetQuery");
						} else if (!$get_perm -> num_rows) {
							errorMessage(3, "You don't have permission to perform this action");
							syslog_a(null, "PBL", "revoke", "marker", "$project: $cmte", false, "", "NoPermission");
						} else goto hasPerm4;
					} else {
						hasPerm4:
						$replace_query = "";
						for ($index = 1; $index <= 5; $index++) $replace_query .= ",mrker$index=(CASE WHEN mrker$index=$cmte THEN NULL ELSE mrker$index END)";
						$success = $APP_DB[0] -> query("UPDATE PBL_group SET lastupdate=lastupdate$replace_query WHERE code='$project'");
						if ($success) {
							successState();
							syslog_a(null, "PBL", "revoke", "marker", "$project: $cmte");
						} else syslog_a(null, "PBL", "revoke", "marker", "$project: $cmte", false, "", "InvalidQuery");
					}
				break; }
				case "assignee": {
					$project = escapeSQL($attr);
					if (!$isPBLmaster) {
						$get_perm = $APP_DB[0] -> query("SELECT a.cmteid FROM PBL_cmte a INNER JOIN PBL_group b ON b.code='$project' AND a.type=b.type WHERE a.tchr='$APP_USER' AND a.year=$year AND a.allow='Y' AND a.isHead='Y'");
						if (!$get_perm) {
							errorMessage(3, "Unable to check permission");
							syslog_a(null, "PBL", "revoke", "assignee", "$project: $cmte", false, "", "InvalidGetQuery");
						} else if (!$get_perm -> num_rows) {
							errorMessage(3, "You don't have permission to perform this action");
							syslog_a(null, "PBL", "revoke", "assignee", "$project: $cmte", false, "", "NoPermission");
						} else goto hasPerm5;
					} else {
						hasPerm5:
						$replace_query = "";
						for ($index = 1; $index <= 5; $index++) $replace_query .= ",mrker$index=NULL";
						$success = $APP_DB[0] -> query("UPDATE PBL_group SET lastupdate=lastupdate$replace_query WHERE code='$project'");
						if ($success) {
							successState();
							syslog_a(null, "PBL", "revoke", "assignee", "$project: $cmte");
						} else syslog_a(null, "PBL", "revoke", "assignee", "$project: $cmte", false, "", "InvalidQuery");
					}
				break; }
				default: errorMessage(1, "Invalid command"); break;
			}
		break; }
		default: errorMessage(1, "Invalid type"); break;
	} $APP_DB[0] -> close();
	sendOutput();
?>