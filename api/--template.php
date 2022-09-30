<?php
    $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
    require_once($dirPWroot."resource/php/extend/_RGI.php");
    // Execute
	$self = $_SESSION["auth"]["user"]; $year = $_SESSION["stif"]["t_year"];
	if (empty($self)) errorMessage(3, "You are not signed-in. Please reload and try again."); else
    switch ($type) {
        case "": {
			switch ($command) {
				case "": {

				} break;
				default: errorMessage(1, "Invalid command"); break;
			}
		} break;
		default: errorMessage(1, "Invalid type"); break;
	} $db -> close();
	sendOutput($return);
?>