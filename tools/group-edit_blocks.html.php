<?php
	$dirPWroot = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/") - 1);
	require_once($dirPWroot . "resource/php/core/config.php");

	if (!isset($APP_CONST)) {
		if (!isset($APP_RootDir)) $APP_RootDir = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/"));
		require_once($APP_RootDir."private/config/constant.php");
	}
?>
<!--  -->
<!--block-obj class="tab-selector" tab-type="g">
	<div class="tabs">
		<div class="tab" data-page="___">
			<div class="face" data-name="___">
				<i class="material-icons">___</i>
				<span>Create</span>
			</div>
			<div class="pop-label">
				<span>___</span>
			</div>
		</div>
	</div>
</block-obj-->
<!--  -->
<section class="pages" page-type="ng">
	<div class="page" path="open">
		<form class="form wrapper message rainbow-bg" data-clarity-unmask="true">
			<div class="group spread">
				<input name="gjc" type="text" maxlength="6" size="6" pattern="[A-Za-z0-9]{6}" placeholder="Enter group code here" required>
				<button onClick="return PBL.openGroup()" class="cyan">Load</button>
			</div>
		</form>
	</div>
	<div class="page" path="create">
		<div class="message gray wrapper">
			<form class="form" data-clarity-unmask="true">
				<p>สามารถกรอก/แก้ไขข้อมูลด้านล่างภายหลังได้</p>
				<table class="group-info">
					<tbody>
						<tr>
							<td>ชื่อโครงงานภาษาไทย</td>
							<td><input name="nameth" type="text" maxlength="<?=$APP_CONST["PBL"]["lenLim"]["nameth"]?>" pattern="<?=$APP_CONST["PBL"]["REGEX"]["projNameTH"]?>"></td>
						</tr>
						<tr>
							<td>ชื่อโครงงานภาษาอังกฤษ</td>
							<td><input name="nameen" type="text" maxlength="<?=$APP_CONST["PBL"]["lenLim"]["nameen"]?>" pattern="<?=$APP_CONST["PBL"]["REGEX"]["projNameEN"]?>"></td>
						</tr>
						<tr>
							<td>หัวหน้ากลุ่ม<font style="color: var(--clr-bs-red);" data-title="จำเป็น">*</font>
							</td>
							<td><input name="mbr1" type="hidden"><input type="text" readonly onFocus="pUI.select.leader(1)"></td>
						</tr>
						<tr>
							<td>ครูที่ปรึกษา 1</td>
							<td><input name="adv1" type="hidden"><input type="text" readonly onFocus="pUI.select.advisor(1)"></td>
						</tr>
						<tr>
							<td>ครูที่ปรึกษา 2</td>
							<td><input name="adv2" type="hidden"><input type="text" readonly onFocus="pUI.select.advisor(2)"></td>
						</tr>
						<tr>
							<td>ครูที่ปรึกษา 3</td>
							<td><input name="adv3" type="hidden"><input type="text" readonly onFocus="pUI.select.advisor(3)"></td>
						</tr>
						<tr>
							<td>สาขาโครงงาน</td>
							<td><select name="type">
									<?php foreach (str_split(" ABCDEFGHIJKLM") as $et) echo '<option value="' . $et . '">' . pblcode2text($et)[$_COOKIE["set_lang"]] . '</option>'; ?>
								</select></td>
						</tr>
					</tbody>
				</table>
				<div class="group split" style="gap: 10px;">
					<button onClick="return PBL.createGroup(false)" class="red hollow full-x" type="reset">Restart</button>
					<button onClick="return PBL.createGroup(true)" class="blue full-x" type="submit" style="min-width: 60%">Create</button>
				</div>
			</form>
		</div>
	</div>
</section>
<section class="pages" page-type="hg">
	<div class="page" path="information">
		<div class="--message gray">
			<form class="form" onChange="pUI.form.btnState()" data-clarity-unmask="true">
				<table class="group-info">
					<tbody>
						<tr>
							<td>ชื่อโครงงานภาษาไทย</td>
							<td><input name="nameth" type="text" maxlength="<?=$APP_CONST["PBL"]["lenLim"]["nameth"]?>" pattern="<?=$APP_CONST["PBL"]["REGEX"]["projNameTH"]?>"></td>
						</tr>
						<tr>
							<td>ชื่อโครงงานภาษาอังกฤษ</td>
							<td><input name="nameen" type="text" maxlength="<?=$APP_CONST["PBL"]["lenLim"]["nameen"]?>" pattern="<?=$APP_CONST["PBL"]["REGEX"]["projNameEN"]?>"></td>
						</tr>
						<tr>
							<td>ครูที่ปรึกษา 1</td>
							<td><input name="adv1" type="hidden"><input type="text" readonly onFocus="pUI.select.advisor(1)"></td>
						</tr>
						<tr>
							<td>ครูที่ปรึกษา 2</td>
							<td><input name="adv2" type="hidden"><input type="text" readonly onFocus="pUI.select.advisor(2)"></td>
						</tr>
						<tr>
							<td>ครูที่ปรึกษา 3</td>
							<td><input name="adv3" type="hidden"><input type="text" readonly onFocus="pUI.select.advisor(3)"></td>
						</tr>
						<tr>
							<td>สาขาโครงงาน</td>
							<td><select name="type">
									<?php foreach (str_split(" ABCDEFGHIJKLM") as $et) echo '<option value="' . $et . '">' . pblcode2text($et)[$_COOKIE["set_lang"]] . '</option>'; ?>
								</select></td>
						</tr>
					</tbody>
				</table>
				<div class="group spread">
					<button disabled onClick="return PBL.save.info()" class="blue" type="submit" style="min-width: 40%;">บันทึก (แก้ไข)</button>
				</div>
			</form>
		</div>
		<details class="message cyan score" style="display: none;">
			<summary>ผลการประเมิน: <output name="net"></output> คะแนน</summary>
			<ul>
				<li>เล่มรายงาน <output name="ppr"></output>คะแนน (เต็ม 3)</li>
				<li>โปสเตอร์ <output name="pst"></output>คะแนน (เต็ม 1)</li>
				<li>เข้าร่วมกิจกรรม <output name="act"></output>คะแนน (เต็ม 1)</li>
			</ul>
		</details>
	</div>
	<div class="page" path="member">
		<div class="code">
			<p>รหัสโครงงาน</p>
			<div class="expand">
				<output name="gjc" data-title="โค้ดเข้ากลุ่ม"></output>
			</div>
			<div class="action form"><div class="group center">
				<button onClick="pUI.show.code()" class="gray icon" data-title="ขยายโค้ด"><i class="material-icons">fullscreen</i></button>
				<button onClick="pUI.copy('code')" class="blue icon" data-title="คัดลอกโค้ด"><i class="material-icons">content_copy</i></button>
				<button onClick="pUI.copy('link')" class="blue icon" data-title="คัดลอกลิงก์"><i class="material-icons">link</i></button>
				<button onClick="pUI.show.QRcode()" class="cyan icon" data-title="แสดงคิดอาร์โค้ด"><i class="material-icons">qr_code</i></button>
			</div></div>
		</div>
		<p class="title-btn"><span>สมาชิกกลุ่ม <output name="class"></output></span><button class="yellow" onClick="PBL.terminate(true)">ลบกลุ่ม</button></p>
		<table class="list form slider">
			<tbody></tbody>
		</table>
		<div class="settings message black form" onChange="pUI.form.validate()" data-clarity-unmask="true">
			<strong>การตั้งค่า</strong>
			<div class="group split">
				<div class="group">
					<label for="ref_statusOpen">ปิดไม่รับสมาชิกเพิ่ม</label>
					<input type="checkbox" name="statusOpen" id="ref_statusOpen" class="switch v2 emphasize">
					<label for="ref_statusOpen">เปิดรับสมาชิกใหม่</label>
				</div>
				<button onClick="PBL.save.settings('statusOpen')" class="blue hollow">Apply</button>
			</div>
			<div class="group split">
				<div class="group">
					<input type="checkbox" name="publishWork" id="ref_publishing" class="switch v2 emphasize">
					<label for="ref_publishing">เผยแพร่โครงงาน</label>
				</div>
				<button onClick="PBL.save.settings('publishWork')" class="blue hollow">Apply</button>
			</div>
			<div class="group split">
				<div class="group" style="align-items: center; gap: 10px;">
					<label style="white-space: nowrap;">จำนวนสมาชิกสูงสุด</label>
					<div class="group">
						<select name="maxMember"></select>
					</div>
				</div>
				<button onClick="PBL.save.settings('maxMember')" class="red hollow">Apply</button>
			</div>
		</div>
	</div>
	<div class="page" path="submissions">
		<div class="work form">
			<table cellspacing="0">
				<tbody>

				</tbody>
			</table>
		</div>
		<iframe name="dlframe" hidden></iframe>
	</div>
	<div class="page" path="comments">
		<div class="chat message yellow">
			<div class="start"><button disabled hidden></button></div>
		</div>
	</div>
</section>