<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/")-1);
	$APP_RootDir = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/"));
	require($dirPWroot."resource/hpe/init_ps.php");
	$header_title = "ตรวจเล่มรายงาน";
	$header_desc = "ขั้นที่ 2: ประเมินผล";
	$home_menu = "is-pbl";

	require_once($APP_RootDir."private/script/function/utility.php");
	require_once($APP_RootDir."private/script/function/database.php");
	$year = $_SESSION["stif"]["t_year"];
	$getTimeout = $APP_DB[0] -> query("SELECT value FROM config_sep WHERE year=$year AND name='PBL-cs_M'");
	$readTimeout = (!$getTimeout || !$getTimeout -> num_rows) ? "" : ($getTimeout -> fetch_array(MYSQLI_ASSOC))["value"];
	if (strlen($readTimeout) == 19) $readTimeout = date2TH(substr($readTimeout, 0, 10))." เวลา ".str_replace(":", ".", substr($readTimeout, 11, 5))." น.";
	$APP_DB[0] -> close();
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($dirPWroot."resource/hpe/heading.php"); require($dirPWroot."resource/hpe/init_ss.php"); ?>
		<style type="text/css">
			main > .container.widescreen { width: 100%; }
			main .wrapper {
				--pages: 3;
				width: 100%;
				display: block; /* overflow: hidden; */
				transition: var(--time-tst-fast) ease;
			}
			main .wrapper > div {
				transform: translateX(calc((-100% / var(--pages)) * (var(--page) - 1)));
				width: calc((100% + 2rem) * var(--pages));
				display: flex; transition: var(--time-tst-fast) ease;
				overflow: hidden;
			}
			main .wrapper .page {
				margin-right: 2rem;
				width: 100%; max-width: var(--mw); height: fit-content;
				transition: var(--time-tst-fast) ease;
			}
			main .wrapper .page > *:not(:last-child) { margin: 0px 0px 10px; }
			main .page-1 td:nth-child(1) { font-family: "pixelmix", monospace; font-weight: 100; font-size: 0.8em; }
			main .page-1 .form button { min-width: fit-content; }
			main .page-1 td:nth-child(4) ol { margin: 0; padding-left: 30px; }
			main .page-1 td:nth-child(4) ol li { white-space: nowrap; }
			main .mform {
				--page-h: calc(100vh - var(--top-height) - 50px - 47px);
				margin-bottom: 10px;
				display: grid; grid-template-columns: 6fr 4fr; grid-template-rows: 1fr; grid-column-gap: 10px;
			}
			main .mform section { height: var(--page-h); }
			main .mform section:nth-child(1) {
				display: flex; flex-direction: column;
				overflow: visible;
			}
			main .mform section:nth-child(2) .sform {
				border-top: 0.1rem solid var(--clr-bs-gray);
				border-bottom: 0.1rem solid var(--clr-bs-gray);
				overflow-y: auto;
			}
			main td button.no-action {
				opacity: 1 !important; filter: opacity(1) !important;
				/* cursor: not-allowed; */
			}
			main .mform textarea { resize: none; }
			main .mform output { margin-top: 10px; }
			main .mform iframe {
				width: calc(100% - 0.2rem); height: 100%;
				/* border: 0.1rem solid var(--clr-bs-gray);
				box-shadow: 0 4px 15px 2px rgba(0,0,0,0.35); */
				border: 1.25px solid var(--clr-bs-gray-dark);
				border-radius: 5px;
				background-color: var(--clr-gg-grey-300);
			}
			main .mform p { margin: 0 0 10px; }
			main .status {
				margin-bottom: 2.5px;
				align-items: center !important;
			}
			main .comment h3 { margin: 15px 0 5px; }
		</style>
		<link rel="stylesheet" href="/resource/css/extend/all-PBL.css" />
		<script type="text/javascript">
			const PBLform = {
				"STEM": {
					perMax: 4, questions: [
						{title: "ชิ้นงาน", sub: [
							["ชิ้นงานหรือวิธีการสามารถแก้ปัญหาได้ภายใต้สถานการณ์และเงื่อนไข", 1.25],
							["ชิ้นงานหรือวิธีการสามารถทดสอบการทำงานซ้ำได้", 1],
							["ชิ้นงานหรือวิธีการ สามารถนำไปประยุกต์ใช้ได้ในชีวิตประจำวันมีประโยชน์ต่อชุมชนหรือสังคม", 1.25],
							["ชิ้นงานมีความปลอดภัย มีความเหมาะสมกับผู้ใช้งาน และคำนึงถึงสิ่งแวดล้อม", 1]
						]},
						{title: "การออกแบบเชิงวิศวกรรม", sub: [
							["การทำงานตามกระบวนการออกแบบเชิงวิศวกรรมอย่างครบถ้วน", 3],
							["มีการทำงานตามกระบวนการออกแบบเชิงวิศวกรรมแต่ละขั้นได้อย่างถูกต้องและมีคุณภาพ", 4]
						]},
						{title: "การบูรณาการ S (วิทยาศาสตร์) M (คณิตศาสตร์) T (เทคโนโลยี)", sub: [
							["มีการบูรณาการความรู้ด้านสะเต็มศึกษาที่เกี่ยวข้องกับการแก้ปัญหาได้อย่างครบถ้วน", 4],
							["การอธิบายความรู้ด้านสะเต็มศึกษาที่เกี่ยวข้องและเชื่อมโยงกับการแก้ปัญหาได้อย่างถูกต้อง ชัดเจน", 4]
						]},
						{title: "กระบวนการคิด", sub: [
							["ชิ้นงานหรือวิธีการ ตลอดจนกระบวนการสร้าง มีความคิดสร้างสรรค์ <font>4 ลักษณะ: 1 ความคิดริเริ่มแปลกใหม่, 2 ความคิดคล่อง, 3 ความคิดยืดหยุ่น, 4 ความคิดละเอียดลออ</font>", 3]
						]},
						{title: "รูปเล่มรายงาน", sub: [
							["องค์ประกอบการเข้าเล่มรายงานถูกต้อง เนื้อหาครบถ้วนและเป็นไปตามรูปแบบการทำโครงงานสะเต็มศึกษา", 0.75],
							["การใช้ภาษาถูกหลักไวยากรณ์ มีรายละเอียดที่ชัดเจน เข้าใจง่าย", 1.25],
							["การใช้ศัพท์ทางวิทยาศาสตร์ คณิตศาสตร์ และเทคโนโลยีได้ถูกต้อง", 0.5]
						]}
					], noIndex: true, amount: 12
				},
				"Botanical": {
					perMax: 5, questions: [
						{title: "กระบวนการวิทยาศาสตร์ผนวกงานสวนพฤกษศาสตร์โรงเรียน", sub: [
							["ชื่อเรื่องสอดคล้องกับโครงงานที่ทำ", 2],
							["ความชัดเจนของวัตถุประสงค์ในการศึกษาที่มีความเกี่ยวข้องกับงานสวนพฤกษศาสตร์โรงเรียน", 2],
							["รูปแบบโครงงานมีหัวข้อตามที่โรงเรียนกำหนดครบถ้วน", 2],
							["วิธีการดำเนินงานมีความชัดเจน", 2]
						]},
						{title: "ผลการดำเนินงานที่เกิดขึ้น", sub: [
							["การวิเคราะห์ผลเป็นไปตามหลักการทางวิทยาศาสตร์", 2],
							["บันทึกผลการดำเนินงานอย่างเป็นระบบ", 2],
							["สรุปรายละเอียดของผลการศึกษาอย่างครบถ้วน", 2],
							["อภิปรายให้เห็นถึงข้อสนับสนุนหรือข้อขัดแย้งที่ค้นพบจากการดำเนินงานอย่างเหมาะสม", 2]
						]},
						{title: "ความคิดริเริ่มสร้างสรรค์", sub: [
							["ความคิดริเริ่มสร้างสรรค์ ความรู้ใหม่ นวัตกรรมในการทำโครงงาน", 2]
						]},
						{title: "การนำไปใช้ได้จริง", sub: [
							["ผลการดำเนินงานนำไปใช้งานได้จริง", 2]
						]}
					], noIndex: false, amount: 10
				},
				"SufEco": {
					perMax: 5, questions: [
						{title: "บทที่ 1 บทนำ", sub: [
							["ที่มาและความสำคัญของโครงงานมีการกล่าวเชื่อมโยงกับหลักเศรษฐกิจพอเพียง", 1],
							["วัตถุประสงค์สอดคล้องกับโครงงาน", 1],
							["ระบุขอบเขต ตัวแปรและประโยชน์ของการดำเนินโครงงานที่ชัดเจนและสอดคล้อง", 1]
						]},
						{title: "บทที่ 2 เอกสารและงานวิจัยที่เกี่ยวข้อง", sub: [
							["เอกสารและงานวิจัยที่ศึกษามีความสอดคล้องกับเรื่องที่ทำ", 1],
							["มีการศึกษาเนื้อหาที่เกี่ยวข้องกับเศรษฐกิจพอเพียง 3 ห่วง 2 เงื่อนไข", 1],
							["เอกสารและงานวิจัยที่ศึกษามีความน่าเชื่อถือ", 1]
						]},
						{title: "บทที่ 3 การดำเนินโครงงาน", sub: [
							["การใช้วัสดุอุปกรณ์มีความเหมาะสมกับโครงงาน", 1],
							["การดำเนินโครงงานไม่ใช้วัสดุอุปกรณ์ที่ก่อให้เกิดความสิ้นเปลือง", 1],
							["บูรณาการหลักปรัชญาของเศรษฐกิจพอเพียง 3 ห่วง 2 เงื่อนไขในการดำเนินโครงงาน", 1],
							["ผลสำเร็จของการดำเนินโครงงาน/ชิ้นงาน", 1]
						]},
						{title: "บทที่ 4 สรุปผล", sub: [
							["สรุปผลการดำเนินโครงงานตามวัตถุประสงค์", 1],
							["สรุปผลการดำเนินโครงงานที่ระบุถึงการบูรณาการหลักปรัชญาของเศรษฐกิจพอเพียง", 1]
						]},
						{title: "บทที่ 5 สรุป อภิปรายผล และข้อเสนอแนะ", sub: [
							["สรุป อภิปรายผลการดำเนินโครงงานที่สอดคล้องกับหลักปรัชญาของเศรษฐกิจพอเพียง", 1],
							["มีการระบุข้อเสนอแนะที่น่าสนใจ", 1]
						]},
						{title: "การพิมพ์/จัดเรียงข้อมูลในรายงานถูกต้องเหมาะสม", sub: [
							["มีการเรียบเรียงรายงานถูกต้องตามรูปแบบรายงานการดำเนินโครงงาน", 1],
							["มีการพิมพ์ถูกต้องตามหลักภาษาไทย", 1]
						]},
						{title: "โครงงานนำไปใช้ประโยชน์ในชีวิตประจำวันได้จริง", sub: [
							["ช่วยแก้ปัญหาในชีวิตประจำวันของนักเรียนได้จริง", 1],
							["ส่งผลดีทั้งต่อตนเองและสังคมรอบข้าง", 1]
						]},
						{title: "โครงงานมีความคิดสร้างสรรค์", sub: [
							["มีความคิดสร้างสรรค์และความคิดริเริ่ม", 1],
							["มีความเป็นนวัตกรรม (เป็นผลงานใหม่ไม่เคยมีมาก่อน)", 1]
						]},
					], noIndex: true, amount: 20
				},
				"Others": {
					perMax: 5, questions: [
						{title: "บทที่ 1 บทนำ", sub: [
							["ระบุข้อมูลของปัญหาที่นำไปสู่การทำโครงงานได้ชัดเจน", 1],
							["ระบุข้อมูลที่แสดงว่าโครงงานเรื่องนี้สามารถแก้ปัญหาที่ตั้งขึ้นได้", 1],
							["เขียนแสดงวัตถุประสงค์ที่สอดคล้องกับชื่อโครงงาน วิธีดำเนินงาน สามารถตรวจสอบได้ และสอดคล้องกับผลการดำเนินงาน", 1],
							["ระบุขอบเขตการศึกษา สมมติฐาน (ถ้ามี) ตัวแปร (ถ้ามี) นิยามศัพท์เฉพาะ (ถ้ามี) และประโยชน์ที่คาดว่าจะได้รับชัดเจน ถูกต้อง และสอดคล้องกับเรื่องที่ทำ", 1]
						]},
						{title: "บทที่ 2 เอกสารและงานวิจัยที่เกี่ยวข้อง", sub: [
							["เอกสารและงานวิจัยที่ศึกษามีความสอดคล้องกับเรื่องที่ทำ", 1],
							["มีการศึกษาเนื้อหาที่เกี่ยวข้องครบถ้วน", 1],
							["เอกสารและงานวิจัยที่ศึกษามีความน่าเชื่อถือ", 1]
						]},
						{title: "บทที่ 3 อุปกรณ์และวิธีการดำเนินงาน", sub: [
							["เลือกใช้วัสดุอุปกรณ์ (ถ้ามี) เครื่องมือเก็บข้อมูล และการวิเคราะห์ข้อมูลเหมาะสมกับเรื่องที่ทำ", 1],
							["แสดงวิธีการดำเนินการสอดคล้องกับวัตถุประสงค์", 1],
							["เขียนวิธีการดำเนินการชัดเจน ตามลำดับขั้นตอน เข้าใจได้ง่าย", 1]
						]},
						{title: "บทที่ 4 ผลการดำเนินงาน", sub: [
							["เลือกวิธีการนำเสนอผลการดำเนินการได้เหมาะสมกับเรื่องที่ทำ", 1],
							["เขียนผลการดำเนินงานสอดคล้องกับวัตถุประสงค์และวิธีการดำเนินงาน", 1],
							["เขียนผลการดำเนินงานด้วยภาษาที่เข้าใจง่าย และเป็นไปตามหลักวิชาการ", 1]
						]},
						{title: "บทที่ 5 สรุป อภิปรายผล และข้อเสนอแนะ", sub: [
							["สรุปผลการดำเนินงานสอดคล้องกับวัตถุประสงค์และผลการดำเนินงาน", 1],
							["เขียนอธิบายสาเหตุของผลการดำเนินงานโดยใช้หลักการที่สมเหตุสมผล", 1],
							["เขียนข้อเสนอแนะเพื่อพัฒนาต่อยอดหรือนำโครงงานไปใช้ประโยชน์", 1]
						]},
						{title: "การพิมพ์/จัดเรียงข้อมูลในรายงานถูกต้องเหมาะสม", sub: [
							["จัดเรียงลำดับหัวข้อตามรูปแบบรายงานที่โรงเรียนกำหนด", 1],
							["จัดพิมพ์ข้อความและจัดหน้ากระดาษตามรูปแบบรายงานที่โรงเรียนกำหนด", 1]
						]},
						{title: "โครงงานมีความคิดสร้างสรรค์", sub: [
							["โครงงานมีความคิดสร้างสรรค์", 1]
						]},
						{title: "สอดคล้องกับสาขาโครงงาน", sub: [
							["สอดคล้องกับสาขาโครงงาน", 1]
						]},
					], noIndex: true, amount: 20
				},
			}
			$(document).ready(function() {
				PBL.init();
				chatApp.init(true);
			})
			const PBL = (function(d) {
				const cv = { API_URL: "/t/PBL/v2/api/" };
				var sv = {
					started: false, scrolled: null, chatInit: false,
					dataLoading: []
				};
				var initialize = function() {
					if (!sv.started) {
						getList();
						$(window).on("resize", function() {
							$("main .wrapper > div").css("--mw", $("main .wrapper").width().toString()+"px");
						}).trigger("resize");
						sv.started = true;
					}
				},
				checkPage = function() {
					if (!/^book=[A-Z0-9]{6}$/.test(location.hash.substring(1))) return;
					var book = location.hash.substring(6);
					openFile(book);
					setTimeout(function() {
						sv.scrolled = $("main .action-"+book).offset().top - $(window).height() / 2;
					}, 250);
				},
				getList = function() {
					ajax(cv.API_URL+"evaluation", {type: "list", act: "paper-mark"}).then(function(dat) {
						$("main .loading").remove();
						if (!dat) return;
						var ctn = $("main div.container .wrapper .page-1");
						Object.keys(dat).forEach(ec => {
							ctn.append("<h3 class=\"center\">"+ec+"</h3>");
							var table = '<div class="table wrap"><table><thead><tr><th>รหัสโครงงาน</th><th>ชื่อโครงงาน</th><th>ผลประเมิน</th><th>จำนวนกรรมการ<br>ที่ตรวจแล้ว</th></tr></thead><tbody>';
							Object.keys(dat[ec]).sort().forEach(eg => {
								table += '<tr data-head><th colspan="4">มัธยมศึกษาปีที่ '+eg+'</th></tr>';
								dat[ec][eg].forEach(ep => {
									table += '<tr><td class="center select-all">'+ep["code"]+'</td><td>'+ep["name"]+'</td><td><div class="form center action-'+ep["code"]+'">'+(ep["mark"]?'<div class="group pill"><button class="green small no-action" disabled>ประเมินแล้ว</button><button class="yellow small" onClick="PBL.startView(\''+ep["code"]+'\')" data-title="แก้ไข"><i class="material-icons">edit</i></button></div>':'<button class="blue small" onClick="PBL.startView(\''+ep["code"]+'\')">ตรวจ</button>')+'</div></td><td><center><a '+(ep["aogc"]!="0"?'onClick="PBL.getGradedCommitteeNames(\''+ep["code"]+'\', this)" href="javascript:" draggable="false"':'style="color: var(--clr-bs-red);"')+'>'+(ep["aogc"]!="0"?ep["aogc"]+' ท่าน':"ไม่มี")+'</a></center></td></tr>';
								});
							}); ctn.append(table+'</tbody></table></div>');
						});
						$("main .oform, main .message:where(.minWarn, .features)").toggle("blind");
						<?php if (strlen($readTimeout)) { ?>$("main .message.timeWarn").toggle("blind");<?php } ?>
						$('main select[name^="pr:"]').on("change", function() {
							var code = this.getAttribute("name").split(":")[1];
							$('main button[onClick^="PBL.saveGrade(\''+code+'\'"]').removeAttr("disabled");
						}); setTimeout(function() { $(window).trigger("resize"); }, 750);
						$("main .wrapper > div").css("height", $(`main .wrapper .page-${$("main .wrapper > div").css("--page")}`).outerHeight().toString()+"px");
						checkPage();
					});
				},
				getGradedCommitteeNames = function(code, me) {
					var me = $(me).attr("disabled", "");
					if (sv.dataLoading.includes(code)) return;
					sv.dataLoading.push(code);
					ajax(cv.API_URL+"evaluation", {type: "list", act: "graded-committee", param: code}).then(function(dat) {
						if (!dat) return setTimeout(function() {
							me.removeAttr("disabled");
							sv.dataLoading.splice(sv.dataLoading.indexOf(code), 1);
						}, 750);
						var list = $(`<ol></ol>`);
						dat.forEach(ec => list.append(`<li>${ec}</li>`));
						me.parent().replaceWith(list);
						// Readjust view
						var height = [
							$(`main .wrapper .page-${$("main .wrapper > div").css("--page")}`).outerHeight(),
							$("main .wrapper .page-1").outerHeight()
						]; $("main .wrapper > div").animate({ height: height[1] });
						$(window).trigger("resize");
					});
				},
				toPage = function(pageNo) {
					var height = [
						$(`main .wrapper .page-${$("main .wrapper > div").css("--page")}`).outerHeight(),
						$(`main .wrapper .page-${pageNo}`).outerHeight()
					], wait = height[0] > height[1] ? 1e3 : 0;
					switch (pageNo) {
						case 1: {
							d.querySelector('main iframe[name="viewer"]').src = "";
							if (sv.scrolled != null) {
								$("html, body").animate({ scrollTop: sv.scrolled });
								$("main .wrapper > div").animate({ height: height[1] }, wait);
								sv.scrolled = null; sv.chatInit = false;
							} $('main output[name="time"]').val("").hide();
							$('main > .container').removeClass("widescreen");
						break; }
						case 2: {
							sv.scrolled = $(d).scrollTop();
							$("html, body").animate({ scrollTop: 0 });
							$("main .wrapper > div").animate({ height: height[1] }, wait);
							$('main > .container').addClass("widescreen");
						break; }
					} $("main .wrapper > div").css("--page", parseInt(pageNo));
					$(window).trigger("resize");
				},
				openFile = async function(code) {
					d.querySelector('main iframe[name="viewer"]').src = "/t/PBL/v2/preview?file=report-all&code="+code;
					sv.code = code;
					chatApp.start("tch", [sv.code]);
					history.replaceState(null, null, location.pathname+location.search+"#book="+sv.code);
					sys.back.logPageHistory();
					ajax(cv.API_URL+"submission", {type: "load", act: "mark", param: sv.code}).then(function(dat) {
						if (dat) {
							loadForm(dat.type);
							if (dat.score) {
								let qNo = 1;
								dat.score.split(",").forEach(em => {
									$('main .mform [name="c'+(qNo++).toString()+'"]').val(em);
								})
							} if (dat.note!=null && dat.note.length) $('main .mform [name="cNote"]').val(dat.note);
							if (dat.submit.length) $('main output[name="time"]').val(dat.submit).show();
						}
					}); toPage(2);
				},
				loadForm = function(type) {
					var branch; switch (type) {
						case "B": branch = "STEM"; break;
						case "C": branch = "Botanical"; break;
						case "F": branch = "SufEco"; break;
						default: branch = "Others"; break;
					} sv.branch = branch;
					// Render
					let form = PBLform[branch], render = "", noIndex = [0, 0, 0];
					/* --- Temporary --- */
					if (form.perMax != 4) app.ui.notify(1, [1, "คะแนนเต็ม "+form.perMax.toString()+" คะแนน<br><ol style=\"margin: 0; padding: 0 0 0 20px;\"><li>น้อยที่สุด</li><li>น้อย</li><li>ปานกลาง</li><li>มาก</li><li>มากที่สุด</li></ol>"]);
					else app.ui.notify(1, [1, "คะแนนเต็ม "+form.perMax.toString()+" คะแนน<br><ol style=\"margin: 0; padding: 0 0 0 20px;\"><li>พอใช้</li><li>ดี</li><li>ดีมาก</li><li>ดีเยี่ยม</li></ol>"]);
					/* --- Temporary --- */
					form.questions.forEach(eg => {
						// render += '<tr><th>'+(form.noIndex ? (++noIndex[1]).toString() : "")+'</th><th align="left">'+eg.title+'</th><th></th></tr>';
						render += '<tr>'+(form.noIndex ? '<th colspan="2" align="left" data-chapter="'+(++noIndex[1]).toString()+'">' : '<th></th><th align="left">')+eg.title+'</th><th></th></tr>';
						noIndex[2] = 0;
						eg.sub.forEach(eq => {
							render += '<tr><td class="center">'+(form.noIndex ? noIndex[1].toString()+"."+(++noIndex[2]).toString() : (++noIndex[1]).toString())+'</td><td>'+eq[0]+'</td><td><input type="number" name="c'+(++noIndex[0]).toString()+'" min="1" max="'+form.perMax.toString()+'" step="1" maxlength="1" data-weight="'+eq[1]+'"></td></tr>';
						});
					}); render += '<tr><td colspan="3"><div class="group split status">บันทึกช่วยจำ<button class="black hollow pill small icon" disabled><i class="material-icons">lock</i>ส่วนตัว</button></div><textarea name="cNote" row="3"></textarea></td></tr><tr><td colspan="3"><button class="green full-x" onClick="return PBL.saveGrade()" type="submit" disabled>บันทึก</button></td></tr>'
					$("main .mform tbody").html(render);
					$("main .mform input, main .mform textarea").on("input change", function() {
						$('main .mform button[class~="green"]').removeAttr("disabled");
						sv.pending = true;
					});
				},
				record = function() {
					(async function() {
						// Get edit
						let form = PBLform[sv.branch], newScore = "", sum = 0, noIndex = 0, pass = true;
						form.questions.forEach(eg => {
							if (!pass) return;
							eg.sub.forEach(eq => {
								if (!pass) return;
								var target = $('main .mform input[name="c'+(++noIndex).toString()+'"]');
								var qAns = target.val();
								qAns = (qAns=="" ? 0 : parseInt(qAns));
								if (qAns < 0 || qAns > form.perMax) {
									// Raise error
									target.focus(); app.ui.notify(1, [2, "Invalid score"]);
									pass = false;
								}
								sum += qAns*eq[1];
								newScore += (newScore.length?",":"")+qAns.toString();
							});
						}); var note = d.querySelector('main .mform [name="cNote"]').value.trim();
						// Save
						if (!pass) return;
						$('main .mform button[class~="green"]').attr("disabled", "");
						if (sum!=0 || note.length) ajax(cv.API_URL+"submission", {type: "save", act: "mark", param: {
							code: sv.code, raw: newScore, total: sum, note: note
						}}).then(function(dat) {
							if (dat) {
								app.ui.notify(1, [0, "บันทึกสำเร็จ"]);
								// Update status in list
								$("main .action-"+sv.code).html('<div class="group pill"><button class="green small no-action" disabled>ประเมินแล้ว</button><button class="yellow small" onClick="PBL.startView(\''+sv.code+'\')" data-title="แก้ไข"><i class="material-icons">edit</i></button></div>');
							} else $('main .mform button[class~="green"]').removeAttr("disabled");
						}); else app.ui.notify(1, [2, "กรุณากรอกข้อมูลอย่างน้อย 1 ช่องก่อนบันทึก"]);
					}()); return false;
				},
				search = function() {
					var query = $('main .oform input[name="find"]').val().trim();
					w3.filterHTML("main .table tbody", "tr:not([data-head])", query);
					// Readjust view
					var height = [
						$("main .wrapper .page-"+$("main .wrapper > div").css("--page")).height(),
						$("main .wrapper .page-1").height()
					]; $("main .wrapper > div").height(height[1]);
					$(window).trigger("resize");
				},
				backToList = function() {
					(function() {
						if (!sv.pending || confirm("You have unsaved changes. If you leave now your updates won't be saved. Do you want to proceed ?")) {
							sv.code = null;
							sv.pending = false;
							sv.branch = null;
							toPage(1);
							history.replaceState(null, null, location.pathname+location.search);
							sys.back.logPageHistory();
						}
					}()); return false;
				},
				goToChat = function() {
					(function() {
						$("html, body").animate({ scrollTop: $("#comment").offset().top }, 600, $.bez([0.68, -0.6, 0.32, 1.6]));
					}()); return false;
				};
				return {
					init: initialize,
					startView: openFile,
					saveGrade: record,
					filterByText: search,
					selection: backToList,
					addComment: goToChat,
					getGradedCommitteeNames
				};
			}(document)); top.PBL = PBL;
		</script>
		<script type="text/javascript" src="/resource/js/extend/all-PBL.js"></script>
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
				<div class="wrapper">
					<div style="--page:1;">
						<div class="page page-1">
							<center class="features message blue" style="display: none;">
								<div class="center" style="display: flex; gap: 10px;">
									<i class="material-icons">info</i>
									<span>เฉพาะข้อความที่พิมพ์ส่งในส่วน "สนทนากับนักเรียน" ที่จะแสดงให้นักเรียนเห็น<br><u>บันทึกช่วยจำจะไม่แสดงกับนักเรียน</u>หรือผู้อื่นนอกจากคุณ</span>
								</div>
								<hr>
								<div class="form inline center">ใช้ปุ่ม <button class="yellow icon" disabled=""><i class="material-icons" style="transform: rotate(180deg);">logout</i></button> เพื่อกลับจากหน้าประเมินคะแนนสู่หน้าตารางโครงงาน</div>
							</center>
							<center class="timeWarn message yellow" style="display: none;">หลังวันที่ <?php if (strlen($readTimeout)) echo $readTimeout; ?> เป็นต้นไป<br>ท่านอาจเห็นจำนวนโครงงานที่สามารถตรวจได้จำนวนลดลงหรือไม่เห็นโครงงานใดเลย</center>
							<form class="form oform" onSubmit="return false;" style="display: none;" onSubmit="return false;">
								<div class="group">
									<span><i class="material-icons">search</i></span>
									<input type="search" name="find" placeholder="Find..." onInput="PBL.filterByText()">
								</div>
							</form>
							<center class="minWarn message cyan" style="display: none;">โครงงานแต่ละเล่ม ควรได้รับการพิจารณาคะแนนจากกรรมการอย่างน้อย 3 ท่าน</center>
						</div>
						<div class="page page-2">
							<form class="mform">
								<section>
									<iframe name="viewer"></iframe>
									<output name="time" style="display: none;"></output>
								</section>
								<section class="form">
									<div class="sform">
										<div class="table wrap"><table><thead><tr>
											<th>ข้อ</th><th>หัวข้อ</th><th>คะแนนที่ได้</th>
										</tr></thead><tbody>

										</tbody></table></div>
									</div>
									<div class="group split">
										<button class="gray hollow pill" onClick="return PBL.addComment()">Add a comment</button>
										<button class="yellow icon" onClick="return PBL.selection()" type="reset" data-title="Back">
											<i class="material-icons" style="transform: rotate(180deg);">logout</i>
										</button>
									</div>
								</section>
							</form>
							<div class="comment">
								<h3>สนทนากับนักเรียน</h3>
								<div class="chat" id="comment">
									<span class="start" hidden></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</main>
		<?php require($dirPWroot."resource/hpe/material.php"); ?>
		<footer>
			<?php require($dirPWroot."resource/hpe/footer.php"); ?>
		</footer>
	</body>
</html>