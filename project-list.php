<?php
    $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
	require($dirPWroot."resource/hpe/init_ps.php");
	$header_title = "รายชื่อกลุ่ม PBL";
	$header_desc = "รายการโครงงาน PBL";
	$home_menu = "is-pbl";
    $forceExternalBrowser = true;

	require_once($dirPWroot."resource/php/core/config.php");
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($dirPWroot."resource/hpe/heading.php"); require($dirPWroot."resource/hpe/init_ss.php"); ?>
		<style type="text/css">
			main .form .group.wrap { flex-wrap: wrap; }
			main button.small, main a[role="button"].small, main .form span.small {
				padding: 0.5px 7.5px;
				height: 30px;
				font-size: 0.8em;
			}
			main .oform .finder span i { transform: scaleX(-1); }
			main .oform .finder input { width: 100%; }
			main .browser .results {
				margin: 0 0 10px; padding: 0;
				list-style-type: none;
			}
			main .results > li {
				--li-trans: var(--time-tst-fast) cubic-bezier(0.65, 0, 0.35, 1) /* easeInOutCubic */;
				--li-shad: 0 1px 2px 0 rgb(60 64 67 / 30%), 0 2px 6px 2px rgb(60 64 67 / 15%);
				--li-bdr: 0.0625rem solid;

				border-top: var(--li-bdr) var(--clr-pp-grey-300);
				overflow: hidden; transition: margin var(--li-trans);
			}
			main .results > li[open] {
				margin-top: 10px;
				border-width: 0; border-radius: 0.5rem;
				box-shadow: var(--li-shad);
				display: list-item !important;
			}
			main .results > li[open] + li:not([style="display: none;"]) {
				margin-top: 10px;
				border-top: var(--li-bdr) transparent;
			}
			main .results > li:not([open]):hover {
				border-top: var(--li-bdr) transparent; border-radius: 0.5rem;
				box-shadow: var(--li-shad);
			}
			main .results > li:not([open]):hover + li:not([style="display: none;"]) { border-top: var(--li-bdr) transparent; }
			main .results .accordian {
				padding: 5px 10px;
				height: 30px; line-height: 30px;
				cursor: pointer; transition: var(--time-tst-xfast);
			}
			main .results > li[open] .accordian:hover { background-color: #D7E4F7; }
			main .results .accordian .title { display: block; }
			main .results .extender { height: 0; }
			/* main .results .extender * { transition: padding-top var(--li-trans), padding-bottom var(--li-trans), border-width var(--li-trans); } */
			main .results > li[open] .extender { height: auto; }
			main .results .details {
				padding: 10px;
				border: var(--li-bdr) var(--clr-pp-grey-300); border-left: none; border-right: none; /* border-width: 0; */
				font-size: 0.85em;
			}
			main .results .details > * { margin: 0 0 10px; }
			main .results .details > *:last-child { margin: 0; }
			main .results .details .namelist {
				margin: -5px 0 15px; padding-left: 15px;
				white-space: nowrap;
			}
			main .results .details .namelist a[role="button"] {
				padding: 2.5px 7.5px;
				/* position: absolute; transform: translateY(-50%); */
				font-size: 12.5px; line-height: 20px;
				/* display: none; */
			}
			/* main .results li[open] .details .namelist a[role="button"] { display: inline-flex; } */
			main .results .details .namelist td:nth-child(n+3) { padding-left: 12.5px; }
			main .results .details [name$=":advisor"] {
				padding-right: 30px;
				list-style-type: disc;
			}
			main .results .action {
				padding: 10px;
				justify-content: flex-end;
			}
			main .browser > div.center:last-child { display: flex; }
			main .browser > div:last-child > span { color: var(--clr-bs-gray); }
			main .browser > div:last-child > div.message {
				width: 100%;
				color: var(--clr-pp-red-900);
			}
		</style>
        <!--link rel="stylesheet" href="/t/PBL/v2/tools/components.min.css" /-->
		<!--link rel="stylesheet" href="/resource/css/extend/all-PBL.css" /-->
        <!--link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" /-->
		<script type="text/javascript">
			$(document).ready(function() {
				// PBL.init();
				PBL.init();
			});
			top.USER = "<?=$_SESSION['auth']['user']?>";
			top.USER_ADMIN = !parseInt("<?=(has_perm("PBL") ? 0 : 1)?>");
			const objEncrypt = obj => {
				let queryHash = new URLSearchParams(obj);
				return btoa(queryHash.toString()).replaceAll("=", "").split("m").reverse().join("_").replaceAll("l", "-").split("M").reverse().join("/").replaceAll("0", ".");
			}, objDecrypt = obj => atob(obj.replaceAll(".", "0").split("/").reverse().join("M").replaceAll("-", "l").split("_").reverse().join("m"));
			const pUI = {
				filter: {
					toggle: function() {
						(function() {
							$("main .fform").toggle("blind");
							PBL.changeState("useFilter",
								!$('main .mform button[onClick*="filter.toggle"]').toggleClass("hollow").is(".hollow")
							);
						}()); return false;
					}, show: function() {
						$("main .fform").show();
						$('main .mform button[onClick*="filter.toggle"]').removeClass("hollow")
						PBL.changeState("useFilter", true);
					}, reset: function(close) {
						(function() {
							document.querySelector("main .fform").reset();
							history.replaceState(null, null, location.pathname+location.search);
							if (close) pUI.filter.toggle();
							pUI.filter.enableSearch();
						}()); return false;
					}, update: function() {
						var finder = $("main .oform [name=find]").val().trim().replaceAll("เเ", "แ");
						w3.filterHTML("main .browser .results", "li", finder);
					}, enableSearch: function() {
						$('main .mform button[onClick*="PBL.load"]').removeAttr("disabled");
					}
				}, viewFile: function(code) { // function(file, code) {
					// var link = "/t/PBL/v2/preview?file="+file+"&code="+code;
					var file = $('main .results .action [name="'+code+':file"]').val(),
						link = "/t/PBL/v2/preview?file="+file+"&code="+code;
					if (ppa.ctrling()) window.open(link);
					else app.ui.lightbox.open("mid", {title: code+": "+PBL.cv.workFile[Object.keys(PBL.cv.fileList).indexOf(file)], allowclose: true,
						html: '<iframe src="'+link+'" style="width:90vw;height:80vh;border:none">Loading...</iframe>'});
				}
			};
		</script>
		<script type="text/javascript" src="/t/PBL/v2/tools/PBL-teacher.min.js"></script>
		<script type="text/javascript" src="/t/PBL/v2/tools/group-list.min.js"></script>
		<script type="text/javascript" src="/resource/js/lib/w3.min.js"></script>
		<script type="text/javascript" src="/resource/js/lib/jquery-bez.min.js"></script>
	</head>
	<body>
		<?php require($dirPWroot."resource/hpe/header.php"); ?>
		<main shrink="<?php echo($_COOKIE['sui_open-nt'])??"false"; ?>">
			<div class="container">
				<h2><?=$header_desc?></h2>
				<form class="form mform inline">
					<div class="group">
						<span>ระดับชั้น</span>
						<select name="grade"></select>
					</div>
					<div class="group">
						<span>ห้อง</span>
						<select name="room"></select>
					</div>
					<button class="gray hollow" onClick="return pUI.filter.toggle()" data-title="Filter">
						<i class="material-icons">filter_list</i>
					</button>
					<button class="blue" onClick="return PBL.load()">ค้นหา</button>
					<?php if (has_perm("PBL")) echo '<button disabled class="green" onClick="PBL.()">ดาวน์โหลดรายชื่อ</button>'; ?>
				</form>
				<form class="form fform inline --message default" onSubmit="return false" style="display: none;">
					<div class="group">
						<span>สาขา</span>
						<select name="type">
							<option value=" "><?=($_COOKIE['set_lang']=="th"?"ทั้งหมด":"All")?></option>
							<?php foreach (str_split("ABCDEFGHIJKLM") as $et) echo '<option value="'.$et.'">'.pblcode2text($et)[$_COOKIE['set_lang']].'</option>'; ?>
						</select>
					</div>
					<div class="group">
						<span>ที่</span>
						<select name="search_type">
							<option value="code" data-regex="^[A-Za-z0-9]{1,6}$">รหัสโครงงาน</option>
							<option value="name" data-regex="^[ก-๛0-9A-Za-z ()[\]{}\-!@#$%.,/&*+_?|]{1,150}$">ชื่อโครงงาน</option>
							<option value="member" data-regex="^[1-9]\d{0,4}$">เลขประจำตัวสมาชิก</option>
							<option value="advisor" data-regex="^([a-z]{3,28}\.[a-z]{1,2}|[a-zA-Z]{3,30}\d{0,3})$" disabled>ครูที่ปรึกษา</option>
						</select>
						<select name="search_range">
							<option value="S">เริ่มต้นด้วย</option>
							<option value="E">ลงท้ายด้วย</option>
							<option value="C">มี</option>
						</select>
						<input type="text" name="search_key">
					</div>
					<div class="group wrap" data-title="Multiple selection">
						<span>สถานะงาน</span>
						<select name="files" multiple size="1"></select>
						<select name="status">
							<option value="sent">ส่งแล้ว</option>
							<option value="none">ยังไม่ส่ง</option>
						</select>
						<select name="work">
							<option value="all">ทั้งหมด</option>
							<option value="some">บางส่วน</option>
						</select>
					</div>
					<div class="group">
						<select name="advisor">
							<option value="A">ทุกคนเป็น</option>
							<option value="Y">ฉันเป็น</option>
							<option value="N">ฉันไม่เป็น</option>
						</select>
						<span>ที่ปรึกษาโครงงาน</span>
					</div>
					<div class="group">
						<span>จำนวนสมาชิก</span>
						<select name="mbr_comp">
							<option value=">=">&gt;=</option>
							<option value=">">&gt;</option>
							<option value="<=">&lt;=</option>
							<option value="<">&lt;</option>
							<option value="=">=</option>
							<option value="!=">≠</option>
						</select>
						<select name="mbr_amt"></select>
					</div>
					<div class="group">
						<span>เรียงตาม</span>
						<select name="sort">
							<option value="class">ชั้นเรียน</option>
							<option value="code">รหัสโครงงาน</option>
							<option value="name">ชื่อโครงงาน</option>
							<option value="time">อัพเดทล่าสุด</option>
						</select>
						<select name="order">
							<option value="asc">หน้าไปหลัง</option>
							<option value="desc">ย้อนกลับ</option>
						</select>
					</div>
					<div class="group">
						<button class="orange hollow" onClick="return pUI.filter.reset(false)" data-title="Reset" type="reset"><i class="material-icons">delete_sweep</i></button>
						<button class="red hollow" onClick="return pUI.filter.reset(true)" data-title="Clear" type="reset"><i class="material-icons">backspace</i></button>
					</div>
				</form>
				<form class="form oform" style="display: none;">
					<div class="group finder">
						<span><i class="material-icons">search</i></span>
						<input type="search" name="find" placeholder="Find..." onInput="pUI.filter.update()">
					</div>
				</form>
				<div class="browser" style="display: none;">
					<ul class="results"></ul>
					<div class="center"><input hidden /></div>
				</div>
			</div>
		</main>
		<?php require($dirPWroot."resource/hpe/material.php"); ?>
		<footer>
			<?php require($dirPWroot."resource/hpe/footer.php"); ?>
		</footer>
	</body>
</html>