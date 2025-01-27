<?php
	$APP_RootDir = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/"));
	require($APP_RootDir."private/script/start/PHP.php");
	$header["title"] = "Late report list";
	$header["desc"] = "Projects that report late";

	require_once($APP_RootDir."public_html/resource/php/core/config.php");
	$APP_PAGE -> print -> head();
?>
<style type="text/css">
	app[name=main] .user-port {
		min-width: 440px; width: 100%; max-width: 95vw; height: 685px; max-height: 90vh;
		border: none;
	}
	app[name=main] .subpath {
		/* margin-top: -10px !important; */
		font-size: 25px;
	}
	app[name=main] .subpath button { margin-right: -5px !important; }
	app[name=main] .class-0 button { min-width: 160px; }
	app[name=main] .option-room button { min-width: 50px; }
	app[name=main] .proj-code {
		font-size: .7em; letter-spacing: .25px;
		font-family: "Roboto Mono", "IBM Plex Mono", monospace;
	}
	app[name=main] .proj-name { max-width: 420px; }
	app[name=main] .projects .card {
		--card-pad: 12.5px;
		padding: var(--card-pad);
		min-width: 375px; width: calc(33.33% - 40px); min-height: 181px; height: auto; max-height: 210px;
		box-shadow: .75px 1.25px var(--shd-small) var(--fade-black-7);
		border-radius: 7.5px; box-sizing: border-box;
		font-size: .95em;
		align-self: stretch;
		transition: var(--time-tst-fast) ease-out;
		overflow-x: hidden;
	}
	app[name=main] .projects .card:where(:hover, :focus, :focus-within) {
		transform: scale(1.01);
		box-shadow: .75px 1.25px var(--shd-small) var(--fade-black-6);
		z-index: 1;
	}
	app[name=main] .projects .card .number {
		--chip-size: 32px;
		position: absolute; transform: translate(calc(-1 * var(--card-pad) - .5px), calc(-1 * var(--card-pad) - .5px));
		width: 0; height: 0;
		border-style: solid; border-width: var(--chip-size) var(--chip-size) 0 0;
		border-color: var(--clr-gg-yellow-100) transparent transparent transparent;
		border-radius: 7.5px 0 0 0;
		pointer-events: none;
	}
	app[name=main] .projects .card .number::before {
		position: absolute; transform: translateY(calc(-1 * var(--chip-size)));
		width: calc(var(--chip-size) - 10px); height: var(--chip-size);
		font-size: .9em; text-align: center;
		content: attr(data-index);
		display: block;
	}
	app[name=main] .projects .card h3 a {
		width: 100%;
		text-indent: 12px;
	}
	/* app[name=main] .projects .card h3 a::first-letter { margin-left: 12px; } */
