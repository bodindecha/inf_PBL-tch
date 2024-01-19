<?php
	$APP_RootDir = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/"));
	require($APP_RootDir."private/script/start/PHP.php");
	$header["title"] = "Set rank for awarded projects";
	$header["desc"] = "กำหนดระดับรางวัลตามคะแนนโครงงาน";

	require_once($APP_RootDir."private/script/lib/TianTcl/various.php");
	if (!has_perm("PBL")) $TCL -> http_response_code(901);
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($APP_RootDir."private/block/core/heading.php"); require($APP_RootDir."private/script/start/CSS-JS.php"); ?>
		<style type="text/css">
			
		</style>
		<script type="text/javascript">
			const TRANSLATION = location.pathname.substring(1).replace(/\/$/, "").replaceAll("/", "+");
			$(document).ready(function() {
				page.init();
			});
			const page = (function(d) {
				const cv = { API_URL: AppConfig.APIbase + "PBL/v1-teacher/evaluation" };
				var sv = {inited: false};
				var initialize = function() {
					if (sv.inited) return;
					
					sv.inited = true;
				};
				var preview = function() {
					var btn = $("app[name=main] main .step-1").attr("disabled", "");
					app.Util.ajax(cv.API_URL, {type: "setRank", act: "getPreview"}).then(function(dat) {
						if (!dat) return btn.removeAttr("disabled");
						sv.token = dat.proceedToken;
						renderRanks(dat.ranks);
						$("app[name=main] main .step-2").fadeIn();
					});
				},
				renderRanks = function(data) {
					var holder = $("app[name=main] main .preview tbody").empty(), buffer;
					Object.keys(data).forEach(eg => {
						holder.append(`<tr><th colspan="6" center>${app.UI.language.getMessage("grade")} ${eg}</th></tr>`);
						data[eg].forEach(ep => {
							buffer = $('<tr></tr>');
							buffer.append(`<td center>${ep.room}</td>`);
							buffer.append(`<td class="code center select-all">${ep.code}</td>`);
							buffer.append(`<td center>${ep.type}</td>`);
							buffer.append(`<td center>${ep.avgS}</td>`);
							buffer.append(`<td center>${ep.from}</td>`);
							buffer.append(`<td class="css-bg-${(ep.from != ep.newR) ?
								(ep.from != "ผ่านเกณฑ์" ? "yellow" : "cyan") :
								(ep.from != "ผ่านเกณฑ์" ? "blue" : "red")
							} center">${ep.newR}</td>`);
							holder.append(buffer);
						})
					}); $("app[name=main] main .preview").fadeIn();
				},
				process = function() {
					if (typeof sv.token !== "string" || !sv.token.length) {
						$("app[name=main] main .step-1").removeAttr("disabled");
						$("app[name=main] main .step-2, app[name=main] main .preview").fadeOut();
						return app.UI.notify(2, app.UI.language.getMessage("no-token"));
					} if (!confirm(app.UI.language.getMessage("please-confirm"))) return;
					var btn = $("app[name=main] main .step-2").attr("disabled", "");
					app.Util.ajax(cv.API_URL, {type: "setRank", act: "process", param: { token: sv.token }}).then(function(dat) {
						if (!dat) return btn.removeAttr("disabled");
						delete sv.token;
						$("app[name=main] main .step-3").toggle("blind");
					});
				};
				return {
					init: initialize,
					preview,
					process
				}
			}(document));
		</script>
		<script type="text/javascript" src="<?=$APP_CONST["cdnURL"]?>static/script/lib/w3.min.js"></script>
	</head>
	<body>
		<app name="main">
			<?php require($APP_RootDir."private/block/core/top-panel/structure.php"); ?>
			<main>
				<section class="container">
					<h2><?=$header["title"]?></h2>
					<button class="step-1 blue ripple-click" onClick="page.preview()">Preview results</button>
					<div class="preview table list striped" style="display: none;"><table><thead>
						<tr>
							<th rowspan="2">ห้อง</th>
							<th colspan="2">โครงงาน</th>
							<th colspan="3">ผลประเมิน</th>
						</tr>
						<tr>
							<th>รหัส</th>
							<th>สาขา</th>
							<th>คะแนนเฉลี่ย</th>
							<th>เดิม</th>
							<th>ใหม่</th>
						</tr>
					</thead><tbody class="responsive"></tbody></table></div>
					<div class="step-2 css-flex center" style="display: none;">
						<button class="yellow large pill icon ripple-click" onClick="page.process()">
							<i class="material-icons">warning</i>
							<span class="text">Confirm process!</span>
						</button>
					</div>
					<center class="step-3 message green" style="display: none;">
						<b>Congratulations!</b>
						<span>Ranks have been set for every projects as previewed above.</span>
					</center>
				</section>
			</main>
			<?php require($APP_RootDir."private/block/core/material/main.php"); ?>
		</app>
	</body>
</html>