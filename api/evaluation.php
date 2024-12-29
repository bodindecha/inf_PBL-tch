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
					$TIMEOUT = array(
						2566 => "2024-01-08 23:59:59",
						2567 => "2025-01-10 23:59:59"
					)[$year];
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
						appendData($get_groups, $readperm);
					} else {
						$get_perm = $db -> query("SELECT type,isHead FROM PBL_cmte WHERE allow='Y' AND tchr='$self' AND year=$year");
						if ($get_perm -> num_rows >= 1) {
							$timedout = false;
							while ($readperm = $get_perm -> fetch_assoc()) {
								if (date("Y-m-d H:i:s") > $TIMEOUT && $readperm["isHead"] <> "Y") {
									$timedout = true;
									continue;
								} $get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,a.reward FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' AND b.allow='Y' AND b.type='".$readperm["type"]."' WHERE a.mbr1 IS NOT NULL AND a.type='".$readperm["type"]."' AND a.year=$year AND (a.grader IS NULL OR a.grader=b.cmteid OR b.isHead='Y') ORDER BY a.grade,a.code");
								appendData($get_groups, $readperm["type"]);
							}
						} else if ($get_perm) errorMessage(1, "You are not assigned to mark any of the projects");
						else errorMessage(3, "Unable to read permission");
					} if (count($result)) successState($result);
					else errorMessage(1, "ไม่มีโครงงานให้ตรวจในสาขาที่ท่านได้รับมอบหมาย");
				} break;
				case "paper-mark": {
					$TIMEOUT = array(
						2566 => "2024-01-18 13:30:00",
						2567 => "2025-01-17 23:59:59"
					)[$year];
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
						$get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,c.total AS mark,(SELECT COUNT(d.cmte) FROM PBL_score d WHERE d.code=a.code) AS aogc FROM PBL_group a LEFT JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' LEFT JOIN PBL_score c ON a.code=c.code AND c.cmte=b.cmteid WHERE a.mbr1 IS NOT NULL AND a.type='$readperm' AND a.year=$year AND NOT a.reward='5N' AND a.reward IS NOT NULL GROUP BY a.code ORDER BY a.grade,a.code");
						appendData($get_groups, $readperm);
					} else {
						$get_perm = $db -> query("SELECT cmteid,type,isHead FROM PBL_cmte WHERE allow='Y' AND tchr='$self' AND year=$year");
						if ($get_perm -> num_rows >= 1) {
							$timedout = false;
							while ($readperm = $get_perm -> fetch_assoc()) {
								if (date("Y-m-d H:i:s") > $TIMEOUT && $readperm["isHead"] <> "Y") {
									$timedout = true;
									continue;
								} $get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.fileStatus&512 AS sent,c.total AS mark,(SELECT COUNT(d.cmte) FROM PBL_score d WHERE d.code=a.code) AS aogc FROM PBL_group a INNER JOIN PBL_cmte b ON b.cmteid=$readperm[cmteid] LEFT JOIN PBL_score c ON a.code=c.code AND c.cmte=b.cmteid WHERE a.mbr1 IS NOT NULL AND a.type='$readperm[type]' AND a.year=$year AND NOT a.reward='5N' AND a.reward IS NOT NULL AND (b.cmteid IN(mrker1, mrker2, mrker3, mrker4, mrker5) OR CONCAT(COALESCE(mrker1, ''), COALESCE(mrker2, ''), COALESCE(mrker3, ''), COALESCE(mrker4, ''), COALESCE(mrker5, ''))='' OR b.isHead='Y') ORDER BY a.grade,a.code");
								appendData($get_groups, $readperm["type"]);
							} 
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
		case "setRank": {
			require_once($dirPWroot."../private/script/lib/TianTcl/various.php");
			function createToken() {
				global $TCL;
				$token = uniqid("PBL:");
				$TCL -> sessVar("PBL-token", $token);
				return $TCL -> encrypt($token, "PBL[master]{PSR}");
			}
			function checkToken($token) {
				global $TCL;
				$token = $TCL -> decrypt($token, "PBL[master]{PSR}");
				$compare = $TCL -> sessVar("PBL-token");
				$result = $token == $compare;
				if ($result) $TCL -> sessVar("PBL-token", null);
				return $result;
			}
			switch ($command) {
				case "getPreview": {
					if (!$isPBLmaster) {
						errorMessage(3, "You don't have permission to view this data");
						slog("PBL", "load", "reward", "", "fail", "", "Unauthorized");
					} else {
						$get = $db -> query("SELECT a.code,a.grade,a.room,a.type,(SELECT ROUND(SUM(b.total)/COUNT(b.cmte)) FROM PBL_score b WHERE b.code=a.code GROUP BY b.code) AS score,a.reward,(CASE WHEN (SELECT ROUND(SUM(b.total)/COUNT(b.cmte)) FROM PBL_score b WHERE b.code=a.code GROUP BY b.code) BETWEEN 50 AND 59 THEN '4M' WHEN (SELECT ROUND(SUM(b.total)/COUNT(b.cmte)) FROM PBL_score b WHERE b.code=a.code GROUP BY b.code) BETWEEN 60 AND 69 THEN '3B' WHEN (SELECT ROUND(SUM(b.total)/COUNT(b.cmte)) FROM PBL_score b WHERE b.code=a.code GROUP BY b.code) BETWEEN 70 AND 79 THEN '2S' WHEN (SELECT ROUND(SUM(b.total)/COUNT(b.cmte)) FROM PBL_score b WHERE b.code=a.code GROUP BY b.code) BETWEEN 80 AND 100 THEN '1G' WHEN a.reward IN ('1G', '2S', '3B', '4M') THEN '0P' ELSE a.reward END) AS new_reward FROM PBL_group a WHERE a.year=$year AND a.reward IS NOT NULL AND a.reward != '5N' ORDER BY a.grade,a.room,a.code");
						if (!$get) {
							errorMessage(3, "There's an error generating preview");
							slog("PBL", "load", "reward", "", "fail", "", "InvalidQuery");
						} else if (!$get -> num_rows) {
							errorMessage(1, "There are currently no projects to rank");
							slog("PBL", "load", "reward", "", "fail", "", "Empty");
						} else {
							function reward_code2text($code) {
								switch ($code) {
									case "1G": $text = "ทอง"; break;
									case "2S": $text = "เงิน"; break;
									case "3B": $text = "ทองแดง"; break;
									case "4M": $text = "ชมเชย"; break;
									case "5N": $text = "เข้าร่วม"; break;
									case "0P": $text = "ผ่านเกณฑ์"; break;
									default: $text = "-"; break;
								} return $text;
							} $changes = array(); $grade = 0;
							while ($read = $get -> fetch_assoc()) {
								if ($read["grade"] <> $grade) $grade = intval($read["grade"]);
								if (!array_key_exists($grade, $changes)) $changes[$grade] = array();
								array_push($changes[$grade], array(
									"code" => $read["code"],
									"room" => intval($read["room"]),
									"type" => pblcode2text($read["type"])["th"],
									"avgS" => intval($read["score"]),
									"from" => reward_code2text($read["reward"]),
									"newR" => reward_code2text($read["new_reward"])
								));
							} successState(array(
								"proceedToken" => createToken(),
								"ranks" => $changes
							)); slog("PBL", "load", "reward", "", "pass");
						}
					}
				} break;
				case "process": {
					if (!$isPBLmaster) {
						errorMessage(3, "You don't have permission to view this data");
						slog("PBL", "edit", "reward", "", "fail", "", "Unauthorized");
					} else if (!isset($attr["token"]) || empty($attr["token"])) {
						errorMessage(2, "Token is required");
						slog("PBL", "edit", "reward", "", "fail", "", "NotExisted");
					} else if (!checkToken($attr["token"])) {
						errorMessage(2, "Token mismatched. Please try again");
						slog("PBL", "edit", "reward", "", "fail", "", "InvalidToken");
					} else {
						$success = $db -> query("UPDATE PBL_group a SET a.lastupdate=a.lastupdate,a.reward=(CASE WHEN (SELECT ROUND(SUM(b.total)/COUNT(b.cmte)) FROM PBL_score b WHERE b.code=a.code GROUP BY b.code) BETWEEN 50 AND 59 THEN '4M' WHEN (SELECT ROUND(SUM(b.total)/COUNT(b.cmte)) FROM PBL_score b WHERE b.code=a.code GROUP BY b.code) BETWEEN 60 AND 69 THEN '3B' WHEN (SELECT ROUND(SUM(b.total)/COUNT(b.cmte)) FROM PBL_score b WHERE b.code=a.code GROUP BY b.code) BETWEEN 70 AND 79 THEN '2S' WHEN (SELECT ROUND(SUM(b.total)/COUNT(b.cmte)) FROM PBL_score b WHERE b.code=a.code GROUP BY b.code) BETWEEN 80 AND 100 THEN '1G' ELSE a.reward END),a.lastupdate=a.lastupdate WHERE a.year=$year AND a.reward IS NOT NULL AND a.reward != '5N'");
						if ($success) {
							successState();
							slog("PBL", "edit", "reward", "", "pass");
						} else {
							errorMessage(3, "Unable to update rewards.");
							slog("PBL", "edit", "reward", "", "fail", "", "InvalidQuery");
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