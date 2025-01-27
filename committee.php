<?php
	$APP_RootDir = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/"));
	require($APP_RootDir."private/script/start/PHP.php");
	$header["title"] = "คณะกรรมการ";
	$header["desc"] = "คณะกรรมการตรวจโครงงาน";

	require_once($APP_RootDir."private/script/lib/TianTcl/various.php");
	if (!has_perm("PBL")) TianTcl::http_response_code(901);

	require($APP_RootDir."public_html/resource/php/core/config.php");
	$APP_PAGE -> print -> head();
?>
<style type="text/css">
	app[name=main] .cmte-list td:not(:nth-child(2)) { text-align: center; }
	app[name=main] .candidate button {
		text-wrap: nowrap; white-space-collapse: preserve;
		overflow: hidden;
	}
</style>
<script type="text/javascript">
	const TRANSLATION = location.pathname.substring(1).replace(/\/$/, "").replaceAll("/", "+");
	$(document).ready(function() {
		page.init();
	});
	const page = (function(d) {
		const cv = {
			API_URL: AppConfig.APIbase + "PBL/v1-teacher/committee",
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
						'<td><div class="css-flex center"><input type="checkbox" name="s:' + es.impact + '" class="switch off-red" onChange="page.updateStatus(this)" ' + (es.active ? "checked" : "") + ' /></div></td>' +
						'<td><div class="css-flex center"><input type="checkbox" name="h:' + es.impact + '" class="switch on-purple" onChange="page.updateStatus(this)" ' + (es.chief ? "checked" : "") + ' /></div></td>' +
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
			var nameTag = $('<button name="c:' + ID + '" class="black small hollow pill css-overflow-hidden" onClick="page.removeCandidate(this)" style="display: none;">' + display + '</button>')
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
				.animate({width: 0, padding: 0, borderWidth: 0, marginLeft: -10}, 5e2, "linear", function() {
					setTimeout(() => me.remove(), 1e2);
				});
			addButtonState();
		},
		addButtonState = function() {
			d.querySelector("app[name=main] .add-cmte button.purple").disabled = !sv.candidate.length;
			d.querySelector("app[name=main] .add-cmte button.blue").disabled = sv.candidate.length >= 50;
		},
		assignUser = function() {
			if (!confirm("Are you sure you want to entitle " + sv.candidate.length + " user(s) listed below to be " + cv.jobName + " ?")) return;
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
<?php $APP_PAGE -> print -> nav(); ?>
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
							<label>สาขาโครงงาน</label>
							<select name="type" required>
								<option value disabled selected>— กรุณาเลือก —</option>
								<?php foreach (str_split("ABCDEFGHIJKLM") as $et) echo '<option value="' . $et . '">' . pblcode2text($et)["th"] . '</option>'; ?>
							</select>
						</div>
						<button class="purple ripple-click">แต่งตั้ง</button>
					</div>
				</div>
				<fieldset><legend>รายชื่อบัญชี (คลิกเพื่อนำออก)</legend>
					<div class="candidate css-flex css-flex-inline css-flex-gap-10 css-flex-wrap"></div>
				</fieldset>
			</div>
		</details>
		<div class="form form-bs inline"><div class="group">
			<label><i class="material-icons">filter_list</i></label>
			<input type="search" name="filter" placeholder="ค้นหา..." />
		</div></div>
		<div class="table static responsive striped"><table>
			<thead><tr>
				<th>ลำดับ</th><th>ชื่อ</th><th>สาขา</th><th>เปิดสิทธิ์</th><th>หัวหน้า</th>
			</tr></thead><tbody class="cmte-list">
				<tr><td colspan="5"><center class="css-flex"><div class="loading"></div></center></td></tr>
			</tbody>
		</table></div>
	</section>
</main>
<?php
	$APP_PAGE -> print -> materials();
	$APP_PAGE -> print -> footer();
?>