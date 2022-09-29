<?php
    $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
	$checkAP = false;
    require_once($dirPWroot."resource/php/extend/_RGI.php");
    // Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"];
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
    switch ($type) {
        case null: {
			switch ($command) {
				case null: {
					$loadLimit = 20;
					foreach (array_keys($attr) as $param) if ($param <> "mbr_comp") $attr[$param] = escapeSQL($attr[$param]);
					// Query conductor
					$query = "SELECT code,nameth,nameen FROM PBL_group WHERE year=$year AND mbr1 IS NOT NULL";
					if (count($attr) > 3 || (count($attr) > 1 && (isset($attr["grade"]) || isset($attr["room"])))) {
						if (isset($attr["grade"])) $query .= " AND grade=".$attr["grade"];
						if (isset($attr["room"])) $query .= " AND room=".$attr["room"];
						if (isset($attr["type"])) $query .= " AND type='".$attr["type"]."'";
						if (isset($attr["isAdvisor"]) && $attr["isAdvisor"]<>"A") $query .= " AND '$self' ".($attr["isAdvisor"]=="N"?"NOT ":"")."IN(adv1, adv2, adv3)";
						if (isset($attr["search_key"]) && isset($attr["search_type"]) && isset($attr["search_diff"])) {
							switch ($attr["search_diff"]) {
								case "S": $attr["search_key"] = "'%".$attr["search_key"]."'"; break;
								case "E": $attr["search_key"] = "'".$attr["search_key"]."%'"; break;
								case "C": $attr["search_key"] = "'%".$attr["search_key"]."%'"; break;
								default: $attr["search_key"] = "'".$attr["search_key"]."'"; break;
							} $attr["search_key"] = "LIKE ".$attr["search_key"];
							if ($attr["search_type"] <> "member") {
								if ($attr["search_type"] == "name")
									$query .= " AND (nameth ".$attr["search_key"]." OR nameen ".$attr["search_key"].")";
								else $query .= " AND ".$attr["search_type"]." ".$attr["search_key"];
							} else $query .= " AND (mbr".implode(" ".$attr["search_key"]." OR mbr", str_split("1234567"))." ".$attr["search_key"].")";
						} if (isset($attr["wf_file"]) && isset($attr["wf_count"]) && isset($attr["wf_status"])) {
							if ($attr["wf_count"] == "all")
								$query .= " AND fileStatus&".$attr["wf_file"]."=".($attr["wf_status"]=="sent"?$attr["wf_status"]:"0");
							else $query .= " AND ".($attr["wf_status"]=="sent"?"":"!")."fileStatus&".$attr["wf_file"];
						} if (isset($attr["mbr_comp"]) && isset($attr["mbr_amt"]))
							$query .= " AND (IF(mbr".implode(" IS NULL, 0, 1) + IF(mbr", str_split("1234567"))." IS NULL, 0, 1))".$attr["mbr_comp"].$attr["mbr_amt"];
					}
					// Ordering
					if (!isset($attr["sort"])) {
						$attr["sort"] = "class"; $attr["order"] = "ASC";
					} else if ($attr["sort"] == "time") {
						$attr["sort"] = "lastupdate";
						$attr["order"] = ($attr["order"]=="ASC" ? "DESC" : "ASC");
					} else if ($attr["sort"] == "name") $attr["sort"] = "CONCAT(nameth, nameen, code)";
					if ($attr["sort"] == "class") $query .= " ORDER BY ".($attr["order"]=="ASC" ? "grade,room" : "grade DESC, room DESC");
					else $query .= " ORDER BY ".$attr["sort"]." ".$attr["order"];
					// Limiter
					$query .= " LIMIT ".$attr["loadNext"].", $loadLimit";
					$get = $db -> query($query);
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
							# , "_dev" => array("debug" => array("SQL_query" => $query))
						));
					} else {
						errorMessage(3, "Unable to list projects.");
						# errorMessage(1, $query);
					}
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		default: errorMessage(1, "Invalid type"); break;
	} $db -> close();
	sendOutput($return);
?>