<?php
    session_start();
    // Recieve
    $type = $_REQUEST["type"] ?? null; $command = $_REQUEST["act"] ?? null; $attr = $_REQUEST["param"] ?? null;
    // Review
    $return = array("success" => false, "reason" => array(array(3, "Attributes empty")));
    if (empty($type) || empty($command)) die(json_encode($return));
    else $return["reason"] = array();
    // Connect
    if (!isset($dirPWroot)) $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
    require_once($dirPWroot."resource/php/core/config.php"); require($dirPWroot."resource/php/core/db_connect.php");
    require($dirPWroot."resource/php/core/getip.php"); # require_once($dirPWroot."resource/php/extend/getPermission.php");
    function escapeSQL($input) {
        global $db;
        return $db -> real_escape_string($input);
    }
    function successState($output = null) {
        global $return;
        $return["success"] = true; unset($return["reason"]);
        if (!empty($output)) $return["info"] = $output;
    }
    function errorMessage($type, $text = null) {
        global $return;
        array_push($return["reason"], (empty($text) ? $type : array($type, $text)));
    }
    function sendOutput($info, $readable = false) {
        if (!$readable) echo json_encode($info);
        else echo json_encode($info, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }
?>