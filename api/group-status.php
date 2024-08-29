<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);
	require_once($dirPWroot."resource/php/extend/_RGI.php");
	// Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"];
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
	switch ($type) {
		case "get": {
			switch ($command) {
				case "personal": {
					$get = $db -> query("SELECT code,grade,room FROM PBL_group WHERE code='$attr' AND mbr1 IS NOT NULL");
					if (!$get) errorMessage(3, "Error loading your data.");
					else {
						$data = array("isGrouped" => boolval($get -> num_rows));
						if ($data["isGrouped"]) {
							$read = $get -> fetch_array(MYSQLI_ASSOC);
							$data["grade"] = intval($read["grade"]);
							$data["room"] = intval($read["room"]);

							$data["code"] = $read["code"];
							$data["requireIS"] = ($data["grade"] == 2 || $data["grade"] == 4);
						} successState($data);
					}
				} break;
				case "fileLink": {
					$fileCfg = array(
						"mindmap"		=> "Mindmap",
						"IS1-1"			=> "ใบงาน IS1-1",
						"IS1-2"			=> "ใบงาน IS1-2",
						"IS1-3"			=> "ใบงาน IS1-3",
						"report-1"		=> "รายงานโครงงานบทที่ 1",
						"report-2"		=> "รายงานโครงงานบทที่ 2",
						"report-3"		=> "รายงานโครงงานบทที่ 3",
						"report-4"		=> "รายงานโครงงานบทที่ 4",
						"report-5"		=> "รายงานโครงงานบทที่ 5",
						"report-all"	=> "รายงานฉบับสมบูรณ์",
						"abstract"		=> "Abstract",
						"poster"		=> "Poster"
					); $fileExts = array("png", "jpg", "jpeg", "heic", "heif", "gif", "pdf");
					// Fetch data
					$code = escapeSQL($attr["code"]);
					$file = escapeSQL($attr["type"]);
					$filePos = array_search($file, array_keys($fileCfg));
					$get = $db -> query("SELECT year,grade,fileStatus,fileType FROM PBL_group WHERE code='$code'");
					if (!$get) errorMessage(3, "Error loading your data. Please try again.");
					else if (!$get -> num_rows)
						successState(array("isGrouped" => false));
					else {
						$read = $get -> fetch_array(MYSQLI_ASSOC);
						if (intval($read["fileStatus"])&pow(2, $filePos)) {
							$extension = explode(";", $read["fileType"])[$filePos];
							$year = $read["year"]; $grade = $read["grade"];
							$path = "upload/PBL/$year/$file/$grade/$code.$extension";
							$finder = $dirPWroot."resource/$path";
							if (file_exists($finder)) {
								$URLattr = "?furl=".urlencode($path)."&name=PBL-$code%20$fileCfg[$attr]";
								successState(array(
									"preview" => "/_resx/service/view/file$URLattr",
									"download" => "/resource/file/download$URLattr",
									"print" => base64_encode("/resource/$path")
								));
							} else errorMessage(3, "File not found");
						} else errorMessage(3, "File has not been submitted");
					}
				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		case "work": {
			switch ($command) {
				case "file": {
					$fileCfg = array("mindmap", "IS1-1", "IS1-2", "IS1-3", "report-1", "report-2", "report-3", "report-4", "report-5", "report-all", "abstract", "poster");
					$code = escapeSQL($attr["code"]);
					$getcode = $db -> query("SELECT CONCAT(nameth,nameen) AS name,type,CONCAT(COALESCE(adv1,''),COALESCE(adv2,''),COALESCE(adv3,'')) AS adv,fileStatus FROM PBL_group WHERE code='$code'");
					if (!$getcode) errorMessage(3, "Error loading your data. Please try again.");
					else if (!$getcode -> num_rows) {
						successState(array("isGrouped" => false));
						slog("PBL", "edit", "info", $code, "fail", "", "NotExisted");
					} else {
						$readcode = $getcode -> fetch_array(MYSQLI_ASSOC);
						$status = intval($readcode["fileStatus"]);
						if (!empty($attr["file"])) {
							$pos = array_search($attr["file"], $fileCfg);
							successState(array(
								"fileSent" => boolval($status&pow(2, $pos))
							));
						} else {
							$status = strrev(substr(base_convert(pow(2, count($fileCfg))|$status, 10, 2), 1));
							function bit2bool($fileStatus) {
								return boolval($fileStatus);
							} $status = array_combine($fileCfg, array_map("bit2bool", str_split($status)));
							$status["n1"] = boolval(strlen($readcode["name"]));
							$status["n2"] = boolval(strlen($readcode["adv"]));
							$status["n3"] = boolval(strlen($readcode["type"]));
							successState($status);
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