<?php
	if (!isset($dirPWroot)) $dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/") - 1);
	if ($_SERVER["REDIRECT_URL"] == "/t/PBL/v2/api/export-data") {
		header("Location: /error/914");
		exit(0);
	}

	require_once($dirPWroot."resource/php/core/config.php");
	require($dirPWroot."resource/php/core/db_connect.php");
	function reward_code2text($code) {
		switch ($code) {
			case "1G": $text = "ทอง"; break;
			case "2S": $text = "เงิน"; break;
			case "3B": $text = "ทองแดง"; break;
			case "4M": $text = "ชมเชย"; break;
			case "5N": $text = "เข้าร่วม"; break;
			case "0P": $text = "ผ่านเกณฑ์"; break;
			default: $text = "-"; break;
		} return $text;
	}
	$reqType = "csv"; $delimeter = ($reqType == "tsv" ? "\t" : ",");
	$dltime = date("Y-m-d H_i_s", time());
	if (!has_perm("PBL") && $ds <> "branches") $errorMsg = array(2, "You don't have permission to download this dataset.");
	else switch ($ds) {
		case "branches": {
			$name = "สาขาโครงงาน";
			$result = $db -> query("SELECT grade,room,type,COUNT(code) AS amount,GROUP_CONCAT((CASE a.nameth WHEN '' THEN (CASE a.nameen WHEN '' THEN code ELSE '' END)) AS names FROM PBL_group WHERE year=$year AND mbr1 IS NOT NULL GROUP BY grade,room,type ORDER BY grade,room,(CASE type WHEN '' THEN 1 ELSE 0 END),type");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"สาขาโครงงาน\"$delimeter\"จำนวนโครงงาน\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Modify
				if (empty($er["type"])) $brance = "ยังไม่มีสาขา";
				else $branch = pblcode2text($er["type"])["th"];
				// Concat
				$outputData .= "\n\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$branch."\"$delimeter\"".$er["amount"]."\"";
			}
		} break;
		case "project-title": {
			$name = "รายชื่อโครงงาน";
			$result = $db -> query("SELECT grade,room,code,(CASE nameth WHEN '' THEN (CASE nameen WHEN '' THEN '~ไม่มีชื่อโครงงาน~' ELSE '' END) ELSE nameth END) AS name,type FROM PBL_group WHERE year=$year AND mbr1 IS NOT NULL ORDER BY grade,room,name");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"รหัสโครงงาน\"$delimeter\"หัวข้อโครงงาน\"$delimeter\"สาขาโครงงาน\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Modify
				if ($er["name"] == "") $er["name"] = "~ไม่มีชื่อโครงงาน~";
				// Concat
				$outputData .= "\n\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$er["code"]."\"$delimeter\"".$er["name"]."\"$delimeter\"".pblcode2text($er["type"])["th"]."\"";
			}
		} break;
		case "project-group": {
			$name = "ข้อมูลย่อกลุ่ม";
			$result = $db -> query("SELECT a.grade,a.room,a.code,b.namep,CONCAT(b.namefth, '  ', b.namelth) AS nameath,a.type,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS proj_name,a.reward FROM PBL_group a INNER JOIN user_s b ON a.mbr1=b.stdid WHERE a.year=$year AND a.mbr1 IS NOT NULL ORDER BY a.grade,a.room,a.code");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"รหัสโครงงาน\"$delimeter\"หัวหน้ากลุ่ม\"$delimeter\"สาขาโครงงาน\"$delimeter\"หัวข้อโครงงาน\"$delimeter\"ระดับรางวัล\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Concat
				$outputData .= "\n\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$er["code"]."\"$delimeter\"".prefixcode2text($er["namep"])["th"].$er["nameath"]."\"$delimeter\"".pblcode2text($er["type"])["th"]."\"$delimeter\"".$er["proj_name"]."\"$delimeter\"".reward_code2text($er["reward"])."\"";
			}
		} break;
		case "missing-mindmap": case "missing-paper": case "missing-poster": {
			switch ($ds) {
				case "missing-mindmap": $filePos = 1; $name = "กลุ่มที่ไม่ส่งแผนผังความคิด"; break;
				case "missing-paper":   $filePos = 512; $name = "กลุ่มที่ไม่ส่งเล่มรายงาน"; break;
				case "missing-poster":  $filePos = 2048; $name = "กลุ่มที่ไม่ส่งโปสเตอร์"; break;
			}
			$result = $db -> query("SELECT code,CONCAT(grade, '/', room) AS class,COALESCE(nameth, nameen) AS proj_name,mbr1,COALESCE(mbr2, '') AS mbr2,COALESCE(mbr3, '') AS mbr3,COALESCE(mbr4, '') AS mbr4,COALESCE(mbr5, '') AS mbr5,COALESCE(mbr6, '') AS mbr6,COALESCE(mbr7, '') AS mbr7,COALESCE(adv1, '') AS adv1,COALESCE(adv2, '') AS adv2,COALESCE(adv3, '') AS adv3 FROM `PBL_group` WHERE fileStatus&$filePos=0 AND year=$year AND NOT mbr1 IS NULL ORDER BY grade,room,code");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"รหัสโครงงาน\"$delimeter\"ชั้น/ห้อง\"$delimeter\"หัวข้อโครงงาน\"$delimeter\"หัวหน้ากลุ่ม\"$delimeter\"สมาชิกกลุ่ม\"$delimeter\"สมาชิกกลุ่ม\"$delimeter\"สมาชิกกลุ่ม\"$delimeter\"สมาชิกกลุ่ม\"$delimeter\"สมาชิกกลุ่ม\"$delimeter\"สมาชิกกลุ่ม\"$delimeter\"ครูที่ปรึกษาโครงงาน 1\"$delimeter\"ครูที่ปรึกษาโครงงาน 2\"$delimeter\"ครูที่ปรึกษาโครงงาน 3\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Concat
				$outputData .= "\n\"".$er["code"]."\"$delimeter\"".$er["class"]."\"$delimeter\"".$er["proj_name"]."\"$delimeter\"".$er["mbr1"]."\"$delimeter\"".$er["mbr2"]."\"$delimeter\"".$er["mbr3"]."\"$delimeter\"".$er["mbr4"]."\"$delimeter\"".$er["mbr5"]."\"$delimeter\"".$er["mbr6"]."\"$delimeter\"".$er["mbr7"]."\"$delimeter\"".$er["adv1"]."\"$delimeter\"".$er["adv2"]."\"$delimeter\"".$er["adv3"]."\"";
			}
		} break;
		case "group-member": {
			$name = "รายชื่อสมาชิกกลุ่ม";
			$result = $db -> query("SELECT 6-a.gen-2514+$year AS grade,a.room,a.stdid,a.namep,a.namefth,a.namelth,a.number,a.remark,COALESCE(b.code, '') AS code,b.type,(CASE b.nameth WHEN '' THEN b.nameen ELSE b.nameth END) AS proj_name,b.reward FROM user_s a LEFT JOIN PBL_group b ON a.stdid IN(b.mbr1, b.mbr2, b.mbr3, b.mbr4, b.mbr5, b.mbr6, b.mbr7) AND b.year=$year WHERE a.gen BETWEEN $year-2514 AND $year-2514+5 AND a.room<=17 AND a.number<=90 ORDER BY a.gen DESC,a.room,b.code,(CASE b.mbr1 WHEN a.stdid THEN 1 ELSE 2 END),a.number");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"รหัสนร.\"$delimeter\"คำนำ\"$delimeter\"ชื่อจริง\"$delimeter\"นามสกุล\"$delimeter\"เลขที่\"$delimeter\"หมายเหตุ\"$delimeter\"รหัสโครงงาน\"$delimeter\"สาขาโครงงาน\"$delimeter\"หัวข้อโครงงาน\"$delimeter\"ระดับรางวัล\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Concat
				$outputData .= "\n\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$er["stdid"]."\"$delimeter\"".prefixcode2text($er["namep"])["th"]."\"$delimeter\"".$er["namefth"]."\"$delimeter\"".$er["namelth"]."\"$delimeter\"".$er["number"]."\"$delimeter\"".$er["remark"]."\"$delimeter\"".$er["code"]."\"$delimeter\"".pblcode2text($er["type"])["th"]."\"$delimeter\"".$er["proj_name"]."\"$delimeter\"".reward_code2text($er["reward"])."\"";
			}
		} break;
		case "std-submission": {
			$name = "การส่งเล่มรายงาน";
			$result = $db -> query("SELECT b.grade,a.room,a.stdid,a.namep,a.namefth,a.namelth,a.number,a.remark,COALESCE(b.code, '') AS code,(CASE b.fileStatus&512 WHEN 512 THEN 'ส่งเล่มรายงาน' ELSE 'ไม่ส่ง' END) AS submit FROM user_s a LEFT JOIN PBL_group b ON a.stdid IN(b.mbr1, b.mbr2, b.mbr3, b.mbr4, b.mbr5, b.mbr6, b.mbr7) AND b.year=$year WHERE a.gen BETWEEN 51 AND 56 AND a.room<=17 AND a.number<=90 ORDER BY a.gen DESC,a.room,b.code,(CASE b.mbr1 WHEN a.stdid THEN 1 ELSE 2 END),a.number");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"รหัสนร.\"$delimeter\"คำนำ\"$delimeter\"ชื่อจริง\"$delimeter\"นามสกุล\"$delimeter\"เลขที่\"$delimeter\"หมายเหตุ\"$delimeter\"รหัสโครงงาน\"$delimeter\"การส่งเล่มรายงาน\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Concat
				$outputData .= "\n\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$er["stdid"]."\"$delimeter\"".prefixcode2text($er["namep"])["th"]."\"$delimeter\"".$er["namefth"]."\"$delimeter\"".$er["namelth"]."\"$delimeter\"".$er["number"]."\"$delimeter\"".$er["remark"]."\"$delimeter\"".$er["code"]."\"$delimeter\"".$er["submit"]."\"";
			}
		} break;
		case "student-score": {
			$name = "คะแนนนักเรียนแยกส่วน";
			$result = $db -> query("SELECT a.stdid,6-a.gen-2514+$year AS grade,a.room,a.number,a.namep,CONCAT(a.namefth, '  ', a.namelth) AS nameth,a.remark,(CASE WHEN b.reward IS NULL THEN 0 WHEN b.reward='5N' OR ROUND(SUM(c.total)/COUNT(c.cmte)) IS NULL THEN 2 WHEN ROUND(SUM(c.total)/COUNT(c.cmte))<50 THEN 2 ELSE 3 END) AS score_paper,COALESCE(b.score_poster, 0) AS score_poster,COALESCE(d.score, 0) AS score_ophact,COALESCE(b.code, '') AS code FROM user_s a LEFT JOIN PBL_group b ON a.stdid IN(b.mbr1, b.mbr2, b.mbr3, b.mbr4, b.mbr5, b.mbr6, b.mbr7) AND b.year=$year LEFT JOIN PBL_score c ON c.code=b.code LEFT JOIN user_score d ON d.stdid=a.stdid AND d.year=$year AND d.subj='PBL' AND d.field='oph-act' WHERE a.gen BETWEEN $year-2514 AND $year-2514+5 AND a.room<=17 AND a.number<=90 GROUP BY a.stdid ORDER BY grade,a.room,a.number");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"รหัสนร.\"$delimeter\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"เลขที่\"$delimeter\"ชื่อ-สกุล\"$delimeter\"หมายเหตุ\"$delimeter\"คะแนน: เล่มรายงาน\"$delimeter\"คะแนน: โปสเตอร์\"$delimeter\"คะแนน: เข้าร่วมกิจกรรม\"$delimeter\"คะแนนรวม\"$delimeter\"รหัสโครงงาน\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Modify
				$totalScore = intval($er["score_paper"]) + intval($er["score_poster"]) + intval($er["score_ophact"]);
				// Concat
				$outputData .= "\n\"".$er["stdid"]."\"$delimeter\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$er["number"]."\"$delimeter\"".prefixcode2text($er["namep"])["th"].$er["nameth"]."\"$delimeter\"".$er["remark"]."\"$delimeter\"".$er["score_paper"]."\"$delimeter\"".$er["score_poster"]."\"$delimeter\"".$er["score_ophact"]."\"$delimeter\"".$totalScore."\"$delimeter\"".$er["code"]."\"";
			}
		} break;
		case "cmte-progress": {
			$name = "ความคืบหน้าคะแนนเล่มรายงาน";
			$result = $db -> query("SELECT c.subj,a.tchr,CONCAT('ครู', c.namefth, '  ', c.namelth) AS nameth,(CASE c.namenth WHEN '' THEN '' ELSE CONCAT('ครู', c.namenth) END) AS namen,(SELECT COUNT(e.code) FROM PBL_group e WHERE a.cmteid IN(e.mrker1, e.mrker2, e.mrker3, e.mrker4, e.mrker5) AND e.year=$year) AS assigned,COALESCE((SELECT GROUP_CONCAT(e.code) FROM PBL_group e WHERE a.cmteid IN(e.mrker1, e.mrker2, e.mrker3, e.mrker4, e.mrker5) AND e.year=$year), '') AS asgn_proj,(SELECT COUNT(b.code) FROM PBL_score b INNER JOIN PBL_group d ON b.code=d.code WHERE a.cmteid=b.cmte AND d.year=$year) AS marked,COALESCE((SELECT GROUP_CONCAT(b.code) FROM PBL_score b INNER JOIN PBL_group d ON b.code=d.code WHERE a.cmteid=b.cmte AND d.year=$year), '') AS projects FROM PBL_cmte a INNER JOIN user_t c ON a.tchr=c.namecode GROUP BY a.tchr ORDER BY c.subj,nameth");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"กลุ่มสาระ/วิชา\"$delimeter\"ชื่อ-สกุล\"$delimeter\"ชื่อเล่น\"$delimeter\"มอบหมาย\"$delimeter\"โครงงานที่ได้รับ\"$delimeter\"ตรวจไปแล้ว\"$delimeter\"โครงงานที่ตรวจ\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Modify
				$er["asgn_proj"] = str_replace(",", ", ", $er["asgn_proj"]);
				$er["projects"] = str_replace(",", ", ", $er["projects"]);
				// Concat
				$outputData .= "\n\"".subjcode2name($er["subj"])["th"]."\"$delimeter\"".$er["nameth"]."\"$delimeter\"".$er["namen"]."\"$delimeter\"".$er["assigned"]."\"$delimeter\"".$er["asgn_proj"]."\"$delimeter\"".$er["marked"]."\"$delimeter\"".$er["projects"]."\"";
			}
		} break;
		case "cmte-score": {
			$name = "คะแนนโครงงานรายกรรมการ";
			$result = $db -> query("SELECT d.type,d.grade,d.code,COALESCE(d.nameth, d.nameen) AS proj_name,b.tchr,CONCAT('ครู', c.namefth, '  ', c.namelth) AS nameth,(CASE c.namenth WHEN '' THEN '' ELSE CONCAT('ครู', c.namenth) END) AS namen,a.raw,a.total,a.note,CAST(a.time AS VARCHAR(19)) AS time FROM PBL_score a INNER JOIN PBL_cmte b ON a.cmte=b.cmteid INNER JOIN user_t c ON b.tchr=c.namecode INNER JOIN PBL_group d ON a.code=d.code WHERE d.year=$year ORDER BY d.type,d.grade,d.code");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"สาขาโครงงาน\"$delimeter\"ระดับชั้น\"$delimeter\"รหัสโครงงาน\"$delimeter\"หัวข้อโครงงาน\"$delimeter\"ชื่อ-สกุล\"$delimeter\"ชื่อเล่น\"$delimeter\"คะแนนแยกช่อง\"$delimeter\"คะแนนรวมคิดเป็น\"$delimeter\"บันทึกช่วยจำ\"$delimeter\"ประทับเวลา\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Modify
				$er["note"] = str_replace("\n", "\\n", $er["note"]);
				// Concat
				$outputData .= "\n\"".pblcode2text($er["type"])["th"]."\"$delimeter\"".$er["grade"]."\"$delimeter\"".$er["code"]."\"$delimeter\"".$er["proj_name"]."\"$delimeter\"".$er["nameth"]."\"$delimeter\"".$er["namen"]."\"$delimeter\"".$er["raw"]."\"$delimeter\"".$er["total"]."\"$delimeter\"".$er["note"]."\"$delimeter\"".$er["time"]."\"";
			}
		} break;
		case "project-score": {
			$name = "คะแนนเฉลี่ยกรรมการ";
			$result = $db -> query("SELECT a.type,a.grade,a.code,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS proj_name,ROUND(SUM(b.total)/COUNT(b.cmte)) AS total,(CASE WHEN ROUND(SUM(b.total)/COUNT(b.cmte))<50 THEN 2 ELSE 3 END) AS score, COUNT(b.cmte) AS cmte_amt,GROUP_CONCAT(CONCAT('ครู', d.namefth, ' ', d.namelth)) AS cmte_list FROM PBL_group a INNER JOIN PBL_score b ON a.code=b.code INNER JOIN PBL_cmte c ON b.cmte=c.cmteid AND a.type=c.type AND c.year=$year INNER JOIN user_t d ON c.tchr=d.namecode WHERE a.year=$year GROUP BY a.code ORDER BY type,grade,code");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"สาขาโครงงาน\"$delimeter\"ระดับชั้น\"$delimeter\"รหัสโครงงาน\"$delimeter\"หัวข้อโครงงาน\"$delimeter\"คะแนนเฉลี่ย\"$delimeter\"คิดเป็น\"$delimeter\"จำนวนกรรมการ\"$delimeter\"รายชื่อกรรมการ\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Modify
				$er["cmte_list"] = str_replace(",", ", ", $er["cmte_list"]);
				// Concat
				$outputData .= "\n\"".pblcode2text($er["type"])["th"]."\"$delimeter\"".$er["grade"]."\"$delimeter\"".$er["code"]."\"$delimeter\"".$er["proj_name"]."\"$delimeter\"".$er["total"]."\"$delimeter\"".$er["score"]."\"$delimeter\"".$er["cmte_amt"]."\"$delimeter\"".$er["cmte_list"]."\"";
			}
		} break;
		case "project-result": {
			$name = "ผลและคะแนนเล่มรายงาน";
			$result = $db -> query("SELECT a.grade,a.room,a.code,(CASE a.nameth WHEN '' THEN a.nameen ELSE a.nameth END) AS name,a.type,(CASE WHEN a.reward='5N' THEN 'ไม่ผ่าน' WHEN a.reward IS NULL THEN '-' ELSE 'ผ่าน' END) AS evalG,COALESCE(ROUND(SUM(b.total)/COUNT(b.cmte)), '') AS evalM,(CASE WHEN a.reward IS NULL THEN 0 WHEN a.reward='5N' THEN 2 WHEN ROUND(SUM(b.total)/COUNT(b.cmte)) IS NULL THEN '' WHEN ROUND(SUM(b.total)/COUNT(b.cmte))<50 THEN 2 ELSE 3 END) AS score FROM PBL_group a LEFT JOIN PBL_score b ON a.code=b.code WHERE a.year=$year AND a.mbr1 IS NOT NULL GROUP BY a.code ORDER BY a.grade,a.room,a.code");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"รหัสโครงงาน\"$delimeter\"หัวข้อโครงงาน\"$delimeter\"สาขาโครงงาน\"$delimeter\"ผลประเมิน\"$delimeter\"คะแนนเฉลี่ย\"$delimeter\"คิดเป็น\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Concat
				$outputData .= "\n\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$er["code"]."\"$delimeter\"".$er["name"]."\"$delimeter\"".pblcode2text($er["type"])["th"]."\"$delimeter\"".$er["evalG"]."\"$delimeter\"".$er["evalM"]."\"$delimeter\"".$er["score"]."\"";
			}
		} break;
		case "present-list": {
			$name = "รายชื่อนักเรียนขึ้นหอประชุม";
			$result = $db -> query(""); // -- เฉพาะเหรียญเงิน up
			$has_result = ($result && $result->num_rows);
			$outputData = "\"___\"$delimeter\"___\"$delimeter\"___\"$delimeter\"___\"$delimeter\"___\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Modify
				if ($er["___"] == "") $er["___"] = "___";
				// Concat
				$outputData .= "\n\"".$er["___"]."\"$delimeter\"".$er["___"]."\"$delimeter\"".$er["___"]."\"$delimeter\"".$er["___"]."\"$delimeter\"".pblcode2text($er["type"])["th"]."\"";
			}
		} break;
		case "cert-student": {
			$name = "เกียรติบัตรนักเรียน";
			$result = $db -> query("SELECT a.stdid,6-a.gen-2514+$year AS grade,a.room,a.number,a.namep,CONCAT(a.namefth, '  ', a.namelth) AS nameth,a.remark,b.reward,COALESCE(b.code, '') AS code,COALESCE(b.type, '') AS type FROM user_s a LEFT JOIN PBL_group b ON a.stdid IN(b.mbr1, b.mbr2, b.mbr3, b.mbr4, b.mbr5, b.mbr6, b.mbr7) AND b.year=$year WHERE a.gen BETWEEN $year-2514 AND $year-2514+5 AND a.room<=17 AND a.number<=90 ORDER BY grade,a.room,a.number");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"รหัสนร.\"$delimeter\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"เลขที่\"$delimeter\"ชื่อ-สกุล\"$delimeter\"หมายเหตุ\"$delimeter\"ระดับรางวัล\"$delimeter\"รหัสโครงงาน\"$delimeter\"สาขาโครงงาน\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Modify
				$er["namep"] = prefixcode2text($er["namep"])["th"];
				if ($er["namep"] == "ด.ช.") $er["namep"] = "เด็กชาย";
				else if ($er["namep"] == "ด.ญ.") $er["namep"] = "เด็กหญิง";
				else if ($er["namep"] == "น.ส.") $er["namep"] = "นางสาว";
				// Concat
				$outputData .= "\n\"".$er["stdid"]."\"$delimeter\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$er["number"]."\"$delimeter\"".$er["namep"].$er["nameth"]."\"$delimeter\"".$er["remark"]."\"$delimeter\"".reward_code2text($er["reward"])."\"$delimeter\"".$er["code"]."\"$delimeter\"".pblcode2text($er["type"])["th"]."\"";
			}
		} break;
		case "cert-teacher": {
			$name = "เกียรติบัตรครู";
			$result = $db -> query("(SELECT a.grade,a.room,b.namep,CONCAT(b.namefth, '  ', b.namelth) AS nameth,b.subj,MIN(CASE c.reward WHEN '0P' THEN '5X' WHEN '5N' THEN '6Y' WHEN NULL THEN '7Z' ELSE c.reward END) AS reward,1 AS roi FROM dat_homeroom a INNER JOIN user_t b ON a.tchr1=b.namecode LEFT JOIN PBL_group c ON (b.namecode IN(c.adv1,c.adv2,c.adv3) OR (a.grade=c.grade AND a.room=c.room)) AND c.year=$year WHERE a.year=$year AND a.sem=2 GROUP BY b.namecode) UNION (SELECT a.grade,a.room,b.namep,CONCAT(b.namefth, '  ', b.namelth) AS nameth,b.subj,MIN(CASE c.reward WHEN '0P' THEN '5X' WHEN '5N' THEN '6Y' WHEN NULL THEN '7Z' ELSE c.reward END) AS reward,2 AS roi FROM dat_homeroom a INNER JOIN user_t b ON a.tchr2=b.namecode LEFT JOIN PBL_group c ON (b.namecode IN(c.adv1,c.adv2,c.adv3) OR (a.grade=c.grade AND a.room=c.room)) AND c.year=$year WHERE a.year=$year AND a.sem=2 GROUP BY b.namecode) ORDER BY grade,room,roi");
			$has_result = ($result && $result->num_rows);
			$outputData = "\"ระดับชั้น\"$delimeter\"ห้อง\"$delimeter\"คำนำ\"$delimeter\"ชื่อ-สกุล\"$delimeter\"กลุ่มสาระ/วิชา\"$delimeter\"ระดับรางวัล\"";
			if ($has_result) while ($er = $result->fetch_assoc()) {
				// Modify
				$er["namep"] = prefixcode2text($er["namep"])["th"];
				if ($er["namep"] == "น.ส.") $er["namep"] = "นางสาว";
				// Concat
				$outputData .= "\n\"".$er["grade"]."\"$delimeter\"".$er["room"]."\"$delimeter\"".$er["namep"]."\"$delimeter\"".$er["nameth"]."\"$delimeter\"".subjcode2name($er["subj"])["th"]."\"$delimeter\"".reward_code2text($er["reward"])."\"";
			}
		} break;
		default:
			$errorMsg = array(1, "Invalid dataset.");
	}
	if (!isset($errorMsg) && $result) {
		$name = "PBL $name $dltime.$reqType";
		switch ($reqType) {
			case "csv":
				$mime = "text/csv";
				break;
			case "tsv":
				$mime = "text/tsv";
				break;
			case "json":
				$mime = "application/json";
				break;
		}
		if ($reqType == "json") $outputData = json_encode($outputData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		// --- Start Force Download ---
		if (ob_get_contents()) {
			die("Some data has already been output, can't export data file");
		}
		header("Content-Description: File Transfer");
		if (headers_sent()) {
			die("Some data has already been output to browser, can't export data file");
		}
		header("Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1");
		# header("Cache-Control: public, must-revalidate, max-age=0"); // HTTP/1.1
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		// force download dialog
		if (strpos(php_sapi_name(), "cgi") === false) {
			# header("Content-Type: $mime", true);
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream", false);
			header("Content-Type: application/download", false);
			header("Content-Type: $mime", false);
			header("Content-Length: ".strlen(strval($outputData)));
		} else header("Content-Type: $mime");
		// use the Content-Disposition header to supply a recommended filename
		header("Content-Disposition: attachment; filename=\"".basename($name)."\"");
		header("Content-Transfer-Encoding: binary");
		# TCPDF_STATIC::sendOutputData($this->getBuffer(), $this->bufferlen);
		echo strval($outputData);
		// --- End Force Download ---
		slog("PBL", "download", "report", "$ds.$reqType", "pass");
	} else {
		if (!isset($errorMsg) && !$result) $errorMsg = array(1, "Your data is not ready for download.");
?>
	<script type="text/javascript">
		top.app.ui.notify(1, [<?= $errorMsg[0].", \"".$errorMsg[1]."\"" ?>]);
	</script>
<?php
	}
	exit(0);
?>