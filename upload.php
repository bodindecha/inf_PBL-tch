<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
	require($dirPWroot."resource/hpe/init_ps.php");
	$header_title = "แก้ไข้กลุ่ม PBL";
	$header_desc = "อัปโหลดไฟล์ให้กลุ่ม";
	$home_menu = "is-pbl";
	
	$upload_success = $_SESSION["var"]["PBL-upload-status"] ?? false;
	if ($upload_success) unset($_SESSION["var"]["PBL-upload-status"]);
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($dirPWroot."resource/hpe/heading.php"); if (!$upload_success) require($dirPWroot."resource/hpe/init_ss.php"); ?>
		<style type="text/css">
			main { background-color: var(--clr-gg-grey-100); }
			main .form div.box {
				margin: 0 auto 10px;
				width: 560px; height: 315px;
				border-radius: 5px; border: 2.5px dashed var(--clr-bs-gray);
				background-color: var(--clr-gg-grey-300); background-size: contain; background-repeat: no-repeat; background-position: center;
				/* display: flex; justify-content: center; */
				overflow: hidden; transition: var(--time-tst-fast);
			}
			main .form div.box:after {
				margin: auto;
				position: relative; top: -50%; transform: translateY(-100%);
				text-align: center; text-shadow: 1.25px 1.25px #FFFA;
				display: block; content: "Drag & Drop your file here or Browse";
				pointer-events: none;
			}
			main .form input[type="file"] {
				margin: auto;
				width: 100%; height: 100%; transform: translateY(-2.5px);
				opacity: 0%; filter: opacity(0%);
			}
			main .form div.box:focus-within {
				border-color: var(--clr-bs-blue);
				box-shadow: 0px 0px 0px 0.25rem rgb(13 110 253 / 25%);
			}
			main .form button {
				/* margin: 0 auto; */
				min-width: /*60%*/ 20%;
			}
			main .upload-icon {
				transform: scale(1.25);
				color: var(--clr-gg-blue-700);
				display: flex; justify-content: center; align-items: end; gap: 5px;
			}
			main .upload-icon .animation {
				transform: scaleY(-1);
				display: flex; align-items: baseline; overflow-y: hidden;
				animation: uploading 1.5s ease-in-out infinite forwards;
			}
			@keyframes uploading {
				from { height: 0px; }
				95%, to { height: 24px; }
			}
			main .upload-icon .animation > i { transform: scaleY(-1); }
			@media only screen and (max-width: 768px) {
				main .form div.box { width: 320px; height: 180px; }
			}
			/* Special effects */
			main .success-load {
				width: 100%; height: 100vh;
				display: flex; justify-content: center; align-items: center;
			}
			main .success-load .holder {
				margin: auto;
				width: 50px; height: 50px;
			}
			main .success-load .holder > img {
				width: inherit; height: inherit;
				object-fit: contain;
			}
		</style>
		<script type="text/javascript">
			<?php
				if (isset($_SESSION["var"]["PBL-message-upload"])) {
					foreach ($_SESSION["var"]["PBL-message-upload"] as $msg)
						echo 'app.ui.notify(1, ['.$msg[0].', "'.$msg[1].'"]);';
					unset($_SESSION["var"]["PBL-message-upload"]);
				}
			?>
			<?php if ($upload_success) echo 'top.PBL.save.file("complete");'; else { ?>
			const gsef = (function() {
				if (typeof top.PBL === "undefined") top.PBL = {uploadType: () => "mindmap"};
				var cv = {
					API_URL: "/t/PBL/v2/api/",
					workType: top.PBL.uploadType(),
					fileDetail: {
						"mindmap": {
							name: "แผนผังความคิดบูรณาการ 8 กลุ่มสาระการเรียนรู้",
							sizeLimit: 5, closeTime: null
						}, "IS1-1": {
							name: "ใบงาน IS1-1 (ประเด็นที่ต้องการศึกษา) ",
							sizeLimit: 10, closeTime: null
						}, "IS1-2": {
							name: "ใบงาน IS1-2 (การระบุปัญหา) ",
							sizeLimit: 10, closeTime: null
						}, "IS1-3": {
							name: "ใบงาน IS1-3 (การระบุสมมติฐาน) ",
							sizeLimit: 10, closeTime: null
						}, "report-1": {
							name: "เล่มรายงานโครงงานบทที่ 1 ",
							sizeLimit: 20, closeTime: null
						}, "report-2": {
							name: "เล่มรายงานโครงงานบทที่ 2 ",
							sizeLimit: 25, closeTime: null
						}, "report-3": {
							name: "เล่มรายงานโครงงานบทที่ 3 ",
							sizeLimit: 25, closeTime: null
						}, "report-4": {
							name: "เล่มรายงานโครงงานบทที่ 4 ",
							sizeLimit: 15, closeTime: null
						}, "report-5": {
							name: "เล่มรายงานโครงงานบทที่ 5 ",
							sizeLimit: 20, closeTime: null
						}, "report-all": {
							name: "รวมเล่มรายงานโครงงาน (ฉบับเต็ม)",
							sizeLimit: 50, closeTime: null
						}, "abstract": {
							name: "บทคัดย่อโครงงาน",
							sizeLimit: 5, closeTime: null
						}, "poster": {
							name: "โปสเตอร์",
							sizeLimit: 30, closeTime: null
						}
					}
				}; var sv = {};
				const mb2b = MB => MB*1024000,
					kb2mb = KB => KB/1024,
					b2kb = B => B/1024,
					b2mb = B => B/1024000;
				var initialize = function() {
					if (self == top) location = "/error/902";
					else if (cv.workType == "") {
						app.ui.notify(1, [3, "Error: No such file type. Please try again."])
						top.app.ui.lightbox.close();
					} else {
						$("main h2:first-child").html('<i class="material-icons">attach_file</i> ไฟล์'+cv.fileDetail[cv.workType].name);
						$('main output[name="sizeLimit"]').val(cv.fileDetail[cv.workType].sizeLimit);
						checkSubmitted();
						checkTimeout();
					}
				};
				var checkSubmitted = function() {
					$.post(cv.API_URL+"group-status", {type: "work", act: "file", param: {code: top.PBL.groupCode(), file: cv.workType}}, function(result) {
						var dat = JSON.parse(result);
						if (dat.success) {
							if (dat.info.fileSent) $('<center class="message yellow">นักเรียนได้ส่งไฟล์'+cv.fileDetail[cv.workType].name+'แล้ว หากส่งอีกรอบระบบจะนำไฟล์ล่าสุดที่ส่งให้ครูพิจารณาคะแนน</center>').insertAfter("main h2:first-child");
						} else dat.reason.forEach(em => app.ui.notify(1, em));
					});
				};
				var checkTimeout = function() {
					var lastCall = cv.fileDetail[cv.workType].closeTime;
					if (lastCall != null && Date.now() > new Date(lastCall).getTime()) {
						$('main h2 ~ *:not([style="display: none;"]').toggle("drop", function() { this.remove(); });
						$('<div class="message red center" style="display: none;">ขณะนี้หมดเวลาส่งและแก้ไข'+cv.fileDetail[cv.workType].name+'แล้ว</message>')
							.insertAfter("main .upload-icon")
							.toggle("clip");
					}
				};
				var byte2text = function(bytes) {
					let nv;
					if (bytes < 1024000) nv = Math.round(b2kb(bytes)*100)/100;
					else nv = Math.round(b2mb(bytes)*100)/100;
					if (!nv*100%100) nv = parseInt(nv);
					return nv+(bytes < 1024000 ? " KB" : " MB");
				};
				var upload = function() {
					(function() {
						if (!validate_file(true)) $('main .form [name="usf"]').focus();
						else {
							$("main .form button").attr("disabled", "");
							$("main .upload-icon").show();
							$("main .form")
								.append(addVal("filePart", cv.workType))
								.append(addVal("code", top.PBL.groupCode()))
								.submit();
						}
					}()); return false;
				};
				var addVal = (name, value) => '<input type="hidden" name="'+name+'" value="'+value+'">';
				var validate_file = function(recheck) {
					var f = document.querySelector('.form [name="usf"]').files[0],
						preview = $("main .form div.box"), fprop = {
							name: document.querySelector('main .form input[data-name="name"]'),
							size: document.querySelector('main .form input[data-name="size"]')
						};
					const wDt = cv.fileDetail[cv.workType];
					// if (!recheck && typeof sv.img_link === "string") URL.revokeObjectURL(sv.img_link);
					if (typeof f !== "undefined") {
						let filename = f.name.toLowerCase().split(".");
						if (["png", "jpg", "jpeg", "heic", "heif", "gif", "pdf"].includes(filename[filename.length-1]) && (f.size > 0 && f.size < mb2b(wDt.sizeLimit))) {
							if (!recheck) {
								fprop["name"].value = f.name;
								fprop["size"].value = byte2text(f.size);
								try { if (!isSafari) {
									sv.img_link = URL.createObjectURL(f);
									preview.css("background-image", 'url("'+sv.img_link+'")');
								} } catch(ex) {}
							} return true;
						} else app.ui.notify(1, [2, "กรุณาตรวจสอบว่าไฟล์ของคุณเป็นประเภท PNG/JPG/JPEG/HEIC/HEIF/GIF/PDF และมีขนาดไม่เกิน "+wDt.sizeLimit.toString()+"MB"]);
					} else {
						fprop["name"].value = ""; fprop["size"].value = "";
						preview.removeAttr("style");
						if (recheck) app.ui.notify(1, [1, "กรุณาเลือกไฟล์"+wDt.name+"."]);
					} return false;
				};
				return {
					init: initialize,
					out: upload,
					validate_file: validate_file,
				};
			}());
			$(document).ready(function() {
				$('main .form [name="usf"]').on("change", function() { gsef.validate_file(false); });
				gsef.init();
			}); <?php } ?>
		</script>
	</head>
	<body class="nohbar">
		<main>
			<?php if ($upload_success) { ?>
				<div class="success-load">
					<div class="holder">
						<img src="/resource/images/widget-load_spinner.gif" draggable="false" alt="Loading...">
					</div>
				</div>
			<?php } else { ?>
				<div class="container">
					<h2></h2>
					<form class="form" method="post" enctype="multipart/form-data" action="/t/PBL/v2/api/group-upload">
						<div class="box"><input type="file" name="usf" accept=".png, .jpg, .jpeg, .heic, .heif, .gif, .pdf" required></div>
						<div class="group">
							<span>ชื่อไฟล์</span>
							<input type="text" data-name="name" readonly>
						</div>
						<div class="group split">
							<div class="group">
								<span>ขนาดไฟล์</span>
								<input type="text" data-name="size" readonly>
							</div>
							<button class="blue" onClick="return gsef.out()">อัปโหลด</button>
						</div>
					</form>
					<div class="upload-icon" style="display: none;">
						<span>กำลังอัปโหลด...</span><div class="animation">
							<i class="material-icons">file_upload</i>
						</div>
					</div>
					<p>ประเภทไฟล์ที่รับ: png, jpg, jpeg, heic, heif, gif, pdf<br>ขนาดไฟล์สูงสุด: <output name="sizeLimit"></output> MB</p>
				</div>
			<?php } ?>
		</main>
		<?php require($dirPWroot."resource/hpe/material.php"); ?>
		<footer>
			<?php require($dirPWroot."resource/hpe/footer.php"); ?>
		</footer>
	</body>
</html>