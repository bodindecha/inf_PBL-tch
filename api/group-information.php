<?php
    $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
    require_once($dirPWroot."resource/php/extend/_RGI.php");
    // Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"];
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
    switch ($type) {
        case "group": {
			switch ($command) {
				case "title": {
					$code = escapeSQL($attr["code"]);
					$get = $db -> query("SELECT a.nameth,a.nameen,a.type,a.adv1,a.adv2,a.adv3,a.score_poster+a.score_present+(CASE WHEN ROUND(SUM(b.total)/COUNT(b.cmte))<50 THEN 2 ELSE 3 END) AS score FROM PBL_group a LEFT JOIN PBL_score b ON b.code=a.code WHERE a.code='$code' GROUP BY b.code");
					if (!$get) {
						errorMessage(3, "Error loading your data. Please try again.");
						slog("PBL", "load", "info", $code, "fail", "", "InvalidQuery");
					} else if (!$get -> num_rows) {
						errorMessage(3, "There's an error: Your group information is unavailable. Please try reloading.");
						slog("PBL", "load", "info", $code, "fail", "", "NotExisted");
					} else {
						$read = $get -> fetch_array(MYSQLI_ASSOC);
						successState($read);
					}
				} break;
				case "member": {
					$code = escapeSQL($attr["code"]);
					$settings = array("statusOpen", "publishWork", "maxMember");
					$get = $db -> query("SELECT mbr1,mbr2,mbr3,mbr4,mbr5,mbr6,mbr7,".implode(",", $settings)." FROM PBL_group WHERE code='$code'");
					if (!$get) {
						errorMessage(3, "Error loading your data. Please try again.");
						slog("PBL", "load", "member", $code, "fail", "", "InvalidQuery");
					} else if (!$get -> num_rows) {
						errorMessage(3, "There's an error: Your group information is unavailable. Please try reloading.");
						errorMessage(1, "Code: $code");
						slog("PBL", "load", "member", $code, "fail", "", "NotExisted");
					} else {
						$read = $get -> fetch_array(MYSQLI_ASSOC);
						$data = array("settings" => array());
						foreach (array_reverse($settings) as $es) {
							$data["settings"][$es] = $read[$es];
							array_pop($read);
						} $data["list"] = array_values(array_filter($read));
						successState($data);
					}
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		case "settings": {
			switch ($command) {
				case "member": {
					$code = escapeSQL($attr[2]);
					$setName = escapeSQL($attr[0]); $newValue = escapeSQL($attr[1]);
					$settings = array("statusOpen", "publishWork", "maxMember");
					if (!in_array($setName, $settings)) {
						errorMessage(3, "The settings you are trying to update doesn't exist.");
						slog("PBL", "edit", "setting", "$code: $setName -> $newValue", "fail", "", "Unavailable");
					} else {
						if (array_search($setName, $settings) < 2) $newValue = "'$newValue'";
						$success = $db -> query("UPDATE PBL_group SET $setName=$newValue WHERE code='$code'");
						if ($success) {
							successState(array("message" => array(
								array(0, "Setting changes saved.")
							))); slog("PBL", "edit", "setting", "$code: $setName -> $newValue", "pass");
						} else {
							errorMessage(3, "Unable to save your setting ($setName).");
							# errorMessage(3, "UPDATE PBL_group SET $setName=$newValue WHERE code='$code'");
							slog("PBL", "edit", "setting", "$code: $setName -> $newValue", "fail", "", "InvalidQuery");
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