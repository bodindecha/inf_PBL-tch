<?php
    $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
	require($dirPWroot."resource/hpe/init_ps.php");
	
	if (isset($_REQUEST["furl"])) {
        $path = $_REQUEST["furl"];
        if (preg_match("/^\/.+$/", $path)) $path = ltrim($path, "/");
        if (!preg_match("/^(css|file|fonts|images|js|json|upload)\/.+\.(png|jpg|jpeg|heic|heif|gif|pdf)(?.+)?/", $path)) $error = "403";
        $path = $dirPWroot."resource/$path";

		if (isset($_REQUEST["name"])) $name = trim($_REQUEST["name"]).".".pathinfo($path, PATHINFO_EXTENSION);
		else $name = basename($path);

        if (file_exists($path)) {
            $mime = mime_content_type(pathinfo($path, PATHINFO_EXTENSION));
            // --- Start Force Download ---
            if (ob_get_contents()) die("Some data has already been output, can't download file");
            header("Content-Description: File Transfer");
            if (headers_sent()) die("Some data has already been output to browser, can't download file");
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
                header("Content-Length: ".filesize($path));
            } else header("Content-Type: $mime");
            // use the Content-Disposition header to supply a recommended filename
            header("Content-Disposition: attachment; filename=\"$name\"");
            header("Content-Transfer-Encoding: binary");
            # echo file_get_contents($path);
            fpassthru(fopen($path, "rb")); # readfile($path);
            // --- End Force Download ---
            if (isset($error)) unset($error);
        } else $error = "905";
    } else $error = "902";
    
    if (isset($error)) {
        $header_title = "Error $error";
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($dirPWroot."resource/hpe/heading.php"); require($dirPWroot."resource/hpe/init_ss.php"); ?>
	</head>
	<body>
		<?php require($dirPWroot."resource/hpe/header.php"); ?>
		<main>
			<iframe src="/error/<?=$error?>">Error: <?=$error?></iframe>
		</main>
		<?php require($dirPWroot."resource/hpe/material.php"); ?>
		<footer>
			<?php require($dirPWroot."resource/hpe/footer.php"); ?>
		</footer>
	</body>
</html>
<?php } ?>