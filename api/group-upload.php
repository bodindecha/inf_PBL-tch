<?php
    session_start();
    $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
    require_once($dirPWroot."resource/php/core/reload_settings.php");
    require_once($dirPWroot."resource/php/core/config.php"); require($dirPWroot."resource/php/core/db_connect.php");
    require($dirPWroot."resource/php/core/getip.php");
    $return = array();
    function escapeSQL($input) {
        global $db;
        return $db -> real_escape_string($input);
    }
    function errorMessage($type, $text = null) {
        global $return;
        array_push($return, (empty($text) ? $type : array($type, $text)));
    } // Constants
    $fileCfg = array(
        "mindmap"       => 5,
        "IS1-1"         => 10,
        "IS1-2"         => 10,
        "IS1-3"         => 10,
        "report-1"      => 20,
        "report-2"      => 25,
        "report-3"      => 25,
        "report-4"      => 15,
        "report-5"      => 20,
        "report-all"    => 50,
        "abstract"      => 5,
        "poster"        => 30
    ); $fileExts = array("png", "jpg", "jpeg", "heic", "heif", "gif", "pdf");
    // Variables
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"];
    $file = escapeSQL($_REQUEST["filePart"]); $filePos = array_search($file, array_keys($fileCfg));
    // Execute
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else {
        if (!in_array($file, array_keys($fileCfg))) errorMessage(1, "Invalid upload category"); else {
            // Get group
            $code = escapeSQL($_REQUEST["code"]);
            $getcode = $db -> query("SELECT year,grade,fileStatus,fileType FROM PBL_group WHERE code='$code'");
            if (!$getcode) errorMessage(3, "Error loading your data. Please try again.");
            else if (!$getcode -> num_rows) {
                # successState(array("isGrouped" => false));
                errorMessage(3, "You are not in a group. Please reload the page");
                slog("PBL", "new", "file", "$code: $file", "fail", "", "NotExisted");
            } else {
                $readcode = $getcode -> fetch_array(MYSQLI_ASSOC);
                $year = $readcode["year"]; $grade = $readcode["grade"];
                // Set target directory
                $targetDir = $dirPWroot."resource/upload/PBL/$year";
                if (!is_dir($targetDir)) mkdir($targetDir, 0755);
                $targetDir .= "/$file"; if (!is_dir($targetDir)) mkdir($targetDir, 0755);
                $targetDir .= "/$grade"; if (!is_dir($targetDir)) mkdir($targetDir, 0755);
                // Process upload
                $fileType = strtolower(pathinfo(basename($_FILES["usf"]["name"]), PATHINFO_EXTENSION));
                $targetFile = "$targetDir/$code.$fileType";
                if (!($_FILES["usf"]["size"] > 0 && $_FILES["usf"]["size"] <= $fileCfg[$file]*1024000)) {
                    errorMessage(3, "File too large (larger than $fileCfg[$file] MB) or file is empty.");
                    slog("PBL", "new", "file", "$code: $file", "fail", "", "NotEligible");
                } else if (!in_array($fileType, $fileExts)) {
                    errorMessage(3, "Invalid file type.");
                    slog("PBL", "new", "file", "$code: $file", "fail", "", "NotEligible");
                } else {
                    // Remove previous
                    if (intval($readcode["fileStatus"]) & pow(2, $filePos)) {
                        $ldfle = "$targetDir/$code.".explode(";", $readcode["fileType"])[$filePos];
                        if (file_exists($ldfle)) unlink($ldfle);
                    } // Complete upload
                    if (move_uploaded_file($_FILES["usf"]["tmp_name"], $targetFile)) {
                        $extList = explode(";", $readcode["fileType"]);
                        $extList[$filePos] = $fileType; $extList = implode(";", $extList);
                        $success = $db -> query("UPDATE PBL_group SET fileStatus=fileStatus|".strval(pow(2, $filePos)).",fileType='$extList' WHERE code='$code'");
                        if ($success) {
                            errorMessage(0, "File uploaded successfully");
                            slog("PBL", "new", "file", "$code: $file", "pass");
                            if (!isset($_SESSION["var"])) $_SESSION["var"] = array();
                            $_SESSION["var"]["PBL-upload-status"] = true;
                        } else {
                            errorMessage(3, "Unable to save your file. Please try again.");
                            slog("PBL", "new", "file", "$code: $file", "fail", "", "InvalidQuery");
                        }
                    } else {
                        errorMessage(3, "Unable to upload file. Please try again");
                        slog("PBL", "new", "file", "$code: $file", "fail");
    } } } } } $db -> close();
	if (count($return)) {
        if (!isset($_SESSION["var"])) $_SESSION["var"] = array();
        $_SESSION["var"]["PBL-message-upload"] = $return;
    } header("Location: ../upload");
?>