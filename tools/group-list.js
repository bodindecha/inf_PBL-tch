const PBL = (function(d) {
	const cv = {
		API_URL: "/t/PBL/v2/api/", USER: top.USER,
		isModerator: top.USER_ADMIN,
		workFile: [
			"แผนผังความคิดบูรณาการ 8 กลุ่มสาระการเรียนรู้",
			"ใบงาน IS1-1 (ประเด็นที่ต้องการศึกษา)",
			"ใบงาน IS1-2 (การระบุปัญหา)",
			"ใบงาน IS1-3 (การระบุสมมติฐาน)",
			"เล่มรายงานโครงงานบทที่ 1",
			"เล่มรายงานโครงงานบทที่ 2",
			"เล่มรายงานโครงงานบทที่ 3",
			"เล่มรายงานโครงงานบทที่ 4",
			"เล่มรายงานโครงงานบทที่ 5",
			"รวมเล่มรายงานโครงงาน (ฉบับเต็ม)",
			"บทคัดย่อโครงงาน",
			"โปสเตอร์"
		], fileList: {
			"mindmap": "แผนผังความคิด",
			"IS1-1": "ใบงาน IS1-1",
			"IS1-2": "ใบงาน IS1-2",
			"IS1-3": "ใบงาน IS1-3",
			"report-1": "เล่มรายงานบทที่ 1 ",
			"report-2": "เล่มรายงานบทที่ 2 ",
			"report-3": "เล่มรายงานบทที่ 3 ",
			"report-4": "เล่มรายงานบทที่ 4 ",
			"report-5": "เล่มรายงานบทที่ 5 ",
			"report-all": "เล่มรายงานฉบับเต็ม",
			"abstract": "บทคัดย่อ",
			"poster": "โปสเตอร์",
		}
	};
	var sv = { started: false, opts: null, state: {
		useFilter: false, loaded: false
	} };
	var initialize = function() {
		if (!sv.started) {
			sv.started = true;
			// Fill element
			$("main .mform [name=grade]").append('<option value="0">ทุกระดับชั้น</option>');
			$("main .mform [name=room]").append('<option value="0">ทุกห้องเรียน</option>');
			for (let grade = 1; grade <= 6; grade++) $("main .mform [name=grade]").append('<option value="'+grade+'">ม.'+grade+'</option>');
			for (let room = 1; room <= 19; room++) $("main .mform [name=room]").append('<option value="'+room+'">'+room+'</option>');
			for (let pos = 0; pos < cv.workFile.length; pos++) $("main .fform [name=files]").append('<option value="'+pos+'">'+cv.workFile[pos]+'</option>');
			for (let amt = 1; amt <= 7; amt++) $("main .fform [name=mbr_amt]").append('<option value="'+amt+'">'+amt+'</option>');
			$("main .mform, main .fform").on("change", pUI.filter.enableSearch);
			// Load form
			seek_param();
			// Other initialization
			$(window).on("resize", function() {
				setTimeout(function() {
					var infobox = $("main .results > li[open] .extender");
					infobox.css("height", (infobox.children().first().outerHeight() + infobox.children().last().outerHeight()).toString()+"px");
				}, 250);
			});
		}
	};
	var seek_param = async function() { if (location.hash.length > 1) {
		// Extract hashes
		var hash = {}; location.hash.substring(1, location.hash.length).split("&").forEach((ehs) => {
			let ths = ehs.split("=");
			hash[ths[0]] = ths[1];
		});
		if (typeof hash.filter !== "undefined") fillForm(objDecrypt(hash.filter));
		if (typeof hash.autoload !== "undefined") {
			PBL.load();
			app.io.URL.removeHash("autoload=");
		}
	} },
	fillForm = function(info) {
		var data = {}, filter = ["type", "isAdvisor", "search_type", "search_diff", "search_key", "sort", "order"];
		info.split("&").forEach((eb) => {
			let ei = eb.split("="), target = null;
			switch (ei[0]) {
				case "grade": target = "main .mform [name=grade]"; break;
				case "room": target = "main .mform [name=room]"; break;
				case "type": target = "main .fform [name=type]"; break;
				case "isAdvisor": target = "main .fform [name=advisor]"; break;
				case "search_type": target = "main .fform [name=search_type]"; break;
				case "search_diff": target = "main .fform [name=search_range]"; break;
				case "search_key": target = "main .fform [name=search_key]"; ei[1] = decodeURIComponent(ei[1]); break;
				case "wf_status": target = "main .fform [name=status]"; break;
				case "wf_count": target = "main .fform [name=work]"; break;
				case "mbr_comp": target = "main .fform [name=mbr_comp]"; ei[1] = decodeURIComponent(ei[1]); break;
				case "mbr_amt": target = "main .fform [name=mbr_amt]"; break;
				case "sort": target = "main .fform [name=sort]"; break;
				case "order": target = "main .fform [name=order]"; ei[1] = ei[1].toLowerCase(); break;
			} if (target != null) $(target).val(ei[1]);
			data[ei[0]] = ei[1];
		}); if (Object.keys(data).includes("wf_file") && typeof data.wf_count !== "undefined") {
			// Multiple select decoder
			data.wf_file = parseInt(decodeURIComponent(data.wf_file));
			for (let pos = 0; pos < cv.workFile.length; pos++)
				if (data.wf_file&Math.pow(2, pos)) d.querySelector('main .fform [name=files] option[value="'+pos+'"]').selected = true;
		} if (Object.keys(data).filter(hint => filter.includes(hint)).length) pUI.filter.show();
	},
	getOpts = function() {
		var data = {
			grade: parseInt($("main .mform [name=grade]").val()),
			room: parseInt($("main .mform [name=room]").val())
		}; if (data.grade == 0) delete data.grade;
		if (data.room == 0) delete data.room;
		
		if (typeof data.grade !== "undefined" && (data.grade < 1 || data.grade > 6)) {
			app.ui.notify(1, [2, "Invalid grade."]);
			$("main .mform [name=grade]").focus();
		} else if (typeof data.room !== "undefined" && (data.room < 1 || data.room > 19)) {
			app.ui.notify(1, [2, "Invalid room."]);
			$("main .mform [name=room]").focus();
		} else if (!sv.state["useFilter"]) {
			sv.opts = data;
			return data;
		} else {
			data = {...data,
				type: $("main .fform [name=type]").val(),
				isAdvisor: $("main .fform [name=advisor]").val(),
				search_type: $("main .fform [name=search_type]").val(),
				search_diff: $("main .fform [name=search_range]").val(),
				search_key: $("main .fform [name=search_key]").val().trim().replaceAll("เเ", "แ"),
				wf_file: $("main .fform [name=files]").val(),
				wf_status: $("main .fform [name=status]").val(),
				wf_count: $("main .fform [name=work]").val(),
				mbr_comp: $("main .fform [name=mbr_comp]").val(),
				mbr_amt: parseInt($("main .fform [name=mbr_amt]").val()),
				sort: $("main .fform [name=sort]").val(),
				order: $("main .fform [name=order]").val().toUpperCase()
			}; if (data.type == " ") delete data.type;
			if (data.isAdvisor == "A") delete data.isAdvisor;
			if (data.mbr_comp == ">=" && data.mbr_amt == 1) { delete data.mbr_comp; delete data.mbr_amt; }
			if (data.sort == "class" && data.order == "ASC") { delete data.sort; delete data.order; }
			
			if (![undefined, ...("ABCDEFGHIJKLM".split("")), ""].includes(data.type)) {
				app.ui.notify(1, [2, "Invalid project type."]);
				$("main .fform [name=type]").focus();
			} else if (![undefined, "Y", "N"].includes(data.isAdvisor)) {
				app.ui.notify(1, [2, "Invalid advisor selection."]);
				$("main .fform [name=advisor]").focus();
			} else if (data.search_key.length && !["code", "name", "member", "advisor"].includes(data.search_type)) {
				app.ui.notify(1, [2, "Invalid search type."]);
				$("main .fform [name=search_type]").focus();
			} else if (data.search_key.length && !"SEC".split("").includes(data.search_diff)) {
				app.ui.notify(1, [2, "Invalid search type."]);
				$("main .fform [name=search_type]").focus();
			} else if (data.search_key.length && !data.search_key.match($("main .fform [name=search_type] option:checked").attr("data-regex"))) {
				app.ui.notify(1, [2, "Invalid search keyword."]);
				$("main .fform [name=search_key]").focus();
			} else if (data.wf_file.length && !["sent", "none"].includes(data.wf_status)) {
				app.ui.notify(1, [2, "Invalid work status."]);
				$("main .fform [name=status]").focus();
			} else if (data.wf_file.length && !["all", "some"].includes(data.wf_count)) {
				app.ui.notify(1, [2, "Invalid work counting method."]);
				$("main .fform [name=work]").focus();
			} else if (![undefined, ">=", ">", "<=", "<", "=", "!="].includes(data.mbr_comp)) {
				app.ui.notify(1, [2, "Invalid member amount comparer."]);
				$("main .fform [name=mbr_comp]").focus();
			} else if (data.mbr_amt === "number" && (data.mbr_amt < 1 || data.mbr_amt > 7)) {
				app.ui.notify(1, [2, "Invalid member amount selected."]);
				$("main .fform [name=mbr_amt]").focus();
			} else if (![undefined, "class", "code", "name", "time"].includes(data.sort)) {
				app.ui.notify(1, [2, "Invalid ordering index."]);
				$("main .fform [name=sort]").focus();
			} else if (![undefined, "ASC", "DESC"].includes(data.order)) {
				app.ui.notify(1, [2, "Invalid ordering type."]);
				$("main .fform [name=order]").focus();
			} else {
				if (!data.search_key.length) {
					delete data.search_type;
					delete data.search_diff;
					delete data.search_key;
				} else if (data.search_type == "code") data.search_key = data.search_key.toUpperCase();
				if (!data.wf_file.length) {
					delete data.wf_file;
					delete data.wf_status;
					delete data.wf_count;
				} else data.wf_file = data.wf_file.reduce((total, pos) => total + Math.pow(2, parseInt(pos)), 0);
				sv.opts = data;
				return data;
			}
		} return null;
	},
	fetch = function(loadNext = 0) {
		(async function(loadNext) {
			if (!loadNext) {
				getOpts();
				$('main .mform button[onClick*="PBL.load"]').attr("disabled", "");
			} else $("main .browser div:last-child > button").attr("disabled", "");
			await ajax(cv.API_URL+"list", {type: "group", act: null, param: {...sv.opts, loadNext: loadNext}}).then(function(dat) {
				if (!loadNext) {
					let old = location.pathname+location.search+location.hash;
					history.replaceState(null, null, location.pathname+location.search+(Object.keys(sv.opts).length ? "#filter="+objEncrypt(sv.opts) : ""));
					if (old != location.pathname+location.search+location.hash) sys.back.logPageHistory();
				} else $("main .browser div:last-child > button").removeAttr("disabled");
				if (dat) render(dat.projects, dat.nextLoad, !loadNext);
				else if (!loadNext) pUI.filter.enableSearch();
			});
		}(loadNext)); return false;
	},
	render = async function(projList, nextLoad, clearList = false) {
		var result = $("main .browser .results"), next = $("main .browser > div:last-child > *");
		if (!sv.state.loaded) {
			sv.state.loaded = true;
			$('<hr style="display: none;">').insertAfter("main .fform").fadeIn();
			$("main .oform").toggle("blind");
			result.parent().show();
		} if (clearList) result.html("");
		// Listing
		if (projList.length) {
			projList.forEach(proj => {
				result.append(projBlock(proj));
			}); if (nextLoad != null) next.replaceWith('<button class="blue hollow small" onClick="PBL.load('+nextLoad.toString()+')">Load more</button>');
			else next.fadeOut(function() {
				$(this).replaceWith('<span>——— End of results ———</span>').show();
			}); if (!clearList) pUI.filter.update();
			else d.querySelector("main .oform").reset();
		} else if (clearList) next.replaceWith('<div class="message gray">No result</div>');
		else next.fadeOut(function() {
			$(this).replaceWith('<span>——— End of results ———</span>').show();
		});
	},
	projBlock = pi => '<li><div class="accordian" onClick="PBL.expand(this)">'+
		'<div class="title">'+
			'<span class="title-name txtoe">'+(pi.title == pi.code ? "" : pi.title)+'</span>'+
			'<span class="title-code">'+pi.code+'</span>'+
		'</div></div><div class="extender" data-code="'+pi.code+'" data-loaded="false" style="height: 0;">'+
			'<div class="details">'+
				'<p>สมาชิก <output name="'+pi.code+':class"></output></p>'+
				'<table class="namelist slider"><tbody name="'+pi.code+':member"></tbody></table>'+
				'<p hidden>สาขาโครงงาน: <output name="'+pi.code+':type"></output></p>'+
				'<ul hidden name="'+pi.code+':advisor"></ul>'+
				'<p>แก้ไขล่าสุด<output name="'+pi.code+':update"></output></p>'+
				'<p hidden>ได้ <output name="'+pi.code+':score"></output> คะแนน</p>'+
			'</div><div class="action form inline">'+
				'<!--div class="group" hidden>'+
					'<span class="small">Submissions</span>'+
					'<select name="'+pi.code+':file"></select>'+
					'<button onClick="pUI.viewFile(\''+pi.code+'\')" class="cyan small hollow">View</button>'+
				'</div-->'+
				'<!div class="group">'+
					'<a role="button" href="/t/PBL/v2/group/'+pi.code+'/browse" class="purple small hollow" target="_blank" draggable="false">View submissions</a>'+
					(cv.isModerator ? '<a role="button" href="/t/PBL/v2/group/'+pi.code+'/edit" class="orange small hollow" target="_blank" draggable="false">Overwrite</a>' : '')+
				'<!/div>'+
			'</div>'+
		'</div></li>',
	preview = async function(me) {
		var infobox = $(me).next();
		me = $(me.parentNode);
		const code = infobox.attr("data-code");
		if (me.is("[open]")) {
			me.removeAttr("open");
			me.children().last().animate({height: 0}, 500);
		} else if (infobox.attr("data-loaded") == "false")
			await ajax(cv.API_URL+"information", {type: "group", act: code}).then(async function(dat) {
				$("main .results > li[open] .extender").animate({height: 0}, 500)
					.parent().removeAttr("open");
				if (dat) {
					// Fill information
					$('main .results output[name="'+code+':class"]').val(dat.class);
					if (dat.type.length) $('main .results output[name="'+code+':type"]').val(dat.type)
						.parent().removeAttr("hidden");
					if (dat.score != null) $('main .results output[name="'+code+':score"]').val(dat.score)
						.parent().removeAttr("hidden");
					$('main .results output[name="'+code+':update"]').val(dat.update);
					// Load members
					await ajax(cv.API_URL+"information", {type: "person", act: "student", param: dat.member.join(",")}).then(function(dat2) {
						var index = 1, listBody = "";
						dat2.list.forEach(es => {
							listBody += '<tr><td>'+index.toString()+'.</td><td>'+es.fullname+' (<a href="/user/'+es.ID+'" target="_blank" draggable="false">'+es.nickname+'</a>)</td><td>เลขที่ '+es.number+'</td><td>';
							if (index++ == 1) listBody += '<a role="button" class="blue hollow pill" disabled>หัวหน้ากลุ่ม</a>';
							listBody += '</td></tr>';
						}); $('main .results tbody[name="'+code+':member"]').html(listBody);
					}); // Load advisor
					if (dat.advisor.length) await ajax(cv.API_URL+"information", {type: "person", act: "teacher", param: dat.advisor.join(",")}).then(function(dat3) {
						var display = $('main .results ul[name="'+code+':advisor"]').removeAttr("hidden");
						dat.advisor.forEach(et => {
							if (typeof dat3.list[et] !== "undefined") display.append('<li>'+dat3.list[et]+'</li>');
						}); $('<p>ครูที่ปรึกษาโครงงาน</p>').insertBefore(display);
					}); // Load file option (Unnecessary)
					var container = $('main .results .action [name="'+code+':file"]'),
						requireIS = ["2", "4"].includes(dat.class.substring(2, 3));
					Object.keys(cv.fileList).forEach(ef => {
						if ((requireIS && /^IS\d-\d$/.test(ef)) || !/^IS\d-\d$/.test(ef)) container.append('<option value="'+ef+'">'+cv.fileList[ef]+'</option>');
					}); // Show display
					infobox.attr("data-loaded", "true"); me.attr("open", "");
					infobox.animate({height: infobox.children().first().outerHeight() + infobox.children().last().outerHeight()}, 500, $.bez([0.65, 0, 0.35, 1]));
				}
			});
		else {
			$("main .results > li[open] .extender").animate({height: 0}, 500).parent().removeAttr("open");
			me.attr("open", "");
			infobox.animate({height: infobox.children().first().outerHeight() + infobox.children().last().outerHeight()}, 500, $.bez([0.65, 0, 0.35, 1]));
		}
	};
	return {
		init: initialize,
		load: fetch,
		expand: preview,
		// External
		changeState: (name, value) => { sv.state[name] = value; },
		cv: cv
	};
}(document));