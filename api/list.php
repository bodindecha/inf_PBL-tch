<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);
	$APP_RootDir = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/"));
	$checkAP = false;
	require_once($dirPWroot."resource/php/extend/_RGI.php");
	// Permission checks
	function has_perm($what, $mods = true) {
		if (!(isset($_SESSION["auth"]) && $_SESSION["auth"]["type"]=="t")) return false;
		$mods = ($mods && $_SESSION["auth"]["level"]>=75); $perm = (in_array("*", $_SESSION["auth"]["perm"]) || in_array($what, $_SESSION["auth"]["perm"]));
		return ($perm || $mods);
	}
	// Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"]; $isPBLmaster = has_perm("PBL");
	require_once($APP_RootDir."private/script/lib/TianTcl/various.php");
	define("PBL_ENC_KEY", "PBL-m4^/@9312");
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
	switch ($type) {
		case "group": {
			switch ($command) {
				case null: {
					$loadLimit = 20;
					foreach (array_keys($attr) as $param) if (!in_array($param, ["mbr_comp"])) $attr[$param] = escapeSQL($attr[$param]);
					// Query conductor
					$conditions = array("mbr1 IS NOT NULL");
					if (count($attr) > 1) {
						if (isset($attr["grade"])) array_push($conditions, "grade=$attr[grade]");
						if (isset($attr["room"])) array_push($conditions, "room=$attr[room]");
						if (isset($attr["type"])) array_push($conditions, $attr["type"] <> "-" ? "type='$attr[type]'" : "LENGTH(type)=0");
						if (isset($attr["isAdvisor"]) && $attr["isAdvisor"]<>"A") array_push($conditions, "'$self' ".($attr["isAdvisor"]=="N"?"NOT ":"")."IN(adv1, adv2, adv3)");
						if (count($attr) > 2) {
							if (isset($attr["search_key"]) && isset($attr["search_type"]) && isset($attr["search_diff"])) {
								switch ($attr["search_diff"]) {
									case "S": $attr["search_key"] = "'$attr[search_key]%'"; break;
									case "E": $attr["search_key"] = "'%$attr[search_key]'"; break;
									case "C": $attr["search_key"] = "'%$attr[search_key]%'"; break;
									default: $attr["search_key"] = "'$attr[search_key]'"; break;
								} $attr["search_key"] = "LIKE $attr[search_key]";
								if ($attr["search_type"] <> "member") {
									if ($attr["search_type"] == "name")
										array_push($conditions, "(nameth $attr[search_key] OR nameen $attr[search_key])");
									else array_push($conditions, "$attr[search_type] $attr[search_key]");
								} else array_push($conditions, "(mbr".implode(" $attr[search_key] OR mbr", str_split("1234567"))." $attr[search_key])");
							} if (isset($attr["wf_file"]) && isset($attr["wf_count"]) && isset($attr["wf_status"])) {
								if ($attr["wf_count"] == "all")
									array_push($conditions, "fileStatus&$attr[wf_file]=".($attr["wf_status"]=="sent"?$attr["wf_file"]:"0"));
								else array_push($conditions, ($attr["wf_status"]=="sent"?"":"!")."fileStatus&$attr[wf_file]");
							} if (isset($attr["mbr_comp"]) && isset($attr["mbr_amt"]))
								array_push($conditions, "(IF(mbr".implode(" IS NULL, 0, 1) + IF(mbr", str_split("1234567"))." IS NULL, 0, 1))$attr[mbr_comp]$attr[mbr_amt]");
						}
					} // Ordering
					$orderLim = "";
					if (!isset($attr["sort"])) {
						$attr["sort"] = "class"; $attr["order"] = "ASC";
					} else if ($attr["sort"] == "time") {
						$attr["sort"] = "lastupdate";
						$attr["order"] = ($attr["order"]=="ASC" ? "DESC" : "ASC");
					} else if ($attr["sort"] == "name") $attr["sort"] = "CONCAT(nameth, nameen, code)";
					if ($attr["sort"] == "class") $orderLim .= " ORDER BY ".($attr["order"]=="ASC" ? "grade,room" : "grade DESC, room DESC");
					else $orderLim .= " ORDER BY $attr[sort] $attr[order]";
					// Limiter
					$orderLim .= " LIMIT $attr[loadNext], $loadLimit";
					$searchQuery = "SELECT code,nameth,nameen FROM PBL_group WHERE year=$year AND ".implode(" AND ", $conditions).$orderLim;
					$get = $db -> query($searchQuery);
					if ($get) {
						$result = array();
						if ($get -> num_rows) while ($read = $get -> fetch_assoc()) {
							$projTitle = $read["nameth"];
							if (!strlen($projTitle)) $projTitle = $read["nameen"];
							if (!strlen($projTitle)) $projTitle = $read["code"];
							array_push($result, array(
								"code" => $read["code"],
								"title" => $projTitle
							));
						} successState(array(
							"projects" => $result,
							"nextLoad" => ($get -> num_rows <> $loadLimit ? null : intval($attr["loadNext"]) + $loadLimit)
							# , "_dev" => array("debug" => array("SQL_query" => $searchQuery, "cond" => $conditions))
						));
					} else {
						errorMessage(3, "Unable to list projects.");
						# errorMessage(1, $searchQuery);
					}
				} break;
				case "overdue": {
					$get = $db -> query("SELECT a.exor,b.namep,b.namefth,b.namelth,b.namenth,c.code,c.grade,c.room,c.type,c.nameth,c.nameen,SUBSTRING(MAX(a.time), 1, 19) AS subTime FROM log_action a INNER JOIN user_s b ON a.exor=b.stdid INNER JOIN PBL_group c ON SUBSTRING(a.data, 1, 6)=c.code JOIN config_sys d ON d.name='t_year' INNER JOIN config_sep e ON e.year=d.value AND e.name='PBL-dd_F' WHERE a.app='PBL' AND a.cmd='new' AND a.act='file' AND a.data RLIKE '[A-Z0-9]{6}: report-all' AND a.val='pass' AND CAST(a.time AS DATE) > e.value GROUP BY c.code ORDER BY c.type,c.grade,c.room,a.time");
					if (!$get) {
						errorMessage(3, "Unable to list projects where reports are submitted late.");
						slog("PBL", "list", "overdue", "", "fail", "", "InvalidQuery");
					} else {
						$list = array();
						if ($get -> num_rows) while ($read = $get -> fetch_assoc()) array_push($list, array(
							"group" => array(
								"code" => $read["code"],
								"grade" => intval($read["grade"]),
								"room" => intval($read["room"]),
								"branch" => $read["type"],
								"name" => strlen($read["nameth"]) ? $read["nameth"] : $read["nameen"],
							),
							"sender" => array(
								"ID" => intval($read["exor"]),
								"name" => prefixcode2text($read["namep"])["th"].$read["namefth"]."  ".$read["namelth"],
								"nickname" => $read["namenth"]
							),
							"time" => date("วันที่ d/m/Y เวลา H:i น.", strtotime($read["subTime"]))
						)); successState(array("submissions" => $list));
					}
				} break;
				case "assignees": {
					$result = array();
					function appendData($get_groups, $readperm) {
						global $result, $TCL;
						if ($get_groups -> num_rows) {
							$category = array(); $grade = "0"; $grades = array();
							while ($readgroup = $get_groups -> fetch_assoc()) {
								if ($readgroup["grade"]<>$grade) {
									if (count($grades)) {
										$category[$grade] = $grades;
										$grades = array();
									} $grade = $readgroup["grade"];
								} $markers = array_values(array_filter(array($readgroup["mrker1"], $readgroup["mrker2"], $readgroup["mrker3"], $readgroup["mrker4"], $readgroup["mrker5"])));
								for ($marker = 0; $marker < count($markers); $marker++) $markers[$marker] = $TCL -> encrypt("PBL-".$markers[$marker]."cmte", PBL_ENC_KEY, 2);
								array_push($grades, array(
									"code" => $readgroup["code"],
									"name" => $readgroup["name"],
									"cmte" => $readgroup["grader"] == null ? null : $TCL -> encrypt("PBL-".$readgroup["grader"]."cmte", PBL_ENC_KEY, 2),
									"asgn" => $markers,
									"step" => intval($readgroup["round"]),
								));
							} if (count($grades)) $category[$grade] = $grades;
							if (count($category)) $result[$readperm] = $category;
						}
					}
					if ($isPBLmaster) foreach (str_split("ABCDEFGHIJKLM") as $readperm) {
						$get_groups = $db -> query("SELECT code,grade,(CASE nameth WHEN '' THEN nameen ELSE nameth END) AS name,grader,mrker1,mrker2,mrker3,mrker4,mrker5,(CASE WHEN (NOT reward='5N' AND reward IS NOT NULL) THEN 2 ELSE 1 END) AS round FROM PBL_group WHERE mbr1 IS NOT NULL AND type='$readperm' AND year=$year GROUP BY code ORDER BY grade,code");
						appendData($get_groups, $readperm);
					} else {
						$get_perm = $db -> query("SELECT cmteid,type FROM PBL_cmte WHERE tchr='$self' AND year=$year AND allow='Y' AND isHead='Y'");
						if ($get_perm -> num_rows) while ($readperm = $get_perm -> fetch_assoc()) {
							$get_groups = $db -> query("SELECT a.code,a.grade,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.grader,a.mrker1,a.mrker2,a.mrker3,a.mrker4,a.mrker5,(CASE WHEN (NOT a.reward='5N' AND a.reward IS NOT NULL) THEN 2 ELSE 1 END) AS round FROM PBL_group a INNER JOIN PBL_cmte b ON b.cmteid=$readperm[cmteid] WHERE a.mbr1 IS NOT NULL AND a.type='$readperm[type]' AND a.year=$year ORDER BY a.grade,a.code");
							appendData($get_groups, $readperm["type"]);
						} else if ($get_perm) errorMessage(1, "You are not assigned as a head of any project type");
						else errorMessage(3, "Unable to read permission");
					} if (count($result)) successState($result);
					else errorMessage(1, "ไม่มีโครงงานที่ผ่านการประเมินขั้นที่ 1 ในสาขาที่ท่านได้รับมอบหมาย");
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		case "work": {
			switch ($command) {
				case "permission": {
					$code = escapeSQL($attr);
					$grade = $_SESSION["auth"]["info"]["grade"] ?? "0";
					$room = $_SESSION["auth"]["info"]["room"] ?? "0";
					/***
					 * แผนผังความคิด:		ครูที่สอนห้องนั้นทุกท่าน
					 * ใบงาน IS1-1 ถึง 3:	-
					 * เล่มรายงานบทที่ 1-5:   -
					 * เล่มรายงานฉบับเต็ม:	  ครูที่ได้รับมอบหมายให้ตรวจโครงงานสาขานั้น
					 * บทคัดย่อ:			ครูที่ได้รับมอบหมายให้ตรวจโครงงานสาขานั้น
					 * โปสเตอร์:			ครูทุกท่านในระบบโรงเรียน
					 * (ครูประจำชั้นและที่ปรึกษาโครงงานสามารถดูได้ทุกไฟล์)
					 ***/
					$allow = array_fill(0, 11, 0);
					/***
					 * null = Hide
					 * 0	= Unclickable
					 * 1	= View
					 * 2	= Grade
					 ***/
					$get = $db -> query("SELECT grade,room,type,adv1,adv2,adv3 FROM PBL_group WHERE code='$code'");
					if (!$get || $get -> num_rows <> 1) errorMessage(3, "Unable to get permission");
					else {
						$read = $get -> fetch_array(MYSQLI_ASSOC);
						$is_PBLmaster = has_perm("PBL");
						// Homeroom teacher
						if ($grade==$read["grade"] && $room==$read["room"]) $allow = array_fill(0, 11, 1);
						// Project advisor
						else if (in_array($self, array_values($read)) || $is_PBLmaster) $allow = array_fill(0, 11, 1);
						// Mindmap: Subject teachers
						$getTch = $db -> query("SELECT NULL FROM subj_teacher WHERE year=$year AND sem=1 AND tchr='$self'");
						if ($getTch -> num_rows == 1) $allow[0] = 1;
						// Full-report & Abstract: Committee
						# $getCat = $db -> query("SELECT GROUP_CONCAT(type) types FROM PBL_cmte WHERE allow='Y' AND tchr='$self' AND year=$year GROUP BY tchr");
						$getCat = $db -> query("SELECT NULL FROM PBL_cmte WHERE allow='Y' AND tchr='$self' AND year=$year AND type='".$read["type"]."'");
						if ($is_PBLmaster || $getCat -> num_rows == 1) {
							$allow[9] = 2;
							$allow[10] = 1;
						} // All teacher in school
						$allow[11] = 1;
						// Special access
						if ($is_PBLmaster) {
							$allow[10] = 2;
							$allow[11] = 2;
						} // Check if IS
						if (!in_array($read["grade"], array("2", "4"))) {
							$allow[1] = null;
							$allow[2] = null;
							$allow[3] = null;
						} successState($allow);
					}
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		default: errorMessage(1, "Invalid type"); break;
	} $db -> close();
	sendOutput($return);
?>