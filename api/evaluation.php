<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);
	require_once($dirPWroot."resource/php/extend/_RGI.php");
	require_once($dirPWroot."resource/php/core/config.php");
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
		case "list": {
			switch ($command) {
				case "paper-grade": {
					$result = array();
					function appendData($get_groups, $readperm) {
						global $result;
						if ($get_groups -> num_rows >= 1) {
							$category = array(); $grade = "0"; $grades = array();
							while ($readgroup = $get_groups -> fetch_assoc()) {
								if ($readgroup["grade"]<>$grade) {
									if (count($grades)) {
										$category[$grade] = $grades;
										$grades = array();
									} $grade = $readgroup["grade"];
								} # $submit_time = !empty($readgroup["time"]) ? date("ส่งเมื่อวันที่ d/m/Y เวลา H:i น.", strtotime($readgroup["time"])) : "";
								array_push($grades, array(
									"code" => $readgroup["code"],
									"name" => $readgroup["name"],
									"rank" => $readgroup["reward"],
									"sent" => boolval($readgroup["sent"]),
									# "time" => $submit_time
								));
							} if (count($grades)) $category[$grade] = $grades;
							if (count($category)) $result[pblcode2text($readperm)["th"]] = $category;
						}
					}
					if ($isPBLmaster) foreach (str_split("ABCDEFGHIJKLM") as $readperm) {
						$get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,a.reward FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' WHERE a.mbr1 IS NOT NULL AND a.type='$readperm' AND a.year=$year GROUP BY a.code ORDER BY a.grade,a.code");
						# $get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,a.reward,LEFT(c.time, 19) AS time FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' LEFT JOIN log_action c ON c.app='PBL' AND c.cmd='new' AND c.act='file' AND c.data=CONCAT(a.code, ': report-all') AND c.val='pass' WHERE a.mbr1 IS NOT NULL AND a.type='$readperm' AND a.year=$year GROUP BY a.code ORDER BY a.grade,a.code,c.time DESC");
						appendData($get_groups, $readperm);
					} else {
						$get_perm = $db -> query("SELECT type,isHead FROM PBL_cmte WHERE allow='Y' AND tchr='$self' AND year=$year");
						if ($get_perm -> num_rows >= 1) {
							$timedout = false;
							while ($readperm = $get_perm -> fetch_assoc()) {
								if (date("Y-m-d H:i:s") > date("2024-01-07 23:59:59") && $readperm["isHead"] <> "Y") {
									$timedout = true;
									continue;
								} $get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,a.reward FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' AND b.allow='Y' AND b.type='".$readperm["type"]."' WHERE a.mbr1 IS NOT NULL AND a.type='".$readperm["type"]."' AND a.year=$year AND (a.grader IS NULL OR a.grader=b.cmteid) ORDER BY a.grade,a.code");
								# $get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,a.reward,LEFT(c.time, 19) AS time FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' AND b.allow='Y' AND b.type='".$readperm["type"]."' LEFT JOIN log_action c ON c.app='PBL' AND c.cmd='new' AND c.act='file' AND c.data=CONCAT(a.code, ': report-all') AND c.val='pass' WHERE a.mbr1 IS NOT NULL AND a.type='".$readperm["type"]."' AND a.year=$year AND (a.grader IS NULL OR a.grader=b.cmteid) ORDER BY a.grade,a.code,c.time DESC");
								appendData($get_groups, $readperm["type"]);
							}
						} else if ($get_perm) errorMessage(1, "You are not assigned to mark any of the projects");
						else errorMessage(3, "Unable to read permission");
					} if (count($result)) successState($result);
					else errorMessage(1, "ไม่มีโครงงานให้ตรวจในสาขาที่ท่านได้รับมอบหมาย");
				} break;
				case "paper-mark": {
					$result = array();
					function appendData($get_groups, $readperm) {
						global $result;
						if ($get_groups -> num_rows >= 1) {
							$category = array(); $grade = "0"; $grades = array();
							while ($readgroup = $get_groups -> fetch_assoc()) {
								if ($readgroup["grade"]<>$grade) {
									if (count($grades)) {
										$category[$grade] = $grades;
										$grades = array();
									} $grade = $readgroup["grade"];
								} # $submit_time = !empty($readgroup["time"]) ? date("ส่งเมื่อวันที่ d/m/Y เวลา H:i น.", strtotime($readgroup["time"])) : "";
								array_push($grades, array(
									"code" => $readgroup["code"],
									"name" => $readgroup["name"],
									"mark" => boolval($readgroup["mark"]),
									"sent" => boolval($readgroup["sent"]),
									# "time" => $submit_time,
									"aogc" => $readgroup["aogc"],
								));
							} if (count($grades)) $category[$grade] = $grades;
							if (count($category)) $result[pblcode2text($readperm)["th"]] = $category;
						}
					}
					if ($isPBLmaster) foreach (str_split("ABCDEFGHIJKLM") as $readperm) {
						# $get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,c.total AS mark FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' LEFT JOIN PBL_score c ON a.code=c.code AND c.cmte=b.cmteid WHERE a.mbr1 IS NOT NULL AND a.type='$readperm' AND a.year=$year AND NOT a.reward='5N' AND a.reward IS NOT NULL GROUP BY a.code ORDER BY a.grade,a.code");
						# $get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,c.total AS mark,LEFT(d.time, 19) AS time FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' LEFT JOIN PBL_score c ON a.code=c.code AND c.cmte=b.cmteid LEFT JOIN log_action d ON d.app='PBL' AND d.cmd='new' AND d.act='file' AND d.data=CONCAT(a.code, ': report-all') AND d.val='pass' WHERE a.mbr1 IS NOT NULL AND a.type='$readperm' AND a.year=$year AND NOT a.reward='5N' AND a.reward IS NOT NULL GROUP BY a.code ORDER BY a.grade,a.code,d.time DESC");
						$get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,c.total AS mark,(SELECT COUNT(d.cmte) FROM PBL_score d WHERE d.code=a.code) AS aogc FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' LEFT JOIN PBL_score c ON a.code=c.code AND c.cmte=b.cmteid WHERE a.mbr1 IS NOT NULL AND a.type='$readperm' AND a.year=$year AND NOT a.reward='5N' AND a.reward IS NOT NULL GROUP BY a.code ORDER BY a.grade,a.code");
						appendData($get_groups, $readperm);
					} else {
						$get_perm = $db -> query("SELECT type FROM PBL_cmte WHERE allow='Y' AND tchr='$self' AND year=$year");
						if ($get_perm -> num_rows >= 1) while ($readperm = $get_perm -> fetch_assoc()) {
							# $get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,c.total AS mark FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' AND b.allow='Y' AND b.type=a.type LEFT JOIN PBL_score c ON a.code=c.code AND c.cmte=b.cmteid WHERE a.mbr1 IS NOT NULL AND a.type='".$readperm["type"]."' AND a.year=$year AND NOT a.reward='5N' AND a.reward IS NOT NULL AND (b.cmteid IN(mrker1, mrker2, mrker3, mrker4, mrker5) OR CONCAT(COALESCE(mrker1, ''), COALESCE(mrker2, ''), COALESCE(mrker3, ''), COALESCE(mrker4, ''), COALESCE(mrker5, ''))='') ORDER BY a.grade,a.code");
							# $get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,c.total AS mark,LEFT(d.time, 19) AS time FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' AND b.allow='Y' AND b.type=a.type LEFT JOIN PBL_score c ON a.code=c.code AND c.cmte=b.cmteid LEFT JOIN log_action d ON d.app='PBL' AND d.cmd='new' AND d.act='file' AND d.data=CONCAT(a.code, ': report-all') AND d.val='pass' WHERE a.mbr1 IS NOT NULL AND a.type='".$readperm["type"]."' AND a.year=$year AND NOT a.reward='5N' AND a.reward IS NOT NULL AND (b.cmteid IN(mrker1, mrker2, mrker3, mrker4, mrker5) OR CONCAT(COALESCE(mrker1, ''), COALESCE(mrker2, ''), COALESCE(mrker3, ''), COALESCE(mrker4, ''), COALESCE(mrker5, ''))='') ORDER BY a.grade,a.code,d.time DESC");
							$get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,c.total AS mark,(SELECT COUNT(d.cmte) FROM PBL_score d WHERE d.code=a.code) AS aogc FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' AND b.allow='Y' AND b.type=a.type LEFT JOIN PBL_score c ON a.code=c.code AND c.cmte=b.cmteid WHERE a.mbr1 IS NOT NULL AND a.type='".$readperm["type"]."' AND a.year=$year AND NOT a.reward='5N' AND a.reward IS NOT NULL AND (b.cmteid IN(mrker1, mrker2, mrker3, mrker4, mrker5) OR CONCAT(COALESCE(mrker1, ''), COALESCE(mrker2, ''), COALESCE(mrker3, ''), COALESCE(mrker4, ''), COALESCE(mrker5, ''))='') ORDER BY a.grade,a.code");
							appendData($get_groups, $readperm["type"]);
						} else if ($get_perm) errorMessage(1, "You are not assigned to mark any of the projects");
						else errorMessage(3, "Unable to read permission");
					} if (count($result)) successState($result);
					else errorMessage(1, "ไม่มีโครงงานให้ตรวจในสาขาที่ท่านได้รับมอบหมาย");
				} break;
				case "graded-committee": {
					$code = escapeSQL($attr);
					$get = $db -> query("SELECT c.namep,c.namefth,c.namelth FROM PBL_score a INNER JOIN PBL_cmte b ON a.cmte=b.cmteid INNER JOIN user_t c ON b.tchr=c.namecode WHERE a.code='$code' ORDER BY a.time");
					if (!$get) errorMessage(3, "Unable to load graded committee names");
					else if (!$get -> num_rows) errorMessage(3, "Unable to get graded committee names"); 
					else {
						$logc = array(); while ($read = $get -> fetch_assoc())
							array_push($logc, prefixcode2text($read["namep"])["th"].$read["namefth"]."  ".$read["namelth"]);
						successState($logc);
					}
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		default: errorMessage(1, "Invalid type"); break;
	} $db -> close();
	sendOutput($return);
?>