</style>
<script type="text/javascript">
	const TRANSLATION = location.pathname.substring(1).replace(/\/$/, "").replaceAll("/", "+");
	$(document).ready(function() {
		page.init();
	});
	const page = (function(d) {
		const cv = {
			API_URL: AppConfig.APIbase + "PBL/v1-teacher/list",
			branches: {
				<?php foreach (str_split("ABCDEFGHIJKLM") as $et) echo $et.': {EN: "'.pblcode2text($et)["en"].'", TH: "'.pblcode2text($et)["th"]."\"},\n"; ?>
				"": {EN: "Not chosen", TH: "ยังไม่เลือก"}
			}
		};
		var sv = {
			inited: false,
			currentPath: []
		};
		var initialize = function() {
			if (sv.inited) return;
			$("app[name=main] .class").hide();
			getSubmissions();
			sv.inited = true;
		};
		var getSubmissions = function() {
			app.Util.ajax(cv.API_URL, {type: "group", act: "overdue"}).then(function(dat) {
				sv.submissions = !dat ? null : dat.submissions;
				sv.currentZoom = 0;
				processData();
				render();
				if (sv.submissions != null) $('app[name=main] output[name=total-amount]').val(sv.submissions.length);
			});
		},
		processData = function() {
			if (sv.submissions == null | !sv.submissions.length) return;
			sv.available = { grade: new Set(), branch: new Set() };
			sv.submissions.forEach(es => {
				for (let key in sv.available) sv.available[key].add(es.group[key]);
			}); for (let key in sv.available) sv.available[key] = [...sv.available[key]].sort();
			setOptions();
		},
		setOptions = function() {
			var container = $("app[name=main] .class-1").children(), button;
			for (let grade = 1; grade <= 3; grade++) {
				button = $(`<button class="primary ripple-click" onClick="page.navigate(${grade})">${app.UI.language.getMessage("short-grade")}${grade}</button>`);
				if (!sv.available.grade.includes(grade)) button.attr("disabled", "");
				$(container[1]).find("ol:nth-of-type(1)").append(button);
			} for (let grade = 4; grade <= 6; grade++) {
				button = $(`<button class="primary ripple-click" onClick="page.navigate(${grade})">${app.UI.language.getMessage("short-grade")}${grade}</button>`);
				if (!sv.available.grade.includes(grade)) button.attr("disabled", "");
				$(container[1]).find("ol:nth-of-type(2)").append(button);
			} for (let branch in cv.branches) {
				button = $(`<button class="secondary ripple-click" onClick="page.viewBranch('${branch}')">${cv.branches[branch][app.settings["lang"]]}</button>`);
				if (!sv.available.branch.includes(branch)) button.attr("disabled", "");
				$(container[0]).find("ul").append(button);
			} app.UI.refineElements();
		},
		showRoom = function(grade) {
			sv.available.room = new Set();
			sv.submissions.forEach(es => {
				if (es.group.grade == grade) sv.available.room.add(es.group.room);
			}); sv.available.room = [...sv.available.room];
			var container = $("app[name=main] .class-1 .option-room ol:nth-of-type(1)").empty(), button;
			for (let room = 1; room <= 6; room++) {
				button = $(`<button class="primary ripple-click" onClick="page.viewClass(${grade}, ${room})">${room}</button>`);
				if (!sv.available.room.includes(room)) button.attr("disabled", "");
				container.append(button);
			} container = container.next().empty();
			for (let room = 7; room <= 12; room++) {
				button = $(`<button class="primary ripple-click" onClick="page.viewClass(${grade}, ${room})">${room}</button>`);
				if (!sv.available.room.includes(room)) button.attr("disabled", "");
				container.append(button);
			} container = container.next().empty();
			for (let room = 13; room <= 18; room++) {
				button = $(`<button class="primary ripple-click" onClick="page.viewClass(${grade}, ${room})">${room}</button>`);
				if (!sv.available.room.includes(room)) button.attr("disabled", "");
				container.append(button);
			} app.UI.refineElements();
			container.parent().parent().show();
		},
		render = function(...args) {
			var container = $("app[name=main] .class").hide()
				.filter(`.class-${sv.currentZoom}`).show();
			switch (sv.currentZoom) {
				case 0: {
					container.children(".options, .message").hide();
					if (sv.submissions == null || !sv.submissions.length) container.find(".message").show();
					else {
						container.find(".options").show();
						limitPathLength(0);
					}
				break; }
				case 1: {
					container.children("[class^=option]").hide();
					if (!args[0].showForm) {
						container.find(".option-branch").show();
						limitPathLength(0); sv.currentPath.push([app._var.translationDic()[2].translations[6][app.settings["lang"]], "page.dig('branch')"]);
					} else if (typeof args[1] === "undefined") {
						container.find(".option-grade").show();
						limitPathLength(0); sv.currentPath.push([app._var.translationDic()[2].translations[7][app.settings["lang"]], "page.dig('class')"]);
					} else if (typeof args[2] === "undefined") {
						showRoom(args[1]);
						limitPathLength(1);
						sv.currentPath.push([`${app.UI.language.getMessage("short-grade")}${args[1]}`, null]);
						sv.currentPath.push([app._var.translationDic()[2].translations[8][app.settings["lang"]], `page.navigate(${args[1]})`]);
					}
				break; }
				case 2: {
					container.children(".table, .projects").hide();
					if		(args[0].t == "B" && args[0].s == "T") container.find(".table").show();
					else if	(args[0].t == "C" && args[0].s == "C") container.find(".projects").show();
				break; }
			} showPath();
		},
		showPath = function() {
			var nav = $("app[name=main] .subpath").empty();
			for (let depth = 0; depth < sv.currentPath.length; depth++) {
				var link = $(`<button class="black small icon bare" onClick="${sv.currentPath[depth][1]}"></button>`)
					.html(`<i class="material-icons">chevron_right</i><span class="text">${sv.currentPath[depth][0]}</span>`);
				if (sv.currentPath[depth][1] == null || depth + 1 == sv.currentPath.length) link.attr("disabled", "");
				nav.append(link);
			}
		},
		limitPathLength = function(length) {
			if (sv.currentPath.length > length) sv.currentPath = sv.currentPath.slice(0, length);
		},
		restart = function() {
			sv.currentZoom = 0;
			render();
		}
		dig = function(by) {
			sv.currentZoom = 1;
			render({showForm: by != "branch"});
		},
		navigate = function(grade) {
			sv.currentZoom = 1;
			render({showForm: true}, grade);
		},
		viewBranch = function(branch) {
			sv.currentZoom = 2;
			limitPathLength(1); sv.currentPath.push([cv.branches[branch][app.settings["lang"]], null]);
			render({t: "B", s: "T", d: 1}, branch);
			// Secluded
			var container = $("app[name=main] .class-2 table"), buffer = [null], currentGrade = 0, count = 1;
			container.children(":nth-child(n+2)").remove();
			sv.submissions.forEach(es => {
				if (es.group.branch != branch) return;
				if (es.group.grade != currentGrade) {
					currentGrade = es.group.grade;
					if (buffer[0] != null) container.append(buffer[0]);
					container.append(`<tbody><tr><td colspan="7" center><b>${app.UI.language.getMessage("long-grade")} ${currentGrade}</b></td></tr></tbody>`);
					buffer[0] = $(`<tbody class="responsive"></tbody>`);
				} buffer[1] = $("<tr></tr>");
				buffer[1].append(`<td center>${count++}</td>`);
				buffer[1].append(`<td center>${es.group.room}</td>`);
				buffer[1].append(`<td center class="proj-code select-all">${es.group.code}</td>`);
				buffer[1].append(`<td class="proj-name txtoe"><a href="${AppConfig.baseURL}t/PBL/v2/group/${es.group.code}/browse" target="_blank">${es.group.name}</a></td>`);
				buffer[1].append(`<td>${es.sender.name} (<a href="${AppConfig.baseURL}user/${es.sender.ID}" onClick="return page.showSender(this)" target="_blank">${es.sender.nickname}</a>)</td>`);
				buffer[1].append(`<td center>${es.time}</td>`);
				buffer[0].append(buffer[1]);
			}); container.append(buffer[0]);
			app.UI.refineElements();
		},
		viewClass = function(grade, room) {
			sv.currentZoom = 2;
			limitPathLength(3); sv.currentPath.push([room, null]);
			render({t: "C", s: "C", d: 2}, grade, room);
			// Secluded
			var container = $("app[name=main] .class-2 .projects").empty(), count = 1;
			sv.submissions.forEach(es => {
				if (es.group.grade != grade || es.group.room != room) return;
				container.append(`<li class="card container slider hscroll sscroll css-text-left">\n`.toString()+
					`	<div class="number" data-index="${count++}"></div>\n`.toString()+
					`	<h3><a class="semi-blend css-inline-block" href="${AppConfig.baseURL}t/PBL/v2/group/${es.group.code}/browse" target="_blank">${es.group.name}</a></h3>\n`.toString()+
					`	<p class="txtoe">${app._var.translationDic()[2].translations[6][app.settings["lang"]]}: ${cv.branches[es.group.branch][app.settings["lang"]]}</p>\n`.toString()+
					`	<p>${app._var.translationDic()[2].translations[15][app.settings["lang"]]}: ${es.sender.name} (<a href="${AppConfig.baseURL}user/${es.sender.ID}" onClick="return page.showSender(this)" target="_blank">${es.sender.nickname}</a>)</p>\n`.toString()+
					`	<p>ส่งเมื่อ${es.time}</p>\n`.toString()+
					`	<p class="proj-code right select-all">${es.group.code}</p>\n`.toString()+
					'</li>'
				);
			}); app.UI.refineElements();
		},
		showSender = function(me) {
			if (app.IO.kbd.ctrl() && !app.IO.kbd.alt() && !app.IO.kbd.shift()) return true;
			var code = me.parentNode.parentNode.childNodes[2].innerText,
				sender = me.parentNode.innerText,
				senderID = me.href.match(/\d{5}$/);
			app.UI.lightbox("top", {title: `${app.UI.language.getMessage("work-sender")} ${code} ${app.UI.language.getMessage("student-ID")} ${senderID}`, autoClose: 90}, `<iframe class="user-port" src="${me.href}">Loading...</iframe>`);
			return false;
		};
		return {
			init: initialize,
			restart,
			dig, navigate,
			viewBranch, viewClass,
			showSender
		}
	}(document));
