<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);
	require_once($dirPWroot."resource/php/extend/_RGI.php");
	// Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"];
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
	switch ($type) {
		case "create": {
			if (empty($attr["mbr1"])) {
				errorMessage(3, "Unable to create group. Please add group leader and try again.");
				slog("PBL", "new", "group", "", "fail", "", "Empty");
			} else {
				// Check if isGrouped
				$getdup = $db -> query("SELECT code FROM PBL_group WHERE ".$attr["mbr1"]." IN(mbr1,mbr2,mbr3,mbr4,mbr5,mbr6,mbr7) AND year=$year");
				if (!$getdup) errorMessage(3, "Error loading your data. Please try again.");
				else {
					if ($getdup -> num_rows) {
						errorMessage(3, "The group leader you selected is already in a group.");
						slog("PBL", "new", "group", "", "fail", "", "Existed");
					} else {
						$getstd = $db -> query("SELECT gen,room FROM user_s WHERE stdid=".$attr["mbr1"]);
						if (!$getstd) errorMessage(3, "Error loading student's data. Please try again.");
						else {
							if (!$getstd -> num_rows) {
								errorMessage(3, "The group leader you selected doesn't exists.");
								slog("PBL", "new", "group", "", "fail", "", "Unavailable");
							} else {
								$readstd = $getstd -> fetch_array(MYSQLI_ASSOC);
								$grade = gen2grade($readstd["grade"]); $room = $readstd["room"];
								$cond = "grade=$grade AND room=$room";
								if (intval($grade) > 6 || intval($room) > 19) {
									errorMessage(3, "The group leader you selected is not eligible for group creation. Please reselect and try again.");
									slog("PBL", "new", "group", "", "fail", "", "NotEligible");
								} else { // Create group
									// Read arguments
									$nameth = escapeSQL(htmlspecialchars(preg_replace("/เเ/", "แ", $attr["nameth"])));
									$nameen = escapeSQL(htmlspecialchars(preg_replace("/เเ/", "แ", $attr["nameen"])));
									$mbr1 = escapeSQL($attr["mbr1"]);
									$adv1 = (empty($attr["adv1"]) ? "NULL" : "'".escapeSQL($attr["adv1"])."'");
									$adv2 = (empty($attr["adv2"]) ? "NULL" : "'".escapeSQL($attr["adv2"])."'");
									$adv3 = (empty($attr["adv3"]) ? "NULL" : "'".escapeSQL($attr["adv3"])."'");
									$type = escapeSQL($attr["type"]);
									// Plagiarsm check
									$getPlag = $db -> query("SELECT code,year,nameth,nameen FROM PBL_group WHERE nameth='$nameth' OR nameen='$nameen' ORDER BY year ASC LIMIT 1");
									if (false && $getPlag -> num_rows) {
										$readPlag = $getPlag -> fetch_array(MYSQLI_ASSOC);
										errorMessage(1, "โครงงาน \"$readPlag[nameth]\" ($readPlag[nameen]) ได้มีการทำขึ้นแล้วในปีการศึกษา $readPlag[year] (รหัสโครงงาน $readPlag[code]). กรุณาเลือกชื่อโครงงานอื่น");
										slog("PBL", "new", "group", $code, "fail", "", "Duplicate");
									} else {
										// Check empty group
										$null_grp = $db -> query("SELECT code FROM PBL_group WHERE mbr1 IS NULL AND $cond");
										if ($null_grp && $null_grp -> num_rows) {
											$code = ($null_grp -> fetch_array(MYSQLI_ASSOC))["code"];
											$success = $db -> query("UPDATE PBL_group SET mbr1=$mbr1,nameth='$nameth',nameen='$nameen',type='$type',adv1=$adv1,adv2=$adv2,adv3=$adv3 WHERE code='$code'");
										} else {
											// Generate group code
											$gengno = $db -> query("SELECT COUNT(code) as cnt FROM PBL_group WHERE $cond"); $gengid = ($gengno -> fetch_array(MYSQLI_ASSOC))["cnt"];
											$gengde = $year.$grade.(strlen($room)-1?"":"0").$room.(strlen($gengid)-1?"":"0").$gengid;
											$code = strrev(str_rot13(strtoupper(base_convert($gengde, 10, 36))));
											$success = $db -> query("INSERT INTO PBL_group (code,year,grade,room,nameth,nameen,type,mbr1,adv1,adv2,adv3) VALUES('$code',$year,$grade,$room,'$nameth','$nameen','$type',$self,$adv1,$adv2,$adv3)");
										} if ($success) {
											successState(array("isGrouped" => true, "requireIS" => ($grade == 2 || $grade == 4), "code" => $code, "message" => array(
												array(0, "Group created successfully.")
											))); slog("PBL", "new", "group", $code, "pass");
										} else {
											errorMessage(3, "Unable to create group. Please try again.");
											slog("PBL", "new", "group", $code, "fail", "", "InvalidQuery");
										}
			} } } } } } }
		} break;
		case "update": {
			switch ($command) {
				case "information": {
					$code = escapeSQL($attr["code"]);
					// Read arguments
					$nameth = escapeSQL(htmlspecialchars(preg_replace("/เเ/", "แ", $attr["nameth"])));
					$nameen = escapeSQL(htmlspecialchars(preg_replace("/เเ/", "แ", $attr["nameen"])));
					$adv1 = (empty($attr["adv1"]) ? "NULL" : "'".escapeSQL($attr["adv1"])."'");
					$adv2 = (empty($attr["adv2"]) ? "NULL" : "'".escapeSQL($attr["adv2"])."'");
					$adv3 = (empty($attr["adv3"]) ? "NULL" : "'".escapeSQL($attr["adv3"])."'");
					$type = escapeSQL($attr["type"]);
					// Plagiarsm check
					$getPlag = $db -> query("SELECT code,year,nameth,nameen FROM PBL_group WHERE nameth='$nameth' OR nameen='$nameen' ORDER BY year ASC LIMIT 1");
					if (false && $getPlag -> num_rows) {
						$readPlag = $getPlag -> fetch_array(MYSQLI_ASSOC);
						errorMessage(1, "โครงงาน \"$readPlag[nameth]\" ($readPlag[nameen]) ได้มีการทำขึ้นแล้วในปีการศึกษา $readPlag[year] (รหัสโครงงาน $readPlag[code]). กรุณาเลือกชื่อโครงงานอื่น");
						slog("PBL", "edit", "info", $code, "fail", "", "Duplicate");
					} else { // Update information
						$success = $db -> query("UPDATE PBL_group SET nameth='$nameth',nameen='$nameen',type='$type',adv1=$adv1,adv2=$adv2,adv3=$adv3 WHERE code='$code'");
						if ($success) {
							successState(array("message" => array(
								array(0, "New group information is saved."),
							))); slog("PBL", "edit", "info", $code, "pass");
						} else {
							errorMessage(3, "Unable update group information. Please try again.");
							slog("PBL", "edit", "info", $code, "fail", "", "InvalidQuery");
						}
					}
				} break;
				case "leader": {
					$code = escapeSQL($attr["code"]);
					$candidate = escapeSQL($attr["mbr"]);
					$get = $db -> query("SELECT mbr1 FROM PBL_group WHERE code='$code'");
					if (!$get) errorMessage(3, "Error loading your data. Please try again.");
					else if (!$get -> num_rows) {
						successState(array("isGrouped" => false));
						slog("PBL", "edit", "leader", "$code: leader -> $candidate", "fail", "", "NotExisted");
					} else {
						if ($candidate == ($get -> fetch_array(MYSQLI_ASSOC))["mbr1"]) {
							errorMessage(3, "You are already a group leader.");
							slog("PBL", "edit", "leader", "$code: leader -> $candidate", "fail", "", "Existed");
						} else {
							$findseat = $db -> query("SELECT mbr2,mbr3,mbr4,mbr5,mbr6,mbr7 FROM PBL_group WHERE code='$code' AND $candidate IN(mbr2,mbr3,mbr4,mbr5,mbr6,mbr7)");
							if (!$findseat) {
								errorMessage(3, "Can't set someone outside of your group as a leader.");
								slog("PBL", "edit", "leader", "$code: leader -> $candidate", "fail", "", "Unavailable");
							} else if (!$findseat -> num_rows) {
								errorMessage(3, "Can't set someone outside of your group as a leader.");
								slog("PBL", "edit", "leader", "$code: leader -> $candidate", "fail", "", "NotEligible");
							} else { // Check swapper's seat
								$seats = $findseat -> fetch_array(MYSQLI_ASSOC);
								$swap = array_search($candidate, $seats);
								$success = $db -> query("UPDATE PBL_group SET mbr1=(@temp:=mbr1),mbr1=$swap,$swap=@temp WHERE code='$code'");
								if ($success) {
									successState();
									slog("PBL", "edit", "leader", "$code: leader -> $candidate", "pass");
								} else {
									errorMessage(3, "Unable to set $candidate as the new group leader. Please try again");
									slog("PBL", "edit", "leader", "$code: leader -> $candidate", "fail", "", "InvalidQuery");
								}
							}
						}
					}
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		case "delete": {
			switch ($command) {
				case "member": {
					$code = escapeSQL($attr["code"]);
					$mbr = escapeSQL($attr["mbr"]);
					$success = $db -> query("UPDATE PBL_group SET mbr2=(CASE mbr2 WHEN $mbr THEN NULL ELSE mbr2 END),mbr3=(CASE mbr3 WHEN $mbr THEN NULL ELSE mbr3 END),mbr4=(CASE mbr4 WHEN $mbr THEN NULL ELSE mbr4 END),mbr5=(CASE mbr5 WHEN $mbr THEN NULL ELSE mbr5 END),mbr6=(CASE mbr6 WHEN $mbr THEN NULL ELSE mbr6 END),mbr7=(CASE mbr7 WHEN $mbr THEN NULL ELSE mbr7 END) WHERE code='$code'");
					if ($success) {
						successState(array("message" => array(
							array(0, "Member removed successfully"),
						))); if ($mbr <> $self) slog("PBL", "del", "member", "$code -> $mbr", "pass");
						else slog("PBL", "exit", "group", $code, "pass");
					}
					else {
						errorMessage(3, "Unable to remove $mbr from your group. Please try again.");
						if ($mbr <> $self) slog("PBL", "del", "member", "$code -> $mbr", "fail", "", "InvalidQuery");
						else slog("PBL", "exit", "group", $code, "fail", "", "InvalidQuery");
					}
				} break;
				case "void": {
					$fileCfg = array("mindmap", "IS1-1", "IS1-2", "IS1-3", "report-1", "report-2", "report-3", "report-4", "report-5", "report-all", "abstract", "poster");
					$code = escapeSQL($attr["code"]);
					$get = $db -> query("SELECT year,grade,fileStatus,fileType FROM PBL_group WHERE code='$code'");
					if (!$get) errorMessage(3, "Error loading your data. Please try again.");
					else if (!$get -> num_rows) {
						successState(array("isGrouped" => false));
						slog("PBL", "del", "group", $code, "fail", "", "NotExisted");
					} else {
						$read = $get -> fetch_array(MYSQLI_ASSOC);
						$success = $db -> multi_query("UPDATE PBL_group SET nameth='',nameen='',type='',mbr".implode("=NULL,mbr", str_split("1234567"))."=NULL,maxMember=6,statusOpen='Y',publishWork='Y',adv".implode("=NULL,adv", str_split("123"))."=NULL,fileStatus=0,fileType=';;;;;;;;;;;',grader=NULL,mrker".implode("=NULL,mrker", str_split("12345"))."=NULL,score_poster=NULL WHERE code='$code'; DELETE FROM PBL_score WHERE code='$code'");
						if ($success) {
							if (intval($read["fileStatus"])) { // Delete uploaded file(s)
								$status = $read["fileStatus"];
								$status = strrev(substr(base_convert(pow(2, count($fileCfg))|$status, 10, 2), 1));
								$fileType = explode(";", $group["fileType"]);
								$year = $read["year"]; $grade = $read["grade"];
								function bit2bool($fileStatus) {
									return boolval($fileStatus);
								} $status = array_combine($fileCfg, array_map("bit2bool", str_split($status)));
								/* foreach ($fileCfg as $file) {
									if (boolval($status[$file])) {
										$location = $dirPWroot."resource/upload/PBL/$year/$file/$grade/$code.".$fileType[array_search($file, $fileCfg)];
										if (file_exists($location)) unlink($location);
									}
								} */
								for ($fIdx = 0; $fIdx <= count($fileCfg); $fIdx++) {
									if ($status[$fIdx]) {
										$location = $dirPWroot."resource/upload/PBL/$year/".$fileCfg[$fIdx]."/$grade/$code.".$fileType[$fIdx];
										if (file_exists($location)) unlink($location);
									}
								}
							} successState(array("isGrouped" => false, "message" => array(
								array(0, "Your group is now deleted.")
							))); slog("PBL", "del", "group", $code, "pass");
						} else {
							errorMessage(3, "Unable to delete your group. Please try again.");
							slog("PBL", "del", "group", $code, "fail", "", "InvalidQuery");
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