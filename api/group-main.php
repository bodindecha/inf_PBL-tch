<?php
    $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
    require_once($dirPWroot."resource/php/extend/_RGI.php");
    // Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"];
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
    switch ($type) {
        case "work": {
			switch ($command) {
				case "remove": {
					$file = $attr["type"];
					$fileCfg = array("mindmap", "IS1-1", "IS1-2", "IS1-3", "report-1", "report-2", "report-3", "report-4", "report-5", "report-all", "abstract", "poster");
					$filePos = array_search($file, $fileCfg);
					$code = escapeSQL($attr["code"]);
					$get = $db -> query("SELECT year,grade,fileStatus,fileType FROM PBL_group WHERE code='$code'");
					if (!$get) errorMessage(3, "Error loading your data. Please try again.");
					else if (!$get -> num_rows) {
						successState(array("isGrouped" => false));
						slog("PBL", "del", "file", "$code: $file", "fail", "", "NotExisted");
					} else {
						$read = $get -> fetch_array(MYSQLI_ASSOC);
						// If memory exists
						if (intval($read["fileStatus"])&pow(2, $filePos)) {
							$extension = explode(";", $read["fileType"])[$filePos];
							$year = $read["year"]; $grade = $read["grade"];
							$path = "resource/upload/PBL/$year/$file/$grade/$code.$extension";
							$finder = $dirPWroot.$path;
							function removeSuccess() {
								global $read, $filePos, $db, $code, $file;
								$fileTypes = explode(";", $read["fileType"]);
								$fileTypes[$filePos] = ""; $fileTypes = implode(";", $fileTypes);
								$success = $db -> query("UPDATE PBL_group SET fileStatus=~(~fileStatus|".pow(2, $filePos)."),fileType='$fileTypes' WHERE code='$code'");
								if ($success) {
									successState();
									slog("PBL", "del", "file", "$code: $file", "pass");
								} else {
									errorMessage(3, "Unable to remove file.");
									slog("PBL", "del", "file", "$code: $file", "fail", "", "InvalidQuery");
								}
							} // Find and delete file
							if (file_exists($finder)) {
								if (unlink($finder)) removeSuccess();
								else {
									errorMessage(3, "Unable to delete file.");
									slog("PBL", "del", "file", "$code: $file", "fail", "", "Incorrect");
								}
							} else {
								# errorMessage(3, "No file to remove.");
								# slog("PBL", "del", "file", "$code: $file", "fail", "", "Empty");
								removeSuccess();
							}
						} else {
							errorMessage(3, "File not found.");
							slog("PBL", "del", "file", "$code: $file", "fail", "", "Unavailable");
						}
					}
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		case "member": {
			switch ($command) {
				case "invite": {
					// Check if isGrouped
					$code = escapeSQL($attr["code"]);
					$freshy = escapeSQL($attr["mbr"]);
					$get = $db -> query("SELECT code FROM PBL_group WHERE $freshy IN(mbr1,mbr2,mbr3,mbr4,mbr5,mbr6,mbr7) AND year=$year");
					if (!$get) errorMessage(3, "Error checking your data. Please try again.");
					else {
						if ($get -> num_rows) {
							errorMessage(3, "Student $freshy is already in a group.");
							slog("PBL", "join", "group", "$code <- $freshy", "fail", "", "Existed");
						} else { // Join group
							// Check if code exists
							$getinfo = $db -> query("SELECT grade,room,mbr1,maxMember,statusOpen FROM PBL_group WHERE year=$year AND code='$code'");
							if (!$getinfo) {
								errorMessage(3, "Unable to check availability.");
								slog("PBL", "join", "group", "$code <- $freshy", "fail", "", "InvalidQuery");
							} else if (!$getinfo -> num_rows) {
								errorMessage(3, "This group doesn't exist.");
								slog("PBL", "join", "group", "$code <- $freshy", "fail", "", "NotExisted");
							} else { // Check criteria
								$criteria = $getinfo -> fetch_array(MYSQLI_ASSOC);
								/* if ($criteria["grade"] <> $grade || $criteria["room"] <> $room) {
									errorMessage(3, "You can't add a member to a group outside of their class.");
									slog("PBL", "join", "group", "$code <- $freshy", "fail", "", "NotEligible");
								} else */ if (empty($criteria["mbr1"])) { # || $criteria["statusOpen"]<>"Y") {
									errorMessage(3, "This group is unavailable.");
									slog("PBL", "join", "group", "$code <- $freshy", "fail", "", "Empty");
								} else {
									$findseat = $db -> query("SELECT mbr2,mbr3,mbr4,mbr5,mbr6,mbr7 FROM PBL_group WHERE code='$code' AND (mbr".implode(" IS NULL OR mbr", str_split("234567"))." IS NULL)");
									if (!$findseat) {
										errorMessage(3, "Unable to look for a seat. Please try again.");
										slog("PBL", "join", "group", "$code <- $freshy", "fail", "", "InvalidQuery");
									} else if ($findseat -> num_rows) { // Check seat
										$seats = $findseat -> fetch_array(MYSQLI_ASSOC);
										$myseat = array_search("", $seats);
										if (count(array_filter($seats)) >= intval($criteria["maxMember"])) {
											errorMessage(3, "Unable to join the group.");
											errorMessage(1, "The group you are trying to join is full.");
											slog("PBL", "join", "group", "$code <- $freshy", "fail", "", "NotEmpty");
										} else {
											$success = $db -> query("UPDATE PBL_group SET $myseat=$freshy WHERE code='$code'");
											if ($success) {
												successState();
												slog("PBL", "join", "group", "$code <- $freshy", "pass");
											} else {
												errorMessage(3, "Unable to join the group. Please try again.");
												slog("PBL", "join", "group", "$code <- $freshy", "fail", "", "InvalidQuery");
											}
										}
									} else {
										errorMessage(3, "The group you are trying to join is already full.");
										slog("PBL", "join", "group", "$code <- $freshy", "fail", "", "NotEmpty");
					} } } } }
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		default: errorMessage(1, "Invalid type"); break;
	} $db -> close();
	sendOutput($return);
?>