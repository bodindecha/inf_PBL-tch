<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
	require_once($dirPWroot."resource/php/extend/_RGI.php");
	require_once($dirPWroot."resource/php/core/config.php");
	// Permission checks
	function has_perm($what, $mods = true) {
		if (!(isset($_SESSION['auth']) && $_SESSION['auth']['type']=="t")) return false;
		$mods = ($mods && $_SESSION['auth']['level']>=75); $perm = (in_array("*", $_SESSION['auth']['perm']) || in_array($what, $_SESSION['auth']['perm']));
		return ($perm || $mods);
	}
	// Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"]; $isPBLmaster = has_perm("PBL");
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
	switch ($type) {
		case "list": {
			switch ($command) {
				case "paper-grade": {
					$get_perm = $db -> query("SELECT type FROM PBL_cmte WHERE allow='Y' AND tchr='$self' AND year=$year");
					if ($get_perm -> num_rows >= 1) {
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
									} array_push($grades, array(
										"code" => $readgroup["code"],
										"name" => $readgroup["name"],
										"rank" => $readgroup["reward"],
										"sent" => boolval($readgroup["sent"])
									));
								} if (count($grades)) $category[$grade] = $grades;
								if (count($category)) $result[pblcode2text($readperm)["th"]] = $category;
							}
						}
						if ($isPBLmaster) foreach (str_split("ABCDEFGHIJKLM") as $readperm) {
							$get_groups = $db -> query("SELECT a.code,a.grade,COALESCE(a.nameth, a.nameen) AS name,a.fileStatus&512 AS sent,a.reward FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' WHERE a.mbr1 IS NOT NULL AND a.type='$readperm' AND a.year=$year GROUP BY a.code ORDER BY a.grade,a.code");
							appendData($get_groups, $readperm);
						} else while ($readperm = $get_perm -> fetch_assoc()) {
							$get_groups = $db -> query("SELECT a.code,a.grade,COALESCE(a.nameth, a.nameen) AS name,a.fileStatus&512 AS sent,a.reward FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' AND b.allow='Y' AND b.type='".$readperm["type"]."' WHERE a.mbr1 IS NOT NULL AND a.type='".$readperm["type"]."' AND a.year=$year AND (a.grader IS NULL OR a.grader=b.cmteid) ORDER BY a.grade,a.code");
							appendData($get_groups, $readperm["type"]);
						} if (count($result)) successState($result);
						else errorMessage(1, "ไม่มีโครงงานให้ตรวจในสาขาที่ท่านได้รับมอบหมาย");
					} else if ($get_perm) errorMessage(1, "You are not assigned to mark any of the projects");
					else errorMessage(3, "Unable to read permission");
				} break;
				case "paper-mark": {
					$get_perm = $db -> query("SELECT type FROM PBL_cmte WHERE allow='Y' AND tchr='$self' AND year=$year");
					if ($get_perm -> num_rows >= 1) {
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
									} array_push($grades, array(
										"code" => $readgroup["code"],
										"name" => $readgroup["name"],
										"mark" => boolval($readgroup["mark"]),
										"sent" => boolval($readgroup["sent"])
									));
								} if (count($grades)) $category[$grade] = $grades;
								if (count($category)) $result[pblcode2text($readperm["type"])["th"]] = $category;
							}
						}
						if ($isPBLmaster) foreach (str_split("ABCDEFGHIJKLM") as $readperm) {
							$get_groups = $db -> query("SELECT a.code,a.grade,COALESCE(a.nameth, a.nameen) AS name,a.fileStatus&512 AS sent,c.total AS mark FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' LEFT JOIN PBL_score c ON a.code=c.code AND c.cmte=b.cmteid WHERE a.mbr1 IS NOT NULL AND a.type='$readperm' AND a.year=$year AND NOT a.reward='5N' AND a.reward IS NOT NULL GROUP BY a.code ORDER BY a.grade,a.code");
							appendData($get_groups, $readperm);
						} else while ($readperm = $get_perm -> fetch_assoc()) {
							$get_groups = $db -> query("SELECT a.code,a.grade,COALESCE(a.nameth, a.nameen) AS name,a.fileStatus&512 AS sent,c.total AS mark FROM PBL_group a INNER JOIN PBL_cmte b ON b.year=$year AND b.tchr='$self' AND b.allow='Y' AND b.type=a.type LEFT JOIN PBL_score c ON a.code=c.code AND c.cmte=b.cmteid WHERE a.mbr1 IS NOT NULL AND a.type='".$readperm["type"]."' AND a.year=$year AND NOT a.reward='5N' AND a.reward IS NOT NULL AND (b.cmteid IN(mrker1, mrker2, mrker3, mrker4, mrker5) OR CONCAT(COALESCE(mrker1, ''), COALESCE(mrker2, ''), COALESCE(mrker3, ''), COALESCE(mrker4, ''), COALESCE(mrker5, ''))='') ORDER BY a.grade,a.code");
							appendData($get_groups, $readperm["type"]);
						} if (count($result)) successState($result);
						else errorMessage(1, "ไม่มีโครงงานให้ตรวจในสาขาที่ท่านได้รับมอบหมาย");
					} else if ($get_perm) errorMessage(1, "You are not assigned to mark any of the projects");
					else errorMessage(3, "Unable to read permission");
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		default: errorMessage(1, "Invalid type"); break;
	} $db -> close();
	sendOutput($return);
?>