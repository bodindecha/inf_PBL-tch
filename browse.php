<?php
    $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
	require($dirPWroot."resource/hpe/init_ps.php");
	$header_title = "ดูไฟล์ของโครงงาน PBL";
	$header_desc = "ตรวจงานของกลุ่ม";
	$home_menu = "is-pbl";
    $forceExternalBrowser = true;
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($dirPWroot."resource/hpe/heading.php"); require($dirPWroot."resource/hpe/init_ss.php"); ?>
		<style type="text/css">
			main .namelist {
				margin: -5px 0 15px; padding-left: 15px;
				white-space: nowrap;
			}
			main .namelist a[role="button"] {
				padding: 2.5px 7.5px;
				font-size: 12.5px; line-height: 20px;
			}
			main .namelist td:nth-child(n+3) { padding-left: 12.5px; }
			main .notRequirement { margin: 0 !important; }
			main .grader button { width: 100px; }
			main .file {
				min-height: 640px; height: calc(100vh - 40px - var(--top-height));
				border: 1.25px solid var(--clr-bs-gray-dark); border-radius: 5px;
				overflow: hidden;
			}
			main .file iframe {
				width: 100%; height: 100%;
				border: none;
				background-color: var(--clr-gg-grey-300);
			}
		</style>
		<link rel="stylesheet" href="/resource/css/extend/all-PBL.css">
		<script type="text/javascript">
			$(document).ready(function() {
				PBL.init();
			});
			const PBL = (function(d) {
				const cv = {
					API_URL: "/t/PBL/v2/api/",
					files: [
						["mindmap", "แผนผังความคิดบูรณาการ 8 กลุ่มสาระการเรียนรู้"],
						["IS1-1", "ใบงาน IS1-1 (ประเด็นที่ต้องการศึกษา)"],
						["IS1-2", "ใบงาน IS1-2 (การระบุปัญหา)"],
						["IS1-3", "ใบงาน IS1-3 (การระบุสมมติฐาน)"],
						["report-1", "เล่มรายงานโครงงานบทที่ 1"],
						["report-2", "เล่มรายงานโครงงานบทที่ 2"],
						["report-3", "เล่มรายงานโครงงานบทที่ 3"],
						["report-4", "เล่มรายงานโครงงานบทที่ 4"],
						["report-5", "เล่มรายงานโครงงานบทที่ 5"],
						["report-all", "รวมเล่มรายงานโครงงาน (ฉบับเต็ม)"],
						["abstract", "บทคัดย่อโครงงาน"],
						["poster", "โปสเตอร์"]
					]
				};
				var sv = { inited: false, fileOpen: false };
				var initialize = async function() {
					if (!sv.inited) {
						getGroupCode();
						checkPermission();
						getMember();
						chatApp.start("mod", [sv.code]);
						$('main .form select[name="wfs"]').on("change", function() {
							$("main button.open").removeAttr("disabled");
						});
						$('main .grader [name="score:in"]').on("change", function() {
							$("main .grader button").removeAttr("disabled");
						});
						sv.inited = true;
					}
				},
				getGroupCode = function() {
					var code = location.pathname.match(/\/[A-Z0-9]{6}\//);
					if (code != null && code.length)
						sv.code = code[0].slice(1, 7);
					else {} // Link broken
				},
				checkPermission = async function() {
					await ajax(cv.API_URL+"list", {type: "work", act: "permission", param: sv.code}).then(function(dat) {
						sv.permission = dat;
						// Render choices
						var option = $('main .form select[name="wfs"]');
						for (let fileIndex = 0; fileIndex <= 11; fileIndex++)
							if (dat[fileIndex] != null) option.append('<option value="'+fileIndex.toString()+'" '+(dat[fileIndex]=="0"?"disabled":"")+'>'+cv.files[fileIndex][1]+'</option>');
						if (Object.values(sv.permission).includes(2)) $("main .grader").show();
						// Show selection
						$("main .loader").hide();
						$("main .file-chooser").fadeIn();
					});
				},
				getMember = async function() {
					await ajax(cv.API_URL+"information", {type: "group", act: sv.code}).then(async function(dat) {
						if (dat) {
							$('main output[name="class"]').val(dat.class);
							await ajax(cv.API_URL+"information", {type: "person", act: "student", param: dat.member.join(",")}).then(function(dat2) {
								var index = 1, listBody = "";
								dat2.list.forEach(es => {
									listBody += '<tr><td>'+index.toString()+'.</td><td>'+es.fullname+' (<a href="/'+es.ID+'" target="_blank" draggable="false">'+es.nickname+'</a>)</td><td>เลขที่ '+es.number+'</td><td>';
									if (index++ == 1) listBody += '<a role="button" class="default" disabled>หัวหน้ากลุ่ม</a>';
									listBody += '</td></tr>';
								}); $('main .namelist tbody').html(listBody);
							});
						}
					});
				}
				loadFile = function() {
					$("main button.open").attr("disabled", "");
					var opt = parseInt($('main .form select[name="wfs"]').val());
					// Grader element
					if (opt >= 9 && opt <= 11) {
						$("main .notRequirement").fadeOut(); // .toggle("blind");
						$("main .grader .field").removeAttr("disabled");
					} else {
						$("main .notRequirement").fadeIn(); // .toggle("blind");
						$("main .grader .field").attr("disabled", "");
						$('main .grader [name="score:in"]').val("");
					} let maxScore; switch (opt) {
						case 9: maxScore = 3; break;
						case 10: case 11: maxScore = 1; break;
						default: maxScore = "";
					} $('main .grader [name="score:max"]').val(maxScore);
					$('main .grader [name="score:in"]').attr("max", maxScore);
					$("main .grader button").attr("disabled", "");
					// Render file
					if (sv.permission[opt] > 0) {
						openFile(opt);
						if (maxScore != "") loadScore(opt);
					} else {
						app.ui.notify(1, [3, "You don't have permission to view this file."]);
						if (sv.fileOpen) closeFile();
					}
				},
				openFile = async function(fIdx) {
					await ajax(cv.API_URL+"submission", {type: "load", act: "file", param: {code: sv.code, file: fIdx}}).then(function(dat) {
						if (dat) {
							$('main output[name="timestamp"]').val(dat.date);
							d.querySelector("main .file iframe").src = dat.link;
						} else {
							if (sv.fileOpen) closeFile();
							$('main output[name="timestamp"]').val("ยังไม่ส่งไฟล์");
						}
					});
					sv.fileOpen = true;
				},
				closeFile = function() {
					$('main output[name="timestamp"]').val("");
					d.querySelector("main .file iframe").src = "";
					sv.fileOpen = false;
				},
				loadScore = async function(fIdx) {
					await ajax(cv.API_URL+"submission", {type: "load", act: "score", param: {code: sv.code, file: fIdx}}).then(function(dat) {
						if (dat) {
							$('main .grader [name="score:in"]').val(dat.score);
							sv.score = {
								original: dat.score,
								part: fIdx
							};
						}
					});
				},
				saveScore = function() {
					(async function() {
						var dataBlock = $('main .grader [name="score:in"]');
						if (dataBlock.val() < 0 || dataBlock.val() > dataBlock.attr("max")) app.ui.notify(1, [2, "Invalid score"]);
						else if (dataBlock.val() == sv.score["original"]) {
							$("main .grader button").attr("disabled", "");
						} else await ajax(cv.API_URL+"submission", {type: "save", act: "score", param: {
							code: sv.code, file: sv.score["part"], newPoints: dataBlock.val()
						}}).then(function(dat) {
							if (dat) {
								sv.score["original"] = parseInt(dataBlock.val());
								$("main .grader button").attr("disabled", "");
								app.ui.notify(1, [0, "Score saved."]);
							}
						});
					}()); return false;
				};
				return {
					init: initialize,
					viewFile: loadFile,
					savePoints: saveScore
				}
			}(document));
		</script>
		<script type="text/javascript" src="/resource/js/extend/all-PBL.js"></script>
	</head>
	<body>
		<?php require($dirPWroot."resource/hpe/header.php"); ?>
		<main shrink="<?php echo($_COOKIE['sui_open-nt'])??"false"; ?>">
			<div class="container">
				<h2><?=$header_title?></h2>
				<p>สมาชิกกลุ่ม <output name="class"></output></p>
				<table class="namelist">
					<tbody></tbody>
				</table>
				<p class="loader">กำลังตรวจสอบสิทธิ์... <img height="15px" src="/resource/images/widget-load_spinner.gif" /></p>
				<div class="file-chooser form inline" style="display: none;">
					<div class="group">
						<span>ไฟล์งาน</span>
						<select name="wfs">
							<option value selected disabled>---กรุณาเลือก---</option>
						</select>
					</div>
					<button class="open blue" onClick="PBL.viewFile()" disabled>เปิด</button>
				</div>
				<form class="grader form message cyan" style="display: none;">
					<p class="notRequirement message yellow">กรุณาเปิดไฟล์<u>เล่มรายงาน</u> <u>บทคัดย่อ</u>หรือไฟล์<u>โปสเตอร์</u>เพื่อใช้ช่องลงคะแนน</p>
					<div class="field group split" disabled>
						<div class="group">
							<span>ได้</span>
							<input type="number" name="score:in" min="0" step="1" />
							<span>จากเต็ม</span>
							<input type="text" name="score:max" readonly />
							<span>คะแนน</span>
						</div>
						<button class="green" onClick="return PBL.savePoints()" disabled>บันทึก</button>
					</div>
				</form>
				<p><output name="timestamp"></output></p>
				<div class="file">
					<iframe name="viewer"></iframe>
				</div>
				<p>สนทนากับนักเรียน</p>
				<div class="chat" id="chat">
					<span class="start">
						<button class="gray hollow" onClick="chatApp.init()">Add a comment</button>
					</span>
				</div>
			</div>
		</main>
		<?php require($dirPWroot."resource/hpe/material.php"); ?>
		<footer>
			<?php require($dirPWroot."resource/hpe/footer.php"); ?>
		</footer>
	</body>
</html>