</script>
<script type="text/javascript" src="<?=$APP_CONST["cdnURL"]?>static/script/lib/w3.min.js"></script>
<?php $APP_PAGE -> print -> nav(); ?>
<main>
	<section class="container">
		<h2>
			<a class="blend" onClick="page.restart()" href="javascript:"><?=$header["title"]?></a>
			<ol class="subpath blocks css-inline-flex css-flex-wrap"></ol>
		</h2>
		<p><span class="ref-00001">ทั้งหมด</span> <output name="total-amount"></output> <span class="ref-00002">โครงงาน</span></p>
		<div class="class class-0">
			<div class="options css-flex css-flex-even css-flex-gap-15 center">
				<button class="secondary large ripple-click" onClick="page.dig('branch')"><span class="text">แบ่งตาม<br>สาขาโครงงาน</span></button>
				<button class="secondary large ripple-click" onClick="page.dig('class')"><span class="text">แบ่งตาม<br>ห้องเรียน</span></button>
			</div>
			<div class="message gray center">ไม่มีโครงงานที่ส่งรายงานเกินกำหนดเวลา</div>
		</div>
		<div class="class class-1">
			<div class="option-branch container center">
				<h2>สาขาโครงงาน</h2>
				<ul class="blocks css-flex css-flex-gap-15 css-flex-wrap center"></ul>
			</div>
			<div class="option-grade container center">
				<h2>ระดับชั้น</h2>
				<div class="css-flex css-flex-gap-15 css-flex-wrap center">
					<ol class="blocks css-flex css-flex-gap-15 css-flex-wrap center"></ol>
					<ol class="blocks css-flex css-flex-gap-15 css-flex-wrap center"></ol>
				</div>
			</div>
			<div class="option-room container center">
				<h2>ห้อง</h2>
				<div class="css-flex css-flex-gap-15 css-flex-wrap center">
					<ol class="blocks css-flex css-flex-gap-15 css-flex-wrap center"></ol>
					<ol class="blocks css-flex css-flex-gap-15 css-flex-wrap center"></ol>
					<ol class="blocks css-flex css-flex-gap-15 css-flex-wrap center"></ol>
				</div>
			</div>
		</div>
		<div class="class class-2">
			<div class="table static striped"><table><thead>
				<tr>
					<th rowspan="2">ลำดับที่</th>
					<th colspan="3">โครงงาน</th>
					<th colspan="2">รายงานฉบับสมบูรณ์</th>
				</tr>
				<tr>
					<th>ห้อง</th>
					<th>รหัส</th>
					<th>ชื่อ</th>
					<th>ผู้ส่ง</th>
					<th>เวลาส่ง</th>
				</tr>
			</thead></table></div>
			<ol class="projects blocks css-flex css-flex-gap-20 css-flex-wrap center"></ol>
		</div>
	</section>
</main>
<?php
	$APP_PAGE -> print -> materials();
	$APP_PAGE -> print -> footer();
?>