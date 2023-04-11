<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);
	require_once($dirPWroot."resource/php/extend/_RGI.php");
	// Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"];
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
	switch ($type) {
		case "group": {
			$code = escapeSQL($command);
			// No COALESCE on score -> only show when all is graded
			$get = $db -> query("SELECT a.grade,a.room,a.lastupdate,a.type,a.mbr1,a.mbr2,a.mbr3,a.mbr4,a.mbr5,a.mbr6,a.mbr7,a.adv1,a.adv2,a.adv3,a.score_poster+c.score+(CASE WHEN ROUND(SUM(b.total)/COUNT(b.cmte))<50 THEN 2 ELSE 3 END) AS score FROM PBL_group a LEFT JOIN PBL_score b ON b.code=a.code LEFT JOIN user_score c ON c.stdid=a.mbr1 AND c.year=a.year AND c.subj='PBL' AND c.field='oph-act' WHERE a.code='$code' GROUP BY b.code");
			if (!$get) {
				errorMessage(3, "Error loading group's information. Please try again.");
				slog("PBL", "load", "manifest", $code, "fail", "", "InvalidQuery");
			} else if (!$get -> num_rows) {
				errorMessage(3, "There's an error: Group's information is unavailable. Please try again later.");
				slog("PBL", "load", "manifest", $code, "fail", "", "NotExisted");
			} else {
				$read = $get -> fetch_array(MYSQLI_ASSOC);
				successState(array(
					"class" => "ม.".$read["grade"]."/".$read["room"],
					"update" => date("วันที่ d/m/Y เวลา H:iน.", strtotime($read["lastupdate"])),
					"type" => pblcode2text($read["type"])[$_COOKIE["set_lang"]],
					"member" => array_values(array_filter(array($read["mbr1"], $read["mbr2"], $read["mbr3"], $read["mbr4"], $read["mbr5"], $read["mbr6"], $read["mbr7"]))),
					"advisor" => array_filter(array($read["adv1"], $read["adv2"], $read["adv3"])),
					"score" => $read["score"]
				));
			}
		} break;
		case "person": {
			switch ($command) {
				case "student": {
					$query = escapeSQL($attr);
					if (!preg_match("/^\d{5}(,\d{5})*$/", $query)) {
						errorMessage(3, "Error: Invalid student's referer.");
						slog("PBL", "load", "info", $query, "fail", "", "InvalidValue");
					} else {
						$first = explode(",", $query)[0];
						$get = $db -> query("SELECT stdid,number,namep,namefth,namelth,namenth FROM user_s WHERE stdid IN($query) ORDER BY (CASE stdid WHEN $first THEN 1 ELSE 2 END),number");
						if (!$get) {
							errorMessage(3, "Error loading your data. Please try again.");
							slog("PBL", "load", "info", $query, "fail", "", "InvalidQuery");
						} else if (!$get -> num_rows) {
							errorMessage(3, "There's an error: Your group information is unavailable. Please try reloading.");
							slog("PBL", "load", "info", $query, "fail", "", "NotExisted");
						} else {
							$data = array("list" => array());
							while ($read = $get -> fetch_assoc()) array_push($data["list"], array(
								"ID" => $read["stdid"],
								"fullname" => prefixcode2text($read["namep"])["th"].$read["namefth"]."  ".$read["namelth"],
								"nickname" => $read["namenth"],
								"number" => $read["number"]
							)); successState($data);
						}
					}
				} break;
				case "teacher": {
					$query = escapeSQL($attr);
					if (!preg_match("/^([a-z]{3,28}\.[a-z]{1,2}|[a-zA-Z]{3,30}\d{0,3})(,([a-z]{3,28}\.[a-z]{1,2}|[a-zA-Z]{3,30}\d{0,3}))*$/", $query)) {
						errorMessage(3, "Error: Invalid teacher's referer.");
						slog("PBL", "load", "info", $query, "fail", "", "InvalidValue");
					} else {
						$search = "'".implode("','", explode(",", $query))."'";
						$get = $db -> query("SELECT namecode,namefth,namelth FROM user_t WHERE namecode IN($search)");
						if (!$get) {
							errorMessage(3, "Error loading your data. Please try again.");
							slog("PBL", "load", "info", $query, "fail", "", "InvalidQuery");
						} else if (!$get -> num_rows) {
							errorMessage(3, "There's an error: Your group information is unavailable. Please try reloading.");
							slog("PBL", "load", "info", $query, "fail", "", "NotExisted");
						} else {
							$data = array("list" => array());
							while ($read = $get -> fetch_assoc())
								$data["list"][$read["namecode"]] = "ครู".$read["namefth"]."  ".$read["namelth"];
							successState($data);
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