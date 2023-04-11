<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);
	require_once($dirPWroot."resource/php/extend/_RGI.php");
	// Permission checks
	function has_perm($what, $mods = true) {
		if (!(isset($_SESSION["auth"]) && $_SESSION["auth"]["type"]=="t")) return false;
		$mods = ($mods && $_SESSION["auth"]["level"]>=75); $perm = (in_array("*", $_SESSION["auth"]["perm"]) || in_array($what, $_SESSION["auth"]["perm"]));
		return ($perm || $mods);
	}
	// Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"]; $isPBLmaster = has_perm("PBL");
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
	switch ($type) {
		case "load": {
			switch ($command) {
				case "file": {
					$code = escapeSQL($attr["code"]);
					$filePos = intval(escapeSQL($attr["file"]));
					$fileCfg = array(
						"mindmap"		=> "Mindmap",
						"IS1-1"			=> "ใบงาน IS1-1",
						"IS1-2"			=> "ใบงาน IS1-2",
						"IS1-3"			=> "ใบงาน IS1-3",
						"report-1"		=> "รายงานโครงงานบทที่ 1",
						"report-2"		=> "รายงานโครงงานบทที่ 2",
						"report-3"		=> "รายงานโครงงานบทที่ 3",
						"report-4"		=> "รายงานโครงงานบทที่ 4",
						"report-5"		=> "รายงานโครงงานบทที่ 5",
						"report-all"	=> "รายงานฉบับสมบูรณ์",
						"abstract"		=> "Abstract",
						"poster"		=> "Poster"
					); $file = array_keys($fileCfg)[$filePos];
					$get = $db -> query("SELECT year,grade,fileStatus,fileType FROM PBL_group WHERE code='$code'");
					if (!$get) errorMessage(3, "Error loading your data. Please try again.");
					else if (!$get -> num_rows) errorMessage(3, "This group does not exist.");
					else {
						$read = $get -> fetch_array(MYSQLI_ASSOC);
						if (intval($read["fileStatus"])&pow(2, $filePos)) {
							$extension = explode(";", $read["fileType"])[$filePos];
							$year = $read["year"]; $grade = $read["grade"];
							$path = "resource/upload/PBL/$year/$file/$grade/$code.$extension";
							$finder = $dirPWroot.$path;
							if (file_exists($finder)) {
								$get_submit = $db -> query("SELECT LEFT(time, 19) AS time FROM log_action WHERE app='PBL' AND cmd='new' AND act='file' AND 
								data='$code: $file' AND val='pass' ORDER BY time DESC,logid DESC LIMIT 1");
								$submit_time = ($get_submit && $get_submit -> num_rows) ? date("ส่งเมื่อวันที่ d/m/Y เวลา H:iน.", strtotime(($get_submit -> fetch_array(MYSQLI_ASSOC))["time"])) : "ยังไม่ส่งไฟล์";
								successState(array(
									"link" => "/resource/file/viewer?furl=".urlencode($path)."&name=$code%20-%20$file",
									"date" => $submit_time
								));
							} else errorMessage(3, "File not found");
						} else errorMessage(1, "File has not been submitted");
					}
				} break;
				case "score": {
					$code = escapeSQL($attr["code"]);
					$file = array(
						9 => "paper",
						10 => "present",
						11 => "poster"
					)[intval(escapeSQL($attr["file"]))];
					$get = $db -> query("SELECT COALESCE(score_$file, 0) AS score FROM PBL_group WHERE code='$code'");
					if (!$get) errorMessage(3, "Unable to load scores");
					else if (!$get -> num_rows) errorMessage(3, "Unable to get scores");
					else successState(array(
						"score" => intval(($get -> fetch_array(MYSQLI_ASSOC))["score"]))
					);
				} break;
				case "mark": {
					$code = escapeSQL($attr);
					$checkMaster = $isPBLmaster ? "" : "AND b.allow='Y' AND b.type=a.type";
					$get_score = $db -> query("SELECT a.type,c.raw AS score,c.note FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' $checkMaster LEFT JOIN PBL_score c ON c.code=a.code AND c.cmte=b.cmteid WHERE a.code='$code'");
					$get_submit = $db -> query("SELECT LEFT(time, 19) AS time FROM log_action WHERE app='PBL' AND cmd='new' AND act='file' AND data='$code: report-all' AND val='pass' ORDER BY time DESC LIMIT 1");
					$submit_time = ($get_submit && $get_submit -> num_rows) ? date("ส่งเมื่อวันที่ d/m/Y เวลา H:iน.", strtotime(($get_submit -> fetch_array(MYSQLI_ASSOC))["time"])) : "";
					if (!$get_score) errorMessage(3, "Unable to load scores");
					else if ($get_score -> num_rows <> 1) errorMessage(3, "Unable to get scores");
					else {
						$read_score = $get_score -> fetch_array(MYSQLI_ASSOC);
						$read_score["note"] = str_replace("&quot;", "\"", $read_score["note"]);
						$read_score["submit"] = $submit_time;
						successState($read_score);
					}
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		case "save": {
			switch ($command) {
				case "score": {
					$code = escapeSQL($attr["code"]);
					$file = array(
						9 => "paper",
						10 => "present",
						11 => "poster"
					)[intval(escapeSQL($attr["file"]))];
					$score = intval(escapeSQL($attr["newPoints"]));
					if ($score < 0 || $score > array("paper" => 3, "present" => 1, "poster" => 1)[$file]) {
						errorMessage(3, "Invalid score");
						slog("PBL", "edit", "score", "$code: $file -> $score", "fail", "", "NotEligible");
					} else {
						$success = $db -> query("UPDATE PBL_group SET score_$file=$score,lastupdate=lastupdate WHERE code='$code'");
						if ($success) {
							successState();
							slog("PBL", "edit", "score", "$code: $file -> $score", "pass");
						} else {
							errorMessage(3, "Unable to save score.");
							slog("PBL", "edit", "score", "$code: $file -> $score", "fail", "", "InvalidQuery");
						}
					}
					
				} break;
				case "rank": {
					$code = escapeSQL($attr["code"]);
					$rank = escapeSQL(strtoupper($attr["rank"]));
					$set = ($rank<>"NULL") ? "'$rank'" : $rank;
					$checkMaster = $isPBLmaster ? "" : "allow='Y' AND";
					$get_perm = $db -> query("SELECT GROUP_CONCAT(type) AS types FROM PBL_cmte WHERE $checkMaster tchr='$self' AND year=$year GROUP BY tchr");
					$get_type = $db -> query("SELECT type FROM PBL_group WHERE code='$code'");
					if (!$get_perm || !$get_type) {
						errorMessage(3, "Unable to get permission");
						slog("PBL", "edit", "rank", "$code: $rank", "fail", "", "Unavailable");
					} else if ($get_perm -> num_rows == 1 && $get_type -> num_rows == 1) {
						$readperm = explode(",", ($get_perm -> fetch_array(MYSQLI_ASSOC))["types"]);
						if (!in_array(($get_type -> fetch_array(MYSQLI_ASSOC))["type"], $readperm) && !$isPBLmaster) {
							errorMessage(3, "You don't have permission to grade this project");
							slog("PBL", "edit", "rank", "$code: $rank", "fail", "", "NotEligible");
						} else {
							$success = $db -> query("UPDATE PBL_group SET reward=$set,lastupdate=lastupdate WHERE code='$code'");
							if ($success) {
								successState();
								slog("PBL", "edit", "rank", "$code: $rank", "pass");
							} else {
								errorMessage(3, "Unable to save result.");
								slog("PBL", "edit", "rank", "$code: $rank", "fail", "", "InvalidQuery");
							}
						}
					} else {
						errorMessage(3, "Unable to read permission");
						slog("PBL", "edit", "rank", "$code: $rank", "fail", "", "Incorrect");
					}
				} break;
				case "mark": {
					$code = escapeSQL($attr["code"]);
					$total = escapeSQL($attr["total"]);
					$raw = escapeSQL($attr["raw"]);
					$note = escapeSQL($attr["note"]);
					// Check update
					$checkMaster = $isPBLmaster ? "" : "AND b.allow='Y' AND b.type=a.type";
					$get = $db -> query("SELECT b.cmteid,c.refID FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' $checkMaster LEFT JOIN PBL_score c ON c.cmte=b.cmteid AND c.code=a.code WHERE a.code='$code'");
					if (!$get) {
						errorMessage(3, "Unable to load permission");
						slog("PBL", "save", "mark", "$code: $raw|$total|$note", "fail", "", "Unavailable");
					} else if (!$get -> num_rows) {
						errorMessage(3, "Unable to get permission");
						slog("PBL", "save", "mark", "$code: $raw|$total|$note", "fail", "", "NotExisted");
					} else {
						$read = $get -> fetch_array(MYSQLI_ASSOC);
						$cmte = $read["cmteid"];
						$note = htmlspecialchars($note);
						if (empty($read["refID"])) {
							$success = $db -> query("INSERT INTO PBL_score (cmte,code,raw,total,note,ip) VALUES($cmte,'$code','$raw',$total,'$note','$ip')");
							$action = "new";
						} else {
							$success = $db -> query("UPDATE PBL_score SET cmte=$cmte,code='$code',raw='$raw',total=$total,note='$note',ip='$ip' WHERE refID=".$read["refID"]);
							$action = "edit";
						} if ($success) {
							successState();
							slog("PBL", $action, "mark", "$code: $total", "pass");
						} else {
							errorMessage(3, "Unable to save changes");
							slog("PBL", $action, "mark", "$code: $raw|$total|$note", "fail", "", "InvalidQuery");
						}
					}
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		default: errorMessage(1, "Invalid type"); break;
	} $db -> close();
	sendOutput($return);
?>