<?php
	$APP_RootDir = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/"));
	require($APP_RootDir."private/script/start/PHP.php");
	$header["title"] = "คณะกรรมการ";
	$header["desc"] = "คณะกรรมการตรวจโครงงาน";

	require_once($APP_RootDir."private/script/lib/TianTcl/various.php");
	if (!has_perm("PBL")) $TCL -> http_response_code(901);

	require($APP_RootDir."public_html/resource/php/core/config.php");
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($APP_RootDir."private/block/core/heading.php"); require($APP_RootDir."private/script/start/CSS-JS.php"); ?>
		<style type="text/css">
			app[name=main] .cmte-list td:not(:nth-child(2)) { text-align: center; }
			app[name=main] .candidate button {
				text-wrap: nowrap; white-space-collapse: preserve;
				overflow: hidden;
			}
		</style>
		<script type="text/javascript">
			// const TRANSLATION = "";
			$(document).ready(function() {
				page.init();
			});
			const page = (function(d) {
				const cv = {
					API_URL: AppConfig.baseURL + "t/PBL/v2/api/committee",
					jobName: "คณะกรรมการตรวจโครงงาน"
				};
				var sv = {inited: false, record: [], candidate: []};
				var initialize = function() {
					if (sv.inited) return;
					loadList();
					$("app[name=main] input[name=filter]").on("input change", filter);
					$("app[name=main] .add-cmte button.blue").on("click", selectUser);
					$("app[name=main] .add-cmte button.purple").on("click", assignUser);
					$("app[name=main] .add-cmte select").on("change", addButtonState);
					addButtonState();
					sv.inited = true;
				};
				var loadList = function() {
					app.Util.ajax(cv.API_URL, {act: "list", cmd: "control"}).then(function(dat) {
						if (!dat || !dat.ifo.length) return app.UI.notify(1, "There are currently no comittee to display.");
						var holder = 1, buffer= "";
						dat.ifo.forEach(es => {
							buffer += '<tr><td>' + (holder++).toString() + '</td>' +
								'<td>' + es.name + '</td>' +
								'<td>' + es.branch + '</td>' +
								'<td><div class="css-flex center"><input type="checkbox" name="s:' + es.impact + '" class="switch" onChange="page.updateStatus(this)" ' + (es.active ? "checked" : "") + ' /></div></td>' +
								'<td><div class="css-flex center"><input type="checkbox" name="h:' + es.impact + '" class="switch" onChange="page.updateStatus(this)" ' + (es.chief ? "checked" : "") + ' /></div></td>' +
							'</tr>';
							sv.record[es.impact] = {allow: es.active, isHead: es.chief};
						}); $("app[name=main] .cmte-list").html(buffer);
					});
				},
				updateStatus = function(me) {
					var target = me.name.substring(2),
						field = me.name[0] == "h" ? "isHead" : "allow";
						state = $(me).is(":checked");
					if (sv.record[target][field] == state) return;
					var name = me.parentNode.parentNode.parentNode.children[1].innerText;
					app.Util.ajax(cv.API_URL, {act: "mod", cmd: "setStatus", param: {target, field, state}}).then(function(dat) {
						if (dat) {
							sv.record[target] = state;
							app.UI.notify(0, "Status of " + name + " updated.", 10);
						} else {
							me.checked = !me.checked;
							app.UI.notify(3, "Unable to update status of " + name + ".", 15);
						}
					});
				},
				filter = function() {
					var query = $("app[name=main] input[name=filter]").val();
					w3.filterHTML("app[name=main] .cmte-list", "tr", query);
				},
				selectUser = function() {
					fs.teacher("เลือกบัญชีผู้ใช้งาน", appendUser, sv.candidate);
				},
				appendUser = function(ID, display) {
					if (typeof ID === "undefined") return;
					var nameTag = $('<button name="c:' + ID + '" class="black small hollow pill" onClick="page.removeCandidate(this)" style="display: none;">' + display + '</button>')
					$("app[name=main] .candidate").append(nameTag);
					setTimeout(() => nameTag.toggle("clip"), 250);
					sv.candidate.push(ID);
					addButtonState();
				},
				removeCandidate = function(me) {
					var reference = me.name.substring(2);
					sv.candidate.splice(sv.candidate.indexOf(reference), 1);
					me = $(me);
					me.css("width", me.outerWidth() - 2)
						.animate({width: 0, padding: 0, borderWidth: 0}, 5e2, "linear", function() {
							setTimeout(() => me.remove(), 1e2);
						});
					addButtonState();
				},
				addButtonState = function() {
					d.querySelector("app[name=main] .add-cmte button.purple").disabled = !sv.candidate.length;
					d.querySelector("app[name=main] .add-cmte button.blue").disabled = sv.candidate.length >= 50;
				},
				assignUser = function() {
					if (!confirm("Are you sure you want to entitle user(s) listed below to be " + cv.jobName + " ?")) return;
					$("app[name=main] .add-cmte").attr("disabled", "");
					app.Util.ajax(cv.API_URL, {act: "mod", cmd: "assign", param: {
						candidate: btoa(sv.candidate.join(", ")),
						type: $("app[name=main] .add-cmte select option:checked").val()
					}}).then(function(dat) {
						$("app[name=main] .add-cmte").removeAttr("disabled");
						if (!dat) return;
						setTimeout(() => {
							$("app[name=main] .candidate button").fadeOut(1e3);
							setTimeout(() => $("app[name=main] .candidate button").remove(), 1100);
						}, 250);
						sv.candidate = [];
						addButtonState();
						loadList();
						app.UI.notify(0, "Selected account(s) has been entitled as " + cv.jobName);
					});
				};
				return {
					init: initialize,
					updateStatus,
					removeCandidate
				}
			}(document));
		</script>
		<!-- <script type="text/javascript" src="<?=$APP_CONST["baseURL"]?>_resx/plugin/TianTcl/find-search/data.js"></script> -->
		<script type="text/javascript" src="<?=$APP_CONST["baseURL"]?>resource/js/extend/find-search.js"></script>
		<script type="text/javascript" src="<?=$APP_CONST["cdnURL"]?>static/script/lib/w3.min.js"></script>
	</head>
	<body>
		<app name="main">
			<?php require($APP_RootDir."private/block/core/top-panel/structure.php"); ?>
			<main>
				<section class="container">
					<h2><?=$header["title"]?></h2>
					<details class="card message cyan">
						<summary>เพิ่มคณะกรรมการ</summary>
						<div class="add-cmte form form-bs">
							<div class="group split">
								<button class="blue ripple-click">เพิ่ม</button>
								<div class="css-flex css-flex-gap-10">
									<div class="group">
										<label>โครงงานสาขา</label>
										<select name="type" required>
											<option value disabled selected>— กรุณาเลือก —</option>
											<?php foreach (str_split("ABCDEFGHIJKLM") as $et) echo '<option value="' . $et . '">' . pblcode2text($et)["th"] . '</option>'; ?>
										</select>
									</div>
									<button class="purple ripple-click">แต่งตั้ง</button>
								</div>
							</div>
							<fieldset><legend>รายชื่อบัญชี (click to remove)</legend>
								<div class="candidate css-flex css-flex-inline css-flex-gap-10 css-flex-wrap"></div>
							</fieldset>
						</div>
					</details>
					<div class="form form-bs inline"><div class="group">
						<input type="search" name="filter" placeholder="Filter..." />
					</div></div>
					<div class="table responsive"><table>
						<thead><tr>
							<th>No.</th><th>Name</th><th>Branch</th><th>Enabled</th><th>Head</th>
						</tr></thead><tbody class="cmte-list">
							<tr><td colspan="5"><center class="css-flex"><div class="loading"></div></center></td></tr>
						</tbody>
					</table></div>
				</section>
			</main>
			<?php require($APP_RootDir."private/block/core/material/main.php"); ?>
		</app>
	</body>
</html>