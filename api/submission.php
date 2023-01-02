<?php
    $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
    require_once($dirPWroot."resource/php/extend/_RGI.php");
    // Permission checks
    function has_perm($what, $mods = true) {
        if (!(isset($_SESSION['auth']) && $_SESSION['auth']['type']=="t")) return false;
        $mods = ($mods && $_SESSION['auth']['level']>=75); $perm = (in_array("*", $_SESSION['auth']['perm']) || in_array($what, $_SESSION['auth']['perm']));
        return ($perm || $mods);
    }
    // Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"];
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
    switch ($type) {
        case "load": {
			switch ($command) {
				case "file": {
                    $code = escapeSQL($attr["code"]);
                    $filePos = intval(escapeSQL($attr["file"]));
                    $fileCfg = array(
						"mindmap"       => "Mindmap",
						"IS1-1"         => "ใบงาน IS1-1",
						"IS1-2"         => "ใบงาน IS1-2",
						"IS1-3"         => "ใบงาน IS1-3",
						"report-1"      => "รายงานโครงงานบทที่ 1",
						"report-2"      => "รายงานโครงงานบทที่ 2",
						"report-3"      => "รายงานโครงงานบทที่ 3",
						"report-4"      => "รายงานโครงงานบทที่ 4",
						"report-5"      => "รายงานโครงงานบทที่ 5",
						"report-all"    => "รายงานฉบับสมบูรณ์",
						"abstract"      => "Abstract",
						"poster"        => "Poster"
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
						} else errorMessage(3, "File has not been submitted");
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
						$success = $db -> query("UPDATE PBL_group SET score_$file=$score WHERE code='$code'");
						if ($success) {
							successState();
							slog("PBL", "edit", "score", "$code: $file -> $score", "pass");
						} else {
							errorMessage(3, "Unable to save score.");
							slog("PBL", "edit", "score", "$code: $file -> $score", "fail", "", "InvalidQuery");
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