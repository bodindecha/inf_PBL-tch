<?php
    $dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);
	require($dirPWroot."resource/hpe/init_ps.php");
	$header_title = "คณะกรรมการ";
	$header_desc = "คณะกรรมการตรวจโครงงาน";
	$home_menu = "is-pbl";

	// Check permission
	$nperm = !has_perm("PBL");
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($dirPWroot."resource/hpe/heading.php"); require($dirPWroot."resource/hpe/init_ss.php"); ?>
		<style type="text/css">
			
		</style>
		<script type="text/javascript">
			/* $(document).ready(function() {
				$('div.table table tbody tr td:nth-child(4) center input[name^="status_"]').on("change", function(e) { update(e.target); });
			});
			function update(me) {
				var val = $(me).is(":checked") ? "Y" : "N", cmteid = $(me).attr("name").substring(7);
				$.post("/resource/php/core/override", {app: "PBL", cmd: "cmte-status", attr: cmteid, val: val}, function(res, hsc) {
					var dat = JSON.parse(res);
					app.ui.notify(1, dat.reason);
					if (!dat.success) me.checked = val=="N";
				});
			} */
		</script>
	</head>
	<body>
		<?php require($dirPWroot."resource/hpe/header.php"); ?>
		<main shrink="<?php echo($_COOKIE['sui_open-nt'])??"false"; ?>">
			<div class="container">
				<h2><?=$header_desc?></h2>
				
			</div>
		</main>
		<?php require($dirPWroot."resource/hpe/material.php"); ?>
		<footer>
			<?php require($dirPWroot."resource/hpe/footer.php"); ?>
		</footer>
	</body>
</html>