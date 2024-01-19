const PBL = (function(d) {
	const cv = {
		API_URL: "/t/PBL/v2/api/", USER: top.USER,
		HTML: {
			"tab-menu": (path, name, icon) => '<div class="tab" data-page="'+path+'" onClick="PBL.openPage(this)"><div class="face"><i class="material-icons">'+icon+'</i><span>'+name+'</span></div><div class="pop-label"><span>'+name+'</span></div></div>',
			"timeline": (task, pd) => {
				if (!pd.sem) return '</div><br><p>ภาคเรียนที่ 2</p><div class="progress slider">';
				task = task.toString(); var output = '<div class="sec sec-'+pd.deadline+'"><div class="disp" data-title="'+pd.title+'"><span class="line"></span><label>'+task+'</label></div><p>กำหนดส่งภายใน</p><output deadline="'+pd.deadline+'"></output><div class="asgmt-list">';
				Object.keys(pd.works).forEach(work => {
					output += '<div class="work"><i class="material-icons" work="'+work+'"></i><label>'+pd.works[work]+'</label></div>'
				}); output += '</div></div>';
				return output;
			}, "work-act": type => '<button class="blue icon" onClick="PBL.upload(\''+type+'\')" data-title="Replace with new file"><i class="fa fa-upload"></i></button>'+
				'<button class="gray icon" onClick="PBL.file.preview(\''+type+'\')" data-title="View file"><i class="material-icons">visibility</i></button>'+
				'<button class="yellow icon" onClick="PBL.file.print(\''+type+'\')" data-title="Print file"><i class="material-icons">print</i></button>'+
				'<button class="green icon" onClick="PBL.file.download(\''+type+'\')" data-title="Download file"><i class="material-icons">download</i></button>'+
				'<button class="red icon" onClick="PBL.file.remove(\''+type+'\')" data-title="Remove file"><i class="material-icons">delete</i></button>',
			"memberAction": ID => '<div class="group pill">'+
				'<button onClick="PBL.kick('+ID+')" class="red hollow">ลบชื่อออก</button>'+
				'<button onClick="PBL.setLeader('+ID+')" class="yellow hollow icon" data-title="Set as leader">👑</button>'+
				'</div>',
			"uploadButton": work => '<button class="blue hollow icon" onClick="PBL.upload(\''+work+'\')"><i class="material-icons">add_circle</i>Upload attachment</button>',
			"newMember": index => '<tr class="add"><td>'+index.toString()+'.</td><td colspan="2"><button class="black hollow icon" onClick="PBL.addMember(true)"><i class="material-icons">person_add</i> เพิ่มสมาชิก</button></td><td><input type="hidden" name="temp_mbr" /><input type="hidden" readonly /></td></tr>'
		},
		tab_menu: {
			ng: [
				["open", "Open", "exit_to_app"],
				["create", "Create", "library_add"]
			],
			hg: [
				["information", "Information", "library_books"],
				["member", "Members", "group"],
				["submissions", "Submissions", "assignment"],
				["comments", "Comments", "comment"]
			]
		}, timeline: [
			{
				isPBL: true,
				title: "การจัดกลุ่ม",
				deadline: "A",
				works: {
					"n1": "ชื่อโครงงาน",
					"n2": "ครูที่ปรึกษา",
					"n3": "สาขาโครงงาน"
				}, sem: 1
			}, {
				isPBL: true,
				title: "ส่งผังความคิด",
				deadline: "B",
				works: {
					"mindmap": "ผังความคิดบูรณาการ 8 กลุ่มสาระการเรียนรู้"
				}, sem: 1
			}, {
				isPBL: false,
				title: "ส่งใบงาน IS",
				deadline: "C",
				works: {
					"IS1-1": "IS1-1 ประเด็นที่สนใจ",
					"IS1-2": "IS1-2 ปัญหาเกี่ยวกับประเด็นที่สนใจ",
					"IS1-3": "IS1-3 สมมติฐานที่เกี่ยวข้อง"
				}, sem: 1
			}, {
				isPBL: true,
				title: "ส่งบทที่ 1-3",
				deadline: "D",
				works: {
					"report-1": "รายงานโครงงานบทที่ 1",
					"report-2": "รายงานโครงงานบทที่ 2",
					"report-3": "รายงานโครงงานบทที่ 3"
				}, sem: 1
			}, {
				sem: 0
			}, {
				isPBL: true,
				title: "ส่งบทที่ 4-5",
				deadline: "E",
				works: {
					"report-4": "รายงานโครงงานบทที่ 4",
					"report-5": "รายงานโครงงานบทที่ 5"
				}, sem: 2
			}, {
				isPBL: true,
				title: "ส่งเล่มเต็มและอื่นๆ",
				deadline: "F",
				works: {
					"report-all": "รวมเล่มรายงานโครงงาน",
					"abstract": "บทคัดย่อโครงงาน"
				}, sem: 2
			}, {
				isPBL: true,
				title: "ส่งโปสเตอร์",
				deadline: "G",
				works: {
					"poster" :"โปสเตอร์"
				}, sem: 2
			},
		], MSG: {
			"delete-group": "Are you sure you want to delete this group and its progress (all your work) ?\nThis action can't be undone.",
			"delete-mbr": "Are you sure you want to delete this member?\nThis action can't be undone.",
			"newLeader": "Are you sure you want to set your friend as the new group leader?\nThis action can't be undone.",
			"del-work": filename => "Are you sure you want to delete group \""+filename+"\" file?\nThis action can't be undone."
		}, workload: {
			"mindmap": "แผนผังความคิดบูรณาการ 8 กลุ่มสาระการเรียนรู้",
			"IS1-1": "ใบงาน IS1-1 (ประเด็นที่ต้องการศึกษา)",
			"IS1-2": "ใบงาน IS1-2 (การระบุปัญหา)",
			"IS1-3": "ใบงาน IS1-3 (การระบุสมมติฐาน)",
			"report-1": "เล่มรายงานโครงงานบทที่ 1",
			"report-2": "เล่มรายงานโครงงานบทที่ 2",
			"report-3": "เล่มรายงานโครงงานบทที่ 3",
			"report-4": "เล่มรายงานโครงงานบทที่ 4",
			"report-5": "เล่มรายงานโครงงานบทที่ 5",
			"report-all": "รวมเล่มรายงานโครงงาน (ฉบับเต็ม)",
			"abstract": "บทคัดย่อโครงงาน",
			"poster": "โปสเตอร์"
		}, mbr_settings: ["statusOpen", "publishWork", "maxMember"]
	};
	var sv = {
		started: false, HTML: {}, current: {
			page: "", workType: "",
		}, state: { button_freeze: false,
			loadInfoOver: true, loadSettingsOver: true
		}, notifyJsInited: false, history: {
			unsavedPage: []
		}
	};
	var initialize = function() {
		if (!sv.started) {
			sv.started = true;
			// Check logged-in
			if (!cv.USER.length) return sys.auth.orize(true, true);
			// Initial group state
			sv.HTML["header-bar"] = $("main > .container").html();
			getStatus();
			// Set up Notify-js
			onUnsavedloadOverSetup();
		}
	}, btnAction = {
		freeze: function() {
			$("main .pages .page.current button, main .tab-selector").attr("disabled", "");
			sv.state["button_freeze"] = true;
		}, unfreeze: function() {
			if (sv.state["button_freeze"]) {
				$("main .pages .page.current button, main .tab-selector").removeAttr("disabled");
				sv.state["button_freeze"] = false;
				switch (sv.current["page"]) {
					case "information": {
						sv.state["loadInfoOver"] = false; confirmLeave(sv.current["page"]);
					break; }
					/* case "member": {
						sv.state["loadSettingsOver"] = false; confirmLeave(sv.current["page"]);
					break; } */
				}
			}
		}
	}, helpCentre = function(type = null) {
		if (type == null) app.ui.lightbox.open("mid", {title: "ความช่วยเหลือ", allowclose: true, html: d.querySelector("main > .manual[hidden]").innerHTML});
		else {
			const helpURL = $('main > .manual[hidden] a[href$="PBL.help(\''+type+'\')"]').attr("data-href");
			switch (type) {
				case "document": {
					// app.ui.notify(1, [2, "Help: Manual document is currently unavailable."]);
					let size = [$(".lightbox .body").width().toString()+"px", $(".lightbox .body").height().toString()+"px"]
					$(".lightbox .body").html('<iframe src="'+helpURL+'" style="width:'+size[0]+';height:'+size[1]+';border:none">Loading...</iframe>');
					$(".lightbox .body > iframe").animate({width: "90vw", height: "80vh"});
					// app.ui.lightbox.close();
				break; }
				case "mediaVDO": {
					// app.ui.notify(1, [2, "Help: Manual video playlist is currently unavailable."]);
					let size = [$(".lightbox .body").width().toString()+"px", $(".lightbox .body").height().toString()+"px"]
					$(".lightbox .body").html('<iframe src="'+helpURL+'" style="width:'+size[0]+';height:'+size[1]+';border:none">Loading...</iframe>');
					var dim = [$(window).width(), $(window).height()];
					if (dim[0] > dim[1]) {
						dim[1] *= 0.8;
						dim[0] = dim[1] * 16 / 9;
					} else {
						dim[0] *= 0.9;
						dim[1] = dim[0] * 9 / 16;
					} $(".lightbox .body > iframe").animate({width: dim[0].toString()+"px", height: dim[1].toString()+"px"});
					// app.ui.lightbox.close();
				break; }
				default: {
					const helpWin = window.open(helpURL);
					app.ui.lightbox.close();
				}
			}
		}
	}, onUnsavedloadOverSetup = function() {
		if (!sv.notifyJsInited) {
			sv.notifyJsInited = true;
			$.notify.addStyle("PBL-unsaved", {html: '<div>'+
				'<div class="clearfix">'+
					'<div class="title" data-notify-html="title"></div>'+
					'<div class="form inline">'+
						'<button class="yellow" name="keep">Keep unsaved changes</button>'+
						'<button class="red hollow" name="load">Load updates</button>'+
					'</div>'+
				'</div>'+
			'</div>'});
			$(document).on('click', 'main .notifyjs-PBL-unsaved-base [name="keep"]', function() { $(this).trigger('notify-hide'); });
			$(document).on('click', 'main .page[path="information"] .notifyjs-PBL-unsaved-base [name="load"]', function() {
				load_groupInfo();
				$(this).trigger('notify-hide');
			});
			$(document).on('click', 'main .page[path="member"] .notifyjs-PBL-unsaved-base [name="load"]', function() {
				$('main .page[path] .settings [onClick^="PBL.save.settings"]').attr("disabled", "");
				cv.mbr_settings.slice(0, 2).forEach(es => {
					d.querySelector('main .page[path] .settings [name="'+es+'"]').checked = (sv.groupSettings[es] == "Y");
				}); cv.mbr_settings.slice(2, 3).forEach(es => {
					$('main .page[path] .settings [name="'+es+'"]').val(sv.groupSettings[es]);
				}); sv.state["loadSettingsOver"] = true; checkUnsavedPage("member");
				$(this).trigger('notify-hide');
			});
		}
	}, getStatus = async function() {
		var code = location.pathname.match(/\/[A-Z0-9]{6}\//);
		if (code != null && code.length) {
			code = code[0].slice(1, 7);
			await ajax(cv.API_URL+"group-status", {type: "get", act: "personal", param: code}).then(function(status) {
				sv.status = status;
				sv.code = status.code;
				let loadPart = checkHashPath(sv.status.isGrouped, "render");
				initialRender(loadPart);
			});
		} else { // v2/group/home
			sv.status = {isGrouped: false};
			sv.code = null;
			let loadPart = checkHashPath(sv.status.isGrouped, "render");
			initialRender(loadPart);
		}
	}, checkHashPath = function(isGrouped=null, cb_val=null) {
		var hash = location.hash.substring(1), sendback = [null];
		if (hash.length) {
			// var path = hash.split("/");
			if (isGrouped == false) {
				if (hash == "create") sendback = [hash];
				else if (hash == "open") sendback = [hash, null];
			} else if (isGrouped == true) {
				if (/^(information|member|submissions|comments)$/.test(hash)) sendback = [hash];
			} // if (sendback[0] != null) sv.current["page"] = sendback[0];
		} // if (cb_val == null || cb_val == "render") return sendback[0]; else
		return sendback;
	}, initialRender = function(loadPart) {
		if (loadPart[1] == null && loadPart[2] >= 0) {
			sv.status = {isGrouped: false};
			sv.code = null;
		} // Tab bar
		var dType = (sv.status.isGrouped ? "h" : "n");
		$("main > .container")
			.html(sv.HTML["header-bar"])
			.append('<div class="wrapper tab-selector"><div class="tabs"></div></div>')
			.append('<section class="pages"></section>');
		var tab_menu_holder = $("main > .container .tab-selector .tabs").css("--tab-count", cv.tab_menu[dType+"g"].length);
		cv.tab_menu[dType+"g"].forEach(em => tab_menu_holder.append(cv.HTML["tab-menu"](...em)));
		// Continue to section
		if (loadPart[0] == null) loadPart[0] = (sv.status.isGrouped ? "member" : "open");
		// Add pages
		$("main > .container .pages").load("/t/PBL/v2/tools/group-edit_blocks.html .pages[page-type="+dType+"g]", function() {
			$("main > .container > .pages").html($("main > .container .pages > .pages").html());
			if (dType == "h") {
				renderBlock("member", "initClass");
				renderBlock("member", "settingsOption");
				renderBlock("submissions", "readyTable");
				chatApp.init(true); sv.chatInit = false;
			} PBL.openPage(loadPart[0], loadPart);
		});
	}, load_page = function(me, args=[]) {
		if (typeof me === "string") me = d.querySelector('main .tab-selector .tab[data-page="'+me+'"]');
		var pageURL = $(me).attr("data-page");
		if (pageURL == sv.current["page"]) return false;
		if (args.length <= 1 || true) history.replaceState(null, null, location.pathname+location.search+"#"+pageURL);
		sv.current["page"] = pageURL;
		$("main .tab-selector .tab.active").removeClass("active"); $(me).addClass("active");
		$("main .pages .page.current").removeClass("current"); $('main .pages .page[path="'+pageURL+'"]').addClass("current");
		if (args.length > 1) args.shift();
		renderBlock(pageURL, ...args);
	}
	var renderBlock = function(object, ...params) {
		switch (object) {
			case "create": {
				// if (params[0] == null && typeof params[1] !== "undefined") action_message(params[1]);
			} break;
			case "open": {
				if (params[0] == null || params[0] == "open") $('main .page[path="open"] [name="gjc"]').focus();
				else if (params[0].length) {
					$('main .page[path="open"] [name="gjc"]').val(params[0]);
					$('main .page[path="open"] button').focus();
				}
			} break;
			case "information": {
				if (sv.state["loadInfoOver"]) load_groupInfo();
				else $('main .page[path="information"] .form').notify({
					title: "You have unsaved changes."
				}, {
					className: "warning",
					elementPosition: "bottom right",
					autoHideDelay: 60000,
					clickToHide: false,
					style: "PBL-unsaved"
				}); sv.state["button_freeze"] = true;
			} break;
			case "member": {
				if (params[0] == "initClass") {
					$('main .page[path="member"] output[name="class"]')
						.val("ม."+sv.status.grade.toString()+"/"+sv.status.room.toString());
				} else if (params[0] == "settingsOption") {
					var field = [$('main .page[path] .settings [name="maxMember"]')];
					for (let amt = 1; amt <= 7; amt++) field[0].append('<option value="'+amt+'">'+amt+'</option>');
				} else {
					$('main .page[path="member"] .code output[name="gjc"]').val(sv.code);
					load_member();
				}
			} break;
			case "submissions": {
				if (params[0] == "readyTable") {
					var worklist = "";
					Object.keys(cv.workload).forEach(ew => {
						if (!sv.status.requireIS && ew.substr(0, 2)=="IS") return;
						worklist += '<tr><td><span class="--txtoe">'+cv.workload[ew]+'</span></td><td><div class="group center" data-work="'+ew+'">';
						worklist += '</div></td><td center><output name="'+ew+'"></output></td></tr>';
					}); $('main .page[path="submissions"] .work tbody').html(worklist);
				} else load_work_status();
			} break;
			case "comments": {
				if (sv.chatInit == false) {
					sv.chatInit = true, starter = setInterval(() => {
						if (chatApp.status()) {
							chatApp.start("tch", [sv.code]);
							clearInterval(starter);
						}
					}, 250);
				}
			} break;
		}
	}, action_message = function(msg) {
		var message = []; switch (msg) {
			case 0: message = [1, "Unable to perform action for you are now not in a group."]; break;
		} if (message.length) app.ui.notify(1, message);
	}, group_create = function(send) {
		(async function(mode) {
			if (!mode) $('main .page[path="create"] input, main .page[path="create"] select').val("");
			else {
				var data = {
					nameth: $('main .page[path="create"] [name="nameth"]').val().trim().replaceAll("เเ", "แ"),
					nameen: $('main .page[path="create"] [name="nameen"]').val().trim().replaceAll("เเ", "แ"),
					mbr1: $('main .page[path="create"] [name="mbr1"]').val(),
					adv1: $('main .page[path="create"] [name="adv1"]').val(),
					adv2: $('main .page[path="create"] [name="adv2"]').val(),
					adv3: $('main .page[path="create"] [name="adv3"]').val(),
					type: $('main .page[path="create"] [name="type"]').val()
				};
				if (!/\d{5}/.test(data.mbr1)) {
					app.ui.notify(1, [2, "Group leader required."]);
					$('main .page[path="create"] [name="mbr1"] + input').focus();
				} else if (data.nameth.length && !/^[ก-๛0-9A-Za-z ()[\]{}\-!@#$%.,/&*+_?|]{3,150}$/.test(data.nameth)) {
					app.ui.notify(1, [2, "Invalid Thai project name."]);
					$('main .page[path="create"] [name="nameth"]').focus();
				} else if (data.nameen.length && !/^[A-Za-z0-9ก-๛ ()[\]{}\-!@#$%.,/&*+_?|]{3,150}$/.test(data.nameen)) {
					app.ui.notify(1, [2, "Invalid English project name."]);
					$('main .page[path="create"] [name="nameen"]').focus();
				} else if (!" ABCDEFGHIJKLM".includes(data.type)) {
					app.ui.notify(1, [2, "Invalid project type."]);
					$('main .page[path="create"] [name="type"]').focus();
				} else {
					btnAction.freeze();
					await ajax(cv.API_URL+"group-action", {type: "create", act: "new-group", param: data})
						.then(un2group).then(btnAction.unfreeze);
				}
			}
		}(send)); return false;
	}, group_openLoad = function() {
		(function() {
			var code = $('main .page[path="open"] [name="gjc"]').val().trim().toUpperCase();
			if (!code.length) {
				app.ui.notify(1, [2, "Group code empty."]);
				$('main .page[path="open"] [name="gjc"]').focus();
			} else if (!/^[A-Z0-9]{6}$/.test(code)) {
				app.ui.notify(1, [2, "Invalid group code."]);
				$('main .page[path="open"] [name="gjc"]').focus();
			} else {
				btnAction.freeze();
				history.pushState(null, null, "/t/PBL/v2/group/"+code+"/edit");
				getStatus();
			}
		}()); return false;
	}
	var un2group = function(dat) {
		if (typeof dat.message !== "undefined") dat.message.forEach(em => app.ui.notify(1, em));
		if (dat.isGrouped) {
			sv.status = dat;
			sv.code = dat.code;
			initialRender([null]);
		}
	}, load_work_status = async function() {
		await ajax(cv.API_URL+"group-status", {type: "work", act: "file", param: {code: sv.code}}).then(function(dat) {
			// Assignments
			if (dat) Object.keys(dat).forEach(ew => {
				if (!/^n\d+$/.test(ew)) {
					$('main .page[path="submissions"] .work output[name="'+ew+'"]')
						.attr("class", dat[ew] ? "y" : "n")
						.val(dat[ew] ? "ส่งแล้ว" : "ไม่มีไฟล์");
					var action = (dat[ew] ? cv.HTML["work-act"](ew)
						: cv.HTML["uploadButton"](ew));
					$('main .page[path="submissions"] .work [data-work="'+ew+'"]')
						.html(action)
						.attr("class", "group center"+(dat[ew] ? " action" : ""));
				}
			}); else sys.auth.orize(true, true);
			sv.workStatus = dat;
		});
	}, load_groupInfo = async function() {
		await ajax(cv.API_URL+"group-information", {type: "group", act: "title", param: {code: sv.code}}).then(async function(dat) {
			if (typeof dat.isGrouped !== "undefined" && !dat.isGrouped) initialRender([null, null, 0]); else {
				if (dat.score) {
					$('main .page[path="information"] .score').fadeIn();
					$('main .page[path="information"] .score [name="net"]')
						.val(dat.score)
						.attr("class", "color-"+(" rrrog"[dat.score]));
					$('main .page[path="information"] .score [name="ppr"]').val(dat.score_paper != null ? dat.score_paper+" " : "ไม่มี");
					$('main .page[path="information"] .score [name="pst"]').val(dat.score_poster != null ? dat.score_poster+" " : "ไม่มี");
					$('main .page[path="information"] .score [name="act"]').val(dat.score_activity != null ? dat.score_activity+" " : "ไม่มี");
				} else {
					$('main .page[path="information"] .score').fadeOut();
					$('main .page[path="information"] .score [name="net"]')
						.val("").removeAttr("class");
					$('main .page[path="information"] .score:where([name="ppr"], [name="pst"], [name="act"])').val("");
				} ["score", "score_paper", "score_poster", "score_activity"].forEach(es => delete dat[es]);
				Object.keys(dat).forEach(ei => $('main .page[path="information"] [name="'+ei+'"]').val(dat[ei] || "") );
				sv.state["loadInfoOver"] = true; checkUnsavedPage(sv.current["page"]);
				var advs = [dat["adv1"], dat["adv2"], dat["adv3"]].filter(ea => ea != null);
				if (advs.length) await ajax(cv.API_URL+"information", {type: "person", act: "teacher", param: advs.join(",")}).then(function(dat2) {
					Object.keys(dat2.list).forEach(et =>
						$('main .page[path="information"] [name="adv'+(advs.indexOf(et) + 1).toString()+'"] + input').val(dat2.list[et])
					); for (let ti = Object.keys(dat2.list).length+1; ti <= 3; ti++)
						$('main .page[path="information"] [name="adv'+ti.toString()+'"] + input').val("");
				}); // Fill empty teachers
				else for (let ti = 1; ti <= 3; ti++)
					$('main .page[path="information"] [name="adv'+ti.toString()+'"] + input').val("");
			} return dat;
		}).then(function(dat) {
			if (dat) $("main .pages .page.current button").attr("disabled", "");
		});
	}, update_groupInfo = function() {
		(async function() {
			var data = { code: sv.code,
				nameth: $('main .page[path="information"] [name="nameth"]').val().trim().replaceAll("เเ", "แ"),
				nameen: $('main .page[path="information"] [name="nameen"]').val().trim().replaceAll("เเ", "แ"),
				adv1: $('main .page[path="information"] [name="adv1"]').val(),
				adv2: $('main .page[path="information"] [name="adv2"]').val(),
				adv3: $('main .page[path="information"] [name="adv3"]').val(),
				type: $('main .page[path="information"] [name="type"]').val()
			};
			if (data.nameth.length && !/^[ก-๛0-9A-Za-z ()[\]{}\-!@#$%.,/&*+_?|]{3,150}$/.test(data.nameth)) {
				app.ui.notify(1, [2, "Invalid Thai project name."]);
				$('main .page[path="information"] [name="nameth"]').focus();
			} else if (data.nameen.length && !/^[A-Za-z0-9ก-๛ ()[\]{}\-!@#$%.,/&*+_?|]{3,150}$/.test(data.nameen)) {
				app.ui.notify(1, [2, "Invalid English project name."]);
				$('main .page[path="information"] [name="nameen"]').focus();
			} else if (!" ABCDEFGHIJKLM".includes(data.type)) {
				app.ui.notify(1, [2, "Invalid project type."]);
				$('main .page[path="information"] [name="type"]').focus();
			} else {
				btnAction.freeze();
				await ajax(cv.API_URL+"group-action", {type: "update", act: "information", param: data}).then(function(dat) {
					if (typeof dat.isGrouped !== "undefined" && !dat.isGrouped) initialRender([null, null, 0]); else {
						if (typeof dat.message !== "undefined") dat.message.forEach(em => app.ui.notify(1, em));
					}
				}).then(btnAction.unfreeze).then(function() {
					$("main .pages .page.current button").attr("disabled", "");
					sv.state["loadInfoOver"] = true; checkUnsavedPage(sv.current["page"]);
				});
			}
		}()); return false;
	}, load_member = async function(andSettings=true) {
		await ajax(cv.API_URL+"group-information", {type: "group", act: "member", param: {code: sv.code}}).then(async function(dat) {
			if (typeof dat.isGrouped !== "undefined" && !dat.isGrouped) initialRender([null, null, 0]); else {
				// Member names
				await ajax(cv.API_URL+"information", {type: "person", act: "student", param: dat.list.join(",")}).then(function(dat2) {
					var index = 1, listBody = ""; sv.isLeader = dat2.list[0].ID;
					dat2.list.forEach(es => {
						listBody += '<tr><td>'+index.toString()+'.</td><td>'+es.fullname+' (<a href="/user/'+es.ID+'" target="_blank" draggable="false">'+es.nickname+'</a>)</td><td>เลขที่ '+es.number+'</td><td>';
						if (index++ == 1) listBody += '<a role="button" class="default pill" disabled>หัวหน้ากลุ่ม</a>';
						else listBody += cv.HTML["memberAction"](es.ID);
						listBody += '</td></tr>';
					}); // Check empty
					if (dat.list.length < parseInt(dat.settings["maxMember"])) listBody += cv.HTML["newMember"](index);
					$('main .page[path="member"] .list tbody').html(listBody);
				}); // Settings
				sv.groupSettings = dat.settings;
				if (andSettings) { // Lookup each
					if (sv.state["loadSettingsOver"]) {
						$('main .page[path] .settings [onClick^="PBL.save.settings"]').attr("disabled", "");
						cv.mbr_settings.slice(0, 2).forEach(es => {
							d.querySelector('main .page[path] .settings [name="'+es+'"]').checked = (sv.groupSettings[es] == "Y");
						}); cv.mbr_settings.slice(2, 3).forEach(es => {
							$('main .page[path] .settings [name="'+es+'"]').val(sv.groupSettings[es]);
						}); sv.state["loadSettingsOver"] = true; checkUnsavedPage(sv.current["page"]);
					} else $('main .page[path] .settings').notify({
						title: "You have unsaved changes."
					}, {
						className: "warning",
						elementPosition: "bottom center",
						autoHideDelay: 60000,
						clickToHide: false,
						style: "PBL-unsaved"
					});
				}
			}
		});
	}, leave_group = async function(destroy, param=null) {
		if (destroy || param == sv.isLeader) {
			if (confirm(cv.MSG["delete-group"])) await ajax(cv.API_URL+"group-action", {type: "delete", act: "void", param: {code: sv.code}}).then(function(dat) {
				if (typeof dat.message !== "undefined") dat.message.forEach(em => app.ui.notify(1, em));
				if (dat) {
					history.pushState(null, null, "/t/PBL/v2/group/home");
					initialRender([null, null, 1]);
				}
			});
		} else if (confirm(cv.MSG["delete-mbr"])) await ajax(cv.API_URL+"group-action", {type: "delete", act: "member", param: {code: sv.code, mbr: param}}).then(function(dat) {
			if (dat) {
				if (typeof dat.message !== "undefined") dat.message.forEach(em => app.ui.notify(1, em));
				load_member(false);
			}
		});
	}, kick_member = function(studentID) {
		leave_group(false, studentID);
	}, promote_member = async function(studentID) {
		if (studentID == sv.isLeader) app.ui.notify(1, ([1, "You are already a group leader."]));
		else if (confirm(cv.MSG["newLeader"])) await ajax(cv.API_URL+"group-action", {type: "update", act: "leader", param: {code: sv.code, mbr: studentID}}).then(function(dat) {
			if (dat) {
				if (typeof dat.message !== "undefined") dat.message.forEach(em => app.ui.notify(1, em));
				load_member(true);
			}
		});
	}, update_groupSetting = async function(settingName) {
		if (typeof sv.groupSettings[settingName] === "undefined") app.ui.notify(1, [3, "Setting not found."]);
		else {
			var newValue = (function(getName) {
				if (cv.mbr_settings.slice(0, 2).includes(getName)) return (d.querySelector('main .page[path] .settings [name="'+getName+'"]').checked ? "Y" : "N");
				else if (cv.mbr_settings.slice(2, 3).includes(getName)) return $('main .page[path] .settings [name="'+getName+'"]').val();
				return [null];
			}(settingName)),
				button = $('main .page[path] .settings [onClick="PBL.save.settings(\''+settingName+'\')"]');
			if (newValue == [null]) app.ui.notify(1, [3, "Error validating your setting."]);
			if (sv.groupSettings[settingName] == newValue) {
				app.ui.notify(1, [1, "No change applies."]);
				button.attr("disabled", "");
			} else await ajax(cv.API_URL+"group-information", {type: "settings", act: "member", param: [settingName, newValue, sv.code]}).then(function(dat) {
				if (typeof dat.message !== "undefined") dat.message.forEach(em => app.ui.notify(1, em));
				if (dat) {
					sv.groupSettings[settingName] = newValue;
					button.attr("disabled", "");
					switch (settingName) {
						case cv.mbr_settings[2]: load_member(false); break;
					}
				}
			});
		}
	}, openUploadTab = function(workType) {
		if (workType == false) {
			PBL.save.file(false);
			return;
		} sv.current["workType"] = workType;
		app.ui.lightbox.open("mid", {allowclose: true, html: '<iframe src="/t/PBL/v2/group/'+sv.code+'/upload" style="width:90vw;height:712px;border:none">Loading...</iframe>'});
	}, recieve_file = function(status) {
		app.ui.lightbox.close();
		if (status == "complete") load_work_status();
		sv.current["workType"] = "";
	}, preview_file = function(type) {
		app.ui.lightbox.open("mid", {title: "ไฟล์"+cv.workload[type], allowclose: true, html:
			'<iframe src="preview?file='+type+'&code='+sv.code+'" style="width:90vw;height:80vh;border:none">Loading...</iframe>'
		});
	}, download_file = async function(type) {
		var button = $('main .page[path="submissions"] .work [data-work="'+type+'"] button[onClick*="download"]');
		await ajax(cv.API_URL+"group-status", {type: "get", act: "fileLink", param: {code: sv.code, type: type}}).then(function(dat) {
			if (typeof dat.isGrouped !== "undefined" && !dat.isGrouped) initialRender([null, null, 0]); else
			if (dat.download) {
				d.querySelector('main iframe[name="dlframe"]').src = dat.download;
				setTimeout(function() { button.removeAttr("disabled"); }, 5000);
			} else {
				button.removeAttr("disabled");
				app.ui.notify(1, [3, "There's a problem downloading your file."]);
			}
		}); button.attr("disabled", "");
	}, print_file = async function(type) {
		var button = $('main .page[path="submissions"] .work [data-work="'+type+'"] button[onClick*="print"]');
		await ajax(cv.API_URL+"group-status", {type: "get", act: "fileLink", param: {code: sv.code, type: type}}).then(function(dat) {
			if (typeof dat.isGrouped !== "undefined" && !dat.isGrouped) initialRender([null, null, 0]); else {
				if (dat.print) {
					dat.print = atob(dat.print);
					(/.+\.pdf$/.test(dat.print) ? printJS(dat.print) : printJS(dat.print, "image"));
				} else app.ui.notify(1, [3, "There's a problem preparing your file for print."]);
				setTimeout(function() { button.removeAttr("disabled"); }, 500);
			}
		}); button.attr("disabled", "");
	}, remove_file = async function(type) {
		if (confirm(cv.MSG["del-work"](cv.workload[type]))) {
			var button = $('main .page[path="submissions"] .work [data-work="'+type+'"] button[onClick*="remove"]');
			await ajax(cv.API_URL+"group-main", {type: "work", act: "remove", param: {code: sv.code, type: type}}).then(function(dat) {
				if (typeof dat.isGrouped !== "undefined" && !dat.isGrouped) initialRender([null, null, 0]); else
				if (!dat) button.removeAttr("disabled");
				load_work_status();
			}); button.attr("disabled", "");
		}
	}, confirmLeave = function(page) {
		if (!sv.history["unsavedPage"].length) $(window).bind("beforeunload", function() {
			if (!sv.history["unsavedPage"].includes(sv.current["page"]))
				PBL.openPage(sv.history["unsavedPage"][sv.history["unsavedPage"].length - 1]);
			return null;
		}); if (!sv.history["unsavedPage"].includes(page)) sv.history["unsavedPage"].push(page);
	}, checkUnsavedPage = function(page) {
		/* var flush = true;
		cv.mbr_settings.forEach(sn => { flush = (flush && sv.state[sn]); });
		if (flush) app.io.confirm("unleave"); */
		let pagePos = sv.history["unsavedPage"].indexOf(page);
		if (pagePos > -1) sv.history["unsavedPage"].splice(pagePos, 1);
		if (!sv.history["unsavedPage"].length) app.io.confirm("unleave");
	}, addMember = async function(selectNew=false) {
		if (selectNew) pUI.select.member(sv.status.grade, sv.status.room);
		else {
			var studentID = d.querySelector('input[name="temp_mbr"]').value;
			$('input[name="temp_mbr"], input[name="temp_mbr"] + input[readonly]').val("");
			if (studentID.length) {
				if (!/^[1-9]\d{4}$/.test(studentID)) {
					app.ui.notify(1, [2, "Invalid student selected."]);
					return;
				} // Process
				await ajax(cv.API_URL+"group-main", {type: "member", act: "invite", param: {code: sv.code, mbr: studentID}}).then(function(dat) {
					if (dat) {
						load_member(false);
						app.ui.notify(1, [0, "New member ("+studentID+") added."]);
					}
				});
			} // else // isDiscard
		}
	};
	return {
		init: initialize,
		openPage: load_page,
		help: helpCentre,
		createGroup: group_create,
		openGroup: group_openLoad,
		terminate: leave_group,
		kick: kick_member,
		setLeader: promote_member,
		upload: openUploadTab,
		save: {
			info: update_groupInfo,
			settings: update_groupSetting,
			file: recieve_file
		}, file: {
			preview: preview_file,
			print: print_file,
			download: download_file,
			remove: remove_file
		}, addMember: addMember,
		// Export Internal
		btnAction: btnAction,
		groupCode: () => sv.code,
		pageURL: () => sv.current["page"],
		uploadType: () => sv.current["workType"],
		setState: (name, value) => sv.state[name] = value,
		confirmLeave: confirmLeave
	};
}(document)); top.PBL = PBL;