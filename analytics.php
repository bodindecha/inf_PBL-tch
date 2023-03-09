<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
	require($dirPWroot."resource/hpe/init_ps.php");
	$header_title = "สรุปสถิติ";
	$header_desc = "ประมวลข้อมูลสถิติ";
	$home_menu = "is-pbl";

	$ds = $_GET["dataset"] ?? null;
	$year = $_SESSION["stif"]["t_year"];
	$files = array("Mindmap", "IS1-1", "IS1-2", "IS1-3", "รายงานบท-1", "รายงานบท-2", "รายงานบท-3", "รายงานบท-4", "รายงานบท-5", "เล่มรายงานรวม", "บทคัดย่อ", "Poster");
	$subCols = array("class","group_amount","yes_mindmap","no_mindmap","yes_IS1_1","no_IS1_1","yes_IS1_2","no_IS1_2","yes_IS1_3","no_IS1_3","yes_report_1","no_report_1","yes_report_2","no_report_2","yes_report_3","no_report_3","yes_report_4","no_report_4","yes_report_5","no_report_5","yes_full_report","no_full_report","yes_abstract","no_abstract","yes_poster","no_poster");
	$branches = str_split("ABCDEFGHIJKLM ");

	if (isset($_GET["export"])) require_once("api/export-data.php");
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($dirPWroot."resource/hpe/heading.php"); require($dirPWroot."resource/hpe/init_ss.php"); ?>
		<style type="text/css">
			main .data > * { margin: 0 0 10px; }
			main .data > *:last-child { margin: 0; }
			main .table .none { color: #800B0B; }
			main .table .done { color: #307611; }
			main .table .dim { color: #EFEFEF; }
			main .table tbody td { padding: .75px 2.5px; }
		</style>
		<script type="text/javascript">
			$(document).ready(function() {
				$('main .form [name="dataset"]').on("change", function() {
					$("main .form button").removeAttr("disabled");
				});
				if (location.search.length > 2) {
					if (location.search.substring(1).match(/dataset=[a-z\-]+/)) {
						let dataset = location.search.substring(1).split("&").filter(ed => ed.startsWith("dataset="))[0].split("=")[1];
						document.querySelector('main .form [name="dataset"] option[value="'+dataset+'"]').selected = true;
					}
				}
				if (typeof tblFx !== "undefined") tblFx();
			})
		</script>
	</head>
	<body>
		<?php require($dirPWroot."resource/hpe/header.php"); ?>
		<main shrink="<?php echo($_COOKIE['sui_open-nt'])??"false"; ?>" <?php if (($ds??"") == "list") echo 'class="rainbow-bg"'; ?>>
			<div class="container">
				<h2><?=$header_desc?></h2>
				<form class="form inline">
					<div class="group">
						<span>ชุดข้อมูล</span>
						<select name="dataset" required>
							<option value selected disabled>---กรุณาเลือก---</option>
							<option value="submission">การส่งงาน</option>
							<option value="branches">สาขาโครงงาน</option>
							<option value="list">อื่นๆ (ดาวน์โหลด)</option>
						</select>
					</div>
					<button class="blue" disabled>โหลดดู</button>
				</form>
				<div class="data">
				<?php if ($ds == "submission") { ?>
					<form class="form inline sh-form">
						<label class="group">
							<input type="checkbox" class="switch" onChange="toggleSubs(this)" checked />
							แสดงไฟล์ย่อยและใบงาน
						</label>
					</form>
					<div class="table"><table center><thead>
						<tr>
							<th rowspan="2">ชั้น</th>
							<th rowspan="2">จำนวน<br>กลุ่ม</th>
							<?php foreach ($files as $file) echo '<th colspan="2">'.$file.'</th>'; ?>
						</tr>
						<tr><?php for ($i = 0; $i < count($files); $i++) echo '<td>ส่ง</td><td>ยัง</td>'; ?></tr>
					</thead>
					<tbody name="gA"></tbody>
					<?php
						require($dirPWroot."resource/php/core/db_connect.php");
						for ($i = 1; $i <= 6; $i++) {
							$get = $db -> query("SELECT CONCAT(grade, '/', room) AS class,COUNT(code) AS group_amount, SUM(CASE WHEN fileStatus&1 THEN 1 ELSE 0 END) AS yes_mindmap, SUM(CASE WHEN fileStatus&1 THEN 0 ELSE 1 END) AS no_mindmap, SUM(CASE WHEN fileStatus&2 THEN 1 ELSE 0 END) AS yes_IS1_1, SUM(CASE WHEN fileStatus&2 THEN 0 ELSE 1 END) AS no_IS1_1, SUM(CASE WHEN fileStatus&4 THEN 1 ELSE 0 END) AS yes_IS1_2, SUM(CASE WHEN fileStatus&4 THEN 0 ELSE 1 END) AS no_IS1_2, SUM(CASE WHEN fileStatus&8 THEN 1 ELSE 0 END) AS yes_IS1_3, SUM(CASE WHEN fileStatus&8 THEN 0 ELSE 1 END) AS no_IS1_3, SUM(CASE WHEN fileStatus&16 THEN 1 ELSE 0 END) AS yes_report_1, SUM(CASE WHEN fileStatus&16 THEN 0 ELSE 1 END) AS no_report_1, SUM(CASE WHEN fileStatus&32 THEN 1 ELSE 0 END) AS yes_report_2, SUM(CASE WHEN fileStatus&32 THEN 0 ELSE 1 END) AS no_report_2, SUM(CASE WHEN fileStatus&64 THEN 1 ELSE 0 END) AS yes_report_3, SUM(CASE WHEN fileStatus&64 THEN 0 ELSE 1 END) AS no_report_3, SUM(CASE WHEN fileStatus&128 THEN 1 ELSE 0 END) AS yes_report_4, SUM(CASE WHEN fileStatus&128 THEN 0 ELSE 1 END) AS no_report_4, SUM(CASE WHEN fileStatus&256 THEN 1 ELSE 0 END) AS yes_report_5, SUM(CASE WHEN fileStatus&256 THEN 0 ELSE 1 END) AS no_report_5, SUM(CASE WHEN fileStatus&512 THEN 1 ELSE 0 END) AS yes_full_report, SUM(CASE WHEN fileStatus&512 THEN 0 ELSE 1 END) AS no_full_report, SUM(CASE WHEN fileStatus&1024 THEN 1 ELSE 0 END) AS yes_abstract, SUM(CASE WHEN fileStatus&1024 THEN 0 ELSE 1 END) AS no_abstract, SUM(CASE WHEN fileStatus&2048 THEN 1 ELSE 0 END) AS yes_poster, SUM(CASE WHEN fileStatus&2048 THEN 0 ELSE 1 END) AS no_poster FROM PBL_group WHERE NOT mbr1 IS NULL AND year=$year AND grade=$i GROUP BY grade,room ORDER BY grade,room LIMIT 99");
							if ($get && $get -> num_rows) {
								echo '<tbody name="g'.$i.'">';
								while ($read = $get -> fetch_assoc()) {
									echo '<tr>';
									foreach ($subCols as $col) echo '<td name="'.$col.'">'.$read[$col].'</td>';
									echo '</tr>';
								} echo '</tbody>';
							}
						}
						$db -> close();
					?>
					</table></div>
					<script type="text/javascript">
						function tblFx() {
							var sumTbl = $('main .table tbody[name="gA"]');
							for (let i = 1; i <= 6; i++) {
								var sumRow = $('<tr><td name="class">ม.'+i.toString()+'</td></tr>');
								for (let j = 2; j <= 26; j++) {
									var sumCol = 0, cellName = "";
									document.querySelectorAll('main .table tbody[name="g'+i.toString()+'"] td:nth-child('+j.toString()+')').forEach(ec => {
										sumCol += parseInt(ec.innerText);
										if (!cellName.length) cellName = ec.getAttribute("name");
									}); sumRow.append('<td name="'+cellName+'">'+sumCol.toString()+'</td>');
								} sumTbl.append(sumRow);
							}
							var sumRow = $('<tr><td name="class">รวม</td></tr>');
							for (let j = 2; j <= 26; j++) {
								var sumCol = 0, cellName = "";
								document.querySelectorAll('main .table tbody[name="gA"] td:nth-child('+j.toString()+')').forEach(ec => {
									sumCol += parseInt(ec.innerText);
									if (!cellName.length) cellName = ec.getAttribute("name");
								}); sumRow.append('<td name="'+cellName+'">'+sumCol.toString()+'</td>');
							} sumTbl.prepend(sumRow);
							document.querySelectorAll("main .table tbody td").forEach(ec => {
								if (ec.getAttribute("name").startsWith("yes_") && ec.innerText=="0")
									ec.classList.add("none");
								else if (ec.getAttribute("name").startsWith("no_") && ec.innerText=="0") {
									ec.classList.add("dim");
									$(ec).prev().addClass("done");
								}
							});
							$("main .table tbody:not(:nth-of-type(2n+1):nth-last-of-type(2n+3)) td:nth-of-type(n+5):nth-last-child(n+17)").attr("class", "dim");
							$("main .table tbody:first-of-type tr:not(:nth-child(2n-1):nth-last-child(2n+3)) td:nth-of-type(n+5):nth-last-child(n+17)").attr("class", "dim");
						}
						function toggleSubs(me) {
							var state = me.checked,
								target = $("main .table tbody td:nth-child(n+5):nth-last-child(n+7), main .table thead th:nth-child(n+4):nth-last-child(n+4), main .table thead td:nth-child(n+3):nth-last-child(n+7)");
							if (state) target.fadeIn();
							else target.fadeOut()
						}
					</script>
					<style type="text/css">
						main .sh-form { margin-bottom: 10px; }
						main tbody[name="gA"] tr:first-child { background-color: #D6E1F6; }
						main tbody[name="gA"] tr:nth-child(2n+3) { background-color: #E9F1FE; }
						main td:nth-child(2), main th:nth-child(2) { border-right: 2px solid var(--clr-bs-gray) !important; }
						main thead th:nth-child(3), main thead th:nth-child(6), main thead th:nth-child(11), main thead th:nth-child(13) { border-right: 1px solid var(--clr-bs-gray) !important; }
						main thead td:nth-child(2), main thead td:nth-child(8), main thead td:nth-child(18), main thead td:nth-child(22) { border-right: 1px solid var(--clr-bs-gray) !important; }
						main tbody td:nth-child(4), main tbody td:nth-child(10), main tbody td:nth-child(20), main tbody td:nth-child(24) { border-right: 1px solid var(--clr-bs-gray) !important; }
						main thead th:nth-child(4), main thead th:nth-child(5), main thead th:nth-child(7), main thead th:nth-child(8), main thead th:nth-child(9), main thead th:nth-child(10), main thead th:nth-child(12) { border-right: 1.25px dotted var(--clr-bs-gray) !important; }
						main thead td:nth-child(4), main thead td:nth-child(6), main thead td:nth-child(10), main thead td:nth-child(12), main thead td:nth-child(14), main thead td:nth-child(16), main thead td:nth-child(20) { border-right: 1.25px dotted var(--clr-bs-gray) !important; }
						main tbody td:nth-child(6), main tbody td:nth-child(8), main tbody td:nth-child(12), main tbody td:nth-child(14), main tbody td:nth-child(16), main tbody td:nth-child(18), main tbody td:nth-child(22) { border-right: 1.25px dotted var(--clr-bs-gray) !important; }
					</style>
				<?php } else if ($ds == "branches") { ?>
					<div class="table"><table center><thead><tr>
						<th>สาขาโครงงาน</th>
						<th>รวม</th>
						<?php for ($i = 1; $i <= 6; $i++) echo '<th>ม.'.$i.'</th>'; ?>
					</tr></thead>
					<tbody>
						<?php
							require_once($dirPWroot."resource/php/core/config.php");
							require($dirPWroot."resource/php/core/db_connect.php");
							$get = array(); $read = array();
							for ($i = 1; $i <= 6; $i++) {
								$get[$i] = $db -> query("SELECT type as branch,COUNT(code) AS amount FROM PBL_group WHERE NOT mbr1 IS NULL AND year=$year AND grade=$i GROUP BY type ORDER BY (CASE type WHEN '' THEN 1 ELSE 0 END),type");
								$read[$i] = $get[$i] -> num_rows;
								$get[$i] = $get[$i] -> fetch_all(MYSQLI_ASSOC);
							} $call = array_fill(1, 6, 0);
							foreach ($branches as $branch) {
								$branch = trim($branch);
								if (empty($branch)) echo '<tr><td>ยังไม่มีสาขา</td><td name="sum"></td>';
								else echo '<tr><td>'.pblcode2text($branch)["th"].'</td><td name="sum"></td>';
								for ($i = 1; $i <= 6; $i++) {
									if ($get[$i][$call[$i]]["branch"]==$branch) {
										echo '<td>'.($get[$i][$call[$i]]["amount"] ?? "0").'</td>';
										$call[$i] += 1;
									} else echo '<td>0</td>';
								} echo '</tr>';
							}
							$db -> close();
						?>
					</tbody></table></div>
					<div class="dl">
						<iframe name="dlframe" hidden></iframe>
						<a role="button" href="?dataset=branches&export=download" target="dlframe" class="green" data-title="แบบละเอียด"><i class="material-icons">download</i> ดาวน์โหลด</a>
					</div>
					<script type="text/javascript">
						function tblFx() {
							$('main .dl a[role="button"]').on("click", async function() {
								var btn = $(this);
								btn.attr("disabled", "");
								setTimeout(function() { btn.removeAttr("disabled"); }, 10000);
							});
							// Calculations
							for (let i = 1; i <= 14; i++) {
								var sumRow = 0;
								document.querySelectorAll('main .table tbody tr:nth-child('+i.toString()+') td:nth-child(n+3)').forEach(ec => {
									sumRow += parseInt(ec.innerText);
								}); document.querySelector('main .table tbody tr:nth-child('+i.toString()+') td:nth-child(2)').innerText = sumRow;
							}
							var sumRow = $('<tr><td>รวม</td></tr>');
							for (let i = 2; i <= 8; i++) {
								var sumCol = 0;
								document.querySelectorAll('main .table tbody td:nth-child('+i.toString()+')').forEach(ec => {
									sumCol += parseInt(ec.innerText);
								}); sumRow.append('<td>'+sumCol.toString()+'</td>');
							} $("main .table tbody").prepend(sumRow);
							document.querySelectorAll("main .table tbody tr:not(:last-child) td").forEach(ec => { if (ec.innerText=="0") ec.classList.add("dim"); });
							document.querySelectorAll("main .table tbody tr:last-child td:not(:first-child)").forEach(ec => { if (ec.innerText!="0") ec.classList.add("none"); });
						}
					</script>
					<style type="text/css">
						main .table thead th:nth-child(n+3) { font-weight: normal; }
						main .table tbody tr:nth-child(1) td:nth-child(1) { font-weight: bold; }
						main .table tbody td:nth-child(1) { text-align: left; }
						main .table tbody tr:nth-child(1) td:not(:nth-child(1)) { font-weight: bold; }
						main .table tbody td:nth-child(2) { font-weight: bold; }
						main div.dl { margin-top: 10px; }
						main div.dl a[role="button"] {
							margin-left: auto;
							width: fit-content;
						}
					</style>
				<?php } else if ($ds == "list") { ?>
					<?php if (!has_perm("PBL")) echo '<center class="message red">สามารถเข้าถึงได้เฉพาะหัวหน้างานพัฒนาการศึกษาโดยใช้โครงงานเป็นฐาน (IS/PBL) เท่านั้น</center>'; else { ?>
					<p>เนื่องจากข้อมูลมีปริมาณมาก จึงไม่สามารถนำแสดงได้</p>
					<center class="message yellow">ไฟล์ทั้งหมดเป็นประเภท Comma Separated Value (.csv) ดังนั้นจึงควรเปิดบนคอมพิวเตอร์หรือโน้ตบุ๊ค</center>
					<div class="dl table wrap"><table><thead><tr>
						<th>ชื่อรายการ</th><th>กระทำการ</th>
					</tr></thead><tbody>
						<!-- Data loads -->
					</tbody></table></div>
					<iframe name="dlframe" hidden></iframe>
					<script type="text/javascript">
						function tblFx() {
							const datalist = {
								"GROUP_TYPE 1":		"รายชื่อโครงงาน",
								"project-title":	[true, "รายชื่อโครงงานทั้งหมด"],
								"project-group":	[true, "ข้อมูลรายกลุ่มฉบับย่อ"],
								"missing-mindmap":	[true, "ที่ยังไม่ส่ง: แผนผังความคิด"],
								"missing-paper":	[true, "ที่ยังไม่ส่ง: เล่มรายงาน"],
								"missing-poster":	[true, "ที่ยังไม่ส่ง: โปสเตอร์"],
								"GROUP_TYPE 2":		"รายชื่อบุคคล",
								"group-member":		[true, "รายชื่อสมาชิกกลุ่ม"],
								"std-submission":	[true, "การส่งเล่มรายงานนักเรียน"],
								"student-score":	[true, "คะแนนแยกส่วนของนักเรียน"],
								"GROUP_TYPE 3":		"รายงานกรรมการ",
								"cmte-progress":	[true, "ความคืบหน้าการให้คะแนนเล่มรายงาน"],
								"cmte-score":		[true, "การให้คะแนนแต่ละโครงงานของกรรมการ"],
								"GROUP_TYPE 4":		"ผลคะแนนและรางวัล (รายโครงงาน)",
								"project-score":	[true, "คะแนนเฉลี่ย (เต็ม 100)"],
								"project-result":	[true, "ผล (ผ/มผ) และคะแนน (เต็ม 3)"],
								"GROUP_TYPE 5":		"สรุปปลายปีการศึกษา",
								"present-list":		[false, "นักเรียนที่ขึ้นนำเสนอบนหอประชุม"],
								"cert-student":		[true, "เกียรติบัตรนักเรียน"],
								"cert-teacher":		[true, "เกียรติบัตรครู"],
							}; var table = "";
							Object.keys(datalist).forEach(keyname => {
								if (keyname.startsWith("GROUP_TYPE")) table += '<tr><td colspan="2" class="center">'+datalist[keyname]+'</td></tr>';
								else if (keyname.length) table += '<tr id="name='+keyname+'"><td>'+datalist[keyname][1]+'</td><td><a role="button" href="?dataset='+keyname+'&export=download" target="dlframe" class="green icon hollow small" draggable="false" '+(datalist[keyname][0]?"":"disabled")+'><i class="material-icons">download</i>ดาวน์โหลด</a></td></tr>'
							}); $("main .dl tbody").append(table);
							$('main .dl a[role="button"]').on("mouseenter touchstart", function() {
								$(this).not(".loading").removeClass("hollow");
							}).on("mouseleave touchend", function() {
								$(this).not(".loading").addClass("hollow");
							}).on("click", async function() {
								var btn = $(this);
								btn.addClass("orange loading").attr("disabled", "").removeClass("green hollow");
								setTimeout(function() {
									btn.addClass("green hollow").removeClass("orange loading").removeAttr("disabled");
								}, 10000);
							});
							if (location.hash.length>1) {
								$('main .dl [id="'+location.hash.substring(1)+'"]').addClass("target");
								app.io.URL.removeHash("name=");
							}
						}
					</script>
					<style type="text/css">
						main .dl th:nth-child(1) { padding-left: 5px; text-align: left; }
						main .dl tr > td:nth-child(1) { padding-left: 5px; }
						main .dl tr > *:nth-child(2) { min-width: fit-content; width: 20%; }
						main .dl tr > td[colspan] { padding: 3.75px 2.5px; }
						main .dl a[role="button"] {
							margin: 2.5px auto;
							width: fit-content;
						}
						main .target { background-image: linear-gradient(to right, rgba(106, 238, 193, 0.375), rgba(175, 99, 224, 0.25), rgba(106, 238, 193, 0.375)); }
					</style>
				<?php } } ?>
				</div>
			</div>
		</main>
		<?php require($dirPWroot."resource/hpe/material.php"); ?>
		<footer>
			<?php require($dirPWroot."resource/hpe/footer.php"); ?>
		</footer>
	</body>
</html>