<?php
    if (!isset($dirPWroot)) $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
    if ($_SERVER["REDIRECT_URL"]=="/t/PBL/v2/api/export-data") {
        header("Location: /error/914");
        exit(0);
    }

    require_once($dirPWroot."resource/php/core/config.php");
    require($dirPWroot."resource/php/core/db_connect.php");
    $reqType = "csv";
    $dltime = date("Y-m-d H_i_s", time());
    switch ($ds) {
        case "branches": {
            $name = "สาขาโครงงาน";
            $result = $db -> query("SELECT grade,room,type,COUNT(code) AS amount,GROUP_CONCAT(COALESCE(nameth, COALESCE(nameen, code))) AS names FROM PBL_group WHERE year=$year AND mbr1 IS NOT NULL GROUP BY grade,room,type ORDER BY grade,room,(CASE type WHEN '' THEN 1 ELSE 0 END),type");
            $has_result = ($result && $result -> num_rows);
            $delimeter = ($reqType == "tsv" ? "\t" : ",");
            $outputData = "\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"สาขาโครงงาน\"$delimeter\"จำนวนโครงงาน\"";
            if ($has_result) { while ($er = $result -> fetch_assoc()) {
                // Modify
                if (empty($er["type"])) $brance = "ยังไม่มีสาขา";
                else $branch = pblcode2text($er["type"])["th"];
                // Concat
                $outputData .= "\n\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$branch."\"$delimeter\"".$er["amount"]."\"";
            } }
        } break;
        case "title-list": {
            $name = "รายชื่อโครงงาน";
            $result = $db -> query("SELECT grade,room,code,COALESCE(nameth, COALESCE(nameen, 'ไม่มีชื่อโครงงาน')) AS name,type FROM PBL_group WHERE year=$year AND mbr1 IS NOT NULL ORDER BY grade,room,name");
            $has_result = ($result && $result -> num_rows);
            $delimeter = ($reqType == "tsv" ? "\t" : ",");
            $outputData = "\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"รหัสโครงงาน\"$delimeter\"ชื่อโครงงาน\"$delimeter\"สาขาโครงงาน\"";
            if ($has_result) { while ($er = $result -> fetch_assoc()) {
                // Modify
                if ($er["name"]=="") $er["name"] = "~ไม่มีชื่อโครงงาน~";
                // Concat
                $outputData .= "\n\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$er["code"]."\"$delimeter\"".$er["name"]."\"$delimeter\"".pblcode2text($er["type"])["th"]."\"";
            } }
        } break;
    }
    $name = "PBL $name $dltime.$reqType";
    switch ($reqType) {
        case "csv": $mime = "text/csv"; break;
        case "tsv": $mime = "text/tsv"; break;
        case "json": $mime = "application/json"; break;
    } if ($reqType == "json") $outputData = json_encode($outputData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    // --- Start Force Download ---
    if (ob_get_contents()) {
        die("Some data has already been output, can't export data file");
    }
    header("Content-Description: File Transfer");
    if (headers_sent()) {
        die("Some data has already been output to browser, can't export data file");
    }
    header("Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1");
    # header("Cache-Control: public, must-revalidate, max-age=0"); // HTTP/1.1
    header("Pragma: public");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
    header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
    // force download dialog
    if (strpos(php_sapi_name(), "cgi") === false) {
        # header("Content-Type: $mime", true);
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream", false);
        header("Content-Type: application/download", false);
        header("Content-Type: $mime", false);
        header("Content-Length: ".strlen(strval($outputData)));
    } else header("Content-Type: $mime");
    // use the Content-Disposition header to supply a recommended filename
    header("Content-Disposition: attachment; filename=\"".basename($name)."\"");
    header("Content-Transfer-Encoding: binary");
    # TCPDF_STATIC::sendOutputData($this->getBuffer(), $this->bufferlen);
    echo strval($outputData);
    // --- End Force Download ---
    slog("PBL", "download", "report", "$ds.$reqType", "pass");
    exit(0);
?>