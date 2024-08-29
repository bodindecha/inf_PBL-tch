<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);
	require($dirPWroot."resource/hpe/init_ps.php");
	$header_title = "ตรวจเล่มรายงาน";
	$header_desc = "ขั้นที่ 1: ผ่าน/ไม่ผ่าน";
	$home_menu = "is-pbl";

	$timesUp = strtotime("2024-01-08 23:59:59");
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($dirPWroot."resource/hpe/heading.php"); require($dirPWroot."resource/hpe/init_ss.php"); ?>
		<style type="text/css">
			main .proj-list {
				display: flex; flex-direction: column; gap: 10px;
			}
			main td:nth-child(1) { font-family: "pixelmix", monospace; font-weight: 100; font-size: 0.8em; }
			main td div.form {
				min-width: max-content;
				justify-content: space-between;
				gap: 5px;
			}
			main td div.form .group {
				width: 100%;
				align-self: center;
				gap: 5px;
			}
			main td div.form .group.right { justify-content: flex-end; gap: 0; }
			main td div.form a[role=button] { min-width: fit-content; }
			main td div.form select {
				padding: 0.5px 7.5px;
				width: fit-content; height: 30px;
				font-size: 0.8em;
			}
			main td .form button.no-action {
				opacity: 1 !important; filter: opacity(1) !important;
				/* cursor: not-allowed; */
			}
		</style>
		<script type="text/javascript">
			$(document).ready(function() {
				PBL.init();
			})
			const PBL = (function(d) {
				const cv = {
					API_URL: "/t/PBL/v2/api/",
					controlField: "data-load-submission-time"
				};
				var sv = {
					started: false,
					dataLoading: []
				};
				var initialize = function() {
					if (!sv.started) {
						getList();
						<?php if (time() < $timesUp) { ?>
							setTimeout(() => location.reload(), <?=$timesUp - time()?>*1e3);
						<?php } ?>
						sv.started = true;
					}
				},
				getList = function() {
					ajax(cv.API_URL+"evaluation", {type: "list", act: "paper-grade"}).then(function(dat) {
						if (dat) {
							var ctn = $("main div.container .proj-list");
							Object.keys(dat).forEach(ec => {
								ctn.append("<h3 class=\"center\">"+ec+"</h3>");
								var table = '<div class="table wrap"><table><thead><tr><th>รหัสโครงงาน</th><th>ชื่อโครงงาน</th><th>ผลประเมิน</th></tr></thead><tbody>';
								Object.keys(dat[ec]).sort().forEach(eg => {
									table += '<tr data-head><th colspan="3">มัธยมศึกษาปีที่ '+eg+'</th></tr>';
									dat[ec][eg].forEach(ep => {
										table += '<tr><td class="center select-all">'+ep["code"]+'</td><td>'+ep["name"]+'</td><td><div class="form">';
										if (!ep["sent"]) table += '<div class="center"><button class="red small no-action pill" disabled>ยังไม่ส่งไฟล์</button><div>';
										else {
											// table += '<div class="group spread"><a role="button" class="cyan small" href="/t/PBL/v2/preview?file=report-all&code='+ep["code"]+'" onClick="PBL.viewfile(\''+ep["code"]+'\', \''+ep["time"]+'\', event)" target="_blank" draggable="false"'+(ep["time"].length?' data-title="'+ep["time"]+'"':"")+'>เปิดไฟล์</a>';
											table += '<div class="group spread"><a role="button" class="cyan small" href="/t/PBL/v2/preview?file=report-all&code='+ep["code"]+'" '+cv.controlField+'="'+ep["code"]+'" target="_blank" draggable="false">เปิดไฟล์</a>';
											if (["1G", "2S", "3B", "4M"].includes(ep["rank"])) table += '<div class="center"><button class="blue small no-action pill" disabled>มีผลประเมินแล้ว</button></div>';
											else table += '<div class="group right"><select name="pr:'+ep["code"]+'"><option value="null" '+(ep["rank"]==null?"selected":"")+'>รอประเมิน</option>'+(ep["sent"]?'<option value="0P" '+(ep["rank"]=="0P"?"selected":"")+'>ผ่าน</option>':'')+'<option value="5N" '+(ep["rank"]=="5N"?"selected":"")+'>ไม่ผ่าน</option></select><button class="green small" onClick="PBL.saveGrade(\''+ep["code"]+'\', \''+ep["rank"]+'\')" disabled>บันทึก</button></div>';
											table += '</div>';
										} table += '</div></td></tr>';
									});
								}); ctn.append(table+'</tbody></table></div>');
								ctn.find("a[role=button]["+cv.controlField+"]").on("mouseover touchstart", loadSubTime);
							});
							$("main .oform").toggle("blind");
							$('main select[name^="pr:"]').on("change", function() {
								var code = this.getAttribute("name").split(":")[1];
								$('main button[onClick^="PBL.saveGrade(\''+code+'\'"]').removeAttr("disabled");
							})
						} $("main .loading").remove();
						$("main .message.timeWarn").toggle("blind");
					});
				},
				loadSubTime = function(event) {
					if (event.type == "click" && !event.ctrlKey && event.preventDefault) event.preventDefault();
					var me = $(event.target).off("mouseover touchstart").addClass("disabled");
					var code = me.attr(cv.controlField);
					if (sv.dataLoading.includes(code)) return;
					sv.dataLoading.push(code);
					ajax(cv.API_URL+"submission", {type: "load", act: "subTime", param: code}).then(function(dat) {
						me.removeClass("disabled");
						if (dat) {
							me.removeAttr(cv.controlField)
								.attr("onClick", "PBL.viewfile('"+code+"', '"+dat.timestamp+"', event)")
								.attr("data-title", dat.timestamp);
							if (dat.isLate) me.toggleClass("cyan yellow");
						} else setTimeout(function() {
							me.on("mouseover touchstart", loadSubTime);
							sv.dataLoading.splice(sv.dataLoading.indexOf(code), 1);
						}, 750);
						if (event.type == "click" && !event.ctrlKey) me.click();
					});
				},
				openFile = function(code, time, e) {
					if (ppa.ctrling()) return;
					if (e.preventDefault) e.preventDefault();
					app.ui.lightbox.open("mid", { title: "เล่มรายงานรหัสโครงงาน \""+code+"\""+(time.length ? " ("+time+")" : ""), allowclose: true,
						html: '<iframe src="/t/PBL/v2/preview?file=report-all&code='+code+'" style="width:90vw;height:80vh;border:none">Loading...</iframe>'
					});
				},
				record = function(code, data) {
					var update = $('main select[name^="pr:'+code+'"]');
					if (update.val() == data) {
						app.ui.notify(1, [1, "Nothing's changed"]);
						update.next().attr("disabled", "");
					} else (async function() {
						update.next().attr("disabled", "");
						ajax(cv.API_URL+"submission", {type: "save", act: "rank", param: {code: code, rank: update.val()}}).then(function(dat) {
							if (dat) {
								update.next().attr("disabled", "")
									.off("click").attr("onClick", `PBL.saveGrade('${code}', '${update.val()}')`);
								app.ui.notify(1, [0, "บันทึกผลโครงงาน "+code+" สำเร็จ"]);
							} else update.next().removeAttr("disabled");
						});
					}());
				},
				search = function() {
					var query = $('main .oform input[name="find"]').val().trim();
					w3.filterHTML("main .table tbody", "tr:not([data-head]", query);
				};
				return {
					init: initialize,
					viewfile: openFile,
					saveGrade: record,
					filterByText: search
				};
			}(document)); top.PBL = PBL;
		</script>
		<script type="text/javascript" src="https://cdn.TianTcl.net/static/script/lib/w3.min.js"></script>
	</head>
	<body>
		<?php require($dirPWroot."resource/hpe/header.php"); ?>
		<main shrink="<?php echo($_COOKIE['sui_open-nt'])??"false"; ?>">
			<div class="container">
				<h2><?=$header_title.$header_desc?></h2>
				<div class="loading medium">
					<img src="/resource/images/widget-load_spinner.gif" />
				</div>
				<form class="form oform" onSubmit="return false;" style="display: none;">
					<div class="group">
						<span><i class="material-icons">search</i></span>
						<input type="search" name="find" placeholder="Find..." onInput="PBL.filterByText()">
					</div>
				</form>
				<center class="timeWarn message yellow" hidden style="display: none;">หลังวันที่ 7 มกราคม 2567 เวลา 23.59 น. เป็นต้นไป<br>ท่านอาจเห็นจำนวนโครงงานที่สามารถตรวจได้จำนวนลดลงหรือไม่เห็นโครงงานใดเลย</center>
				<center class="message red" hidden>ขณะนี้หมดเวลาในการพิจารณาผ่าน/ไม่ผ่านโครงงานแล้ว</center>
				<div class="proj-list message-black" -disabled>

				</div>
			</div>
		</main>
		<?php require($dirPWroot."resource/hpe/material.php"); ?>
		<footer>
			<?php require($dirPWroot."resource/hpe/footer.php"); ?>
		</footer>
	</body>
</html>