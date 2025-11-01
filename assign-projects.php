<?php
	$APP_RootDir = str_repeat("../", substr_count($_SERVER["PHP_SELF"], "/"));
	require($APP_RootDir."private/script/start/PHP.php");
	$header["title"] = "Assign committee projects";
	$header["desc"] = "Assign projects to commitee for marking and grading";

	require_once($APP_RootDir."private/script/lib/TianTcl/various.php");
	require_once($APP_RootDir."$APP_CONST[publicDir]/resource/php/core/config.php");
	require($APP_RootDir."private/script/function/database.php");
	function is_branch_head() {
		global $APP_USER, $APP_DB;
		if (empty($APP_USER) || !isset($_SESSION["stif"]["t_year"])) TianTcl::http_response_code(907);
		$get = $APP_DB[0] -> query("SELECT allow,isHead FROM PBL_cmte WHERE tchr='$APP_USER' AND year=".$_SESSION["stif"]["t_year"]." ORDER BY allow DESC,isHead DESC LIMIT 1");
		if (!$get || !$get -> num_rows) return false;
		$read = $get -> fetch_array(MYSQLI_ASSOC);
		return ($read["allow"] == "Y" && $read["isHead"] == "Y");
	} if (!has_perm("PBL") && !is_branch_head()) TianTcl::http_response_code(901);
	$APP_DB[0] -> close();
	$APP_PAGE -> print -> head();
?>
<style type="text/css">
	app[name=main] .assign {
		--tabAmt: 3;
		margin-bottom: 0;
		/* border-radius: 10px; box-shadow: 0 0 var(--shd-big) var(--fade-black-7); */
	}
	app[name=main] .rights {
		/* height: 136px; */
		border-radius: 0 0 10px 10px;
		overflow-y: hidden;
	}
	/* app[name=main] .rights > table { margin: -50px 0; } */
	app[name=main] .assign > .space > .form[order] { margin-top: 10px; }
	app[name=main] .assign > .tabs {
		margin: 0;
		border-radius: 10px 10px 0 0;
		display: flex; overflow: hidden;
	}
	app[name=main] .assign > .tabs div {
		padding: 7.5px 10px;
		width: 100%; height: 30px;
		line-height: 30px; text-align: center;
		cursor: pointer; transition: var(--time-tst-xfast) ease;
	}
	app[name=main] .assign > .tabs div:hover { background-color: var(--fade-white-6); }
	app[name=main] .assign > .tabs div.active {
		background-color: var(--fade-black-8);
		/* border-radius: 10px 10px 0 0; */
		pointer-events: none;
	}
	app[name=main] .assign > .tabs + span.bar-responsive {
		margin-bottom: 0;
		transform: translate(calc(100% * var(--show)), -100%);
		width: calc(100% / var(--tabAmt)); height: 2.5px;
		background-color: var(--clr-pp-cyan-800);
		display: block; transition: var(--time-tst-fast);
		pointer-events: none;
	}
	/* app[name=main] .assign > .tabs:active + span.bar-responsive { animation: bar_moving var(--time-tst-fast) ease 1; } */
	@keyframes bar_moving {
		0%, 100% { width: calc(100% / var(--tabAmt)); }
		5%, 95% { width: calc(100% / var(--tabAmt) * 1.75); }
		50% { width: calc(100% / var(--tabAmt) * 0.25); }
	}
	app[name=main] .assign > .space { transform: translateY(-2.5px); }
	app[name=main] .assign > .space div > p { margin: 7.5px 0 0; }
	app[name=main] .projects tbody[data-type] { border-top: 2px solid var(--clr-pp-grey-500) !important; }
	app[name=main] .projects .proj-code {
		font-size: .7em; letter-spacing: .25px;
		font-family: "Roboto Mono", "IBM Plex Mono", monospace;
	}
	app[name=main] .projects tbody td:nth-child(2) { max-width: 250px; }
	app[name=main] .projects [data-holding] label:has(button) {
		padding: 2.5px 5px;
		font-size: .9em;
	}
	app[name=main] .projects [data-holding]:not(:hover) label button { padding-left: 0 !important; padding-right: 0 !important; }
	app[name=main] .projects [data-holding] label button {
		width: 0; transform: translateX(2.5px);
		transition: var(--time-tst-fast);
	}
	app[name=main] .projects [data-holding]:hover label button {
		margin-right: -2.5px;
		width: auto;
	}
	app[name=main] .projects [data-holding] .clear-all { align-self: center; }
</style>
<script type="text/javascript">
	const TRANSLATION = location.pathname.substring(1).replace(/\/$/, "").replaceAll("/", "+");
	$(document).ready(function() {
		page.init();
	});
	const page = (function(d) {
		const cv = {
			API_URL: AppConfig.APIbase + "PBL/v1-teacher/",
			branches: {
				<?php foreach (str_split("ABCDEFGHIJKLM") as $et) echo $et.': {EN: "'.pblcode2text($et)["en"].'", TH: "'.pblcode2text($et)["th"]."\"},\n"; ?>
			},
			maxAsgn: 5
		};
		var sv = {
			inited: false,
			tchr: {},
			typeC: "_", typeA: "_"
		};
		var initialize = function() {
			if (sv.inited) return;
			getCommittee();
			// getProjects();
			$("app[name=main] input[name=filter]").on("input change", filter);
			$("app[name=main] select[name=pointer]").on("change", formC.reset);
			$("app[name=main] select[name=bulk]").on("change", formA.reset);
			showTab(1);
			$("app[name=main] main .card > summary").on("click", function() { setTimeout(optionBtnState, 50); });
			sv.inited = true;
		};
		var getCommittee = function() {
			app.Util.ajax(cv.API_URL + "committee", {act: "list", cmd: "names"}).then(function(dat) {
				if (!dat) return;
				var buffer,
					snglList = $("app[name=main] select[name=pointer]"),
					plurList = $("app[name=main] select[name=bulk]");
				Object.keys(dat).forEach(eb => {
					buffer = $(`<optgroup label="${cv.branches[eb][app.settings["lang"]]}"></optgroup>`);
					dat[eb].forEach(ec => {
						buffer.append(`<option value="${ec.impact}" data-branch="${eb}">${ec.name}</option>`);
						if (!(ec.user_reference in sv.tchr)) sv.tchr[ec.user_reference] = ec.name;
						if (!(ec.impact in sv.tchr)) sv.tchr[ec.impact] = ec.name;
					}); snglList.append(buffer);
					buffer.clone().appendTo(plurList);
				}); getProjects();
			});
		},
		getProjects = function() {
			app.Util.ajax(cv.API_URL+"list", {type: "group", act: "assignees"}).then(function(dat) {
				if (!dat) return;
				var ctn = $("app[name=main] .projects table"), list, newCmte = new Set(), bufferC, bufferA;
				ctn.children(":nth-child(n+2)").remove();
				Object.keys(dat).forEach(ec => {
					ctn.append(`<thead><tr><th colspan="4">${cv.branches[ec][app.settings["lang"]]}</th></tr></thead>`);
					list = "";
					Object.keys(dat[ec]).sort().forEach(eg => {
						list += `<tbody data-type="${ec}"><tr><th class="css-text-left" colspan="4">${app.UI.language.getMessage("grade")} ${eg}</th></tr>`;
						dat[ec][eg].forEach(ep => {
							bufferC = ep.cmte == null ?
								'<div class="css-flex center"><button class="lime pill small ripple-click" onClick="page.C.add(this)" hidden><span class="text">select</span></button></div>' :
								`<div class="group"><label for="ref-null">${sv.tchr[ep.cmte]} <button class="black small icon bare action ripple-click" onClick="page.C.unassign(this)"><i class="material-icons">clear</i></button></label></div>`;
							if (ep.step > 1) {
								bufferA = "";
								if (ep.asgn.length) ep.asgn.forEach(ea => {
									bufferA += `<div class="group"><label for="ref-null">${sv.tchr[ea]} <button class="black small icon bare action ripple-click" onClick="page.A.unassign('${ea}', this)"><i class="material-icons">clear</i></button></label></div>`;
								}); if (ep.asgn.length < cv.maxAsgn) bufferA += `<div class="chooser"><button class="teal pill small ripple-click" onClick="page.A.add(this)" style="display: none;"><span class="text">select</span></button></div>`;
								if (ep.asgn.length) bufferA += '<button class="clear-all red hollow tiny icon ripple-click" onClick="page.A.clear(this)" data-title="Unassign all"><i class="material-icons">clear</i></button>';
							} list += `<tr data-step="${ep.step}"><td class="proj-code center select-all">${ep.code}</td><td class="txtoe">${ep.name}</td>`
							list += `<td><div data-holding="cmte:${ep.code}" class="form form-bs inline">${bufferC}</div></td>`;
							if (ep.step > 1) list += `<td><div data-holding="asgn:${ep.code}" class="form form-bs inline css-flex-gap-5">${bufferA}</div></td></tr>`;
							else list += `<td class="css-bg-gray"></td></tr>`;
							/* if (ep.cmte != null) {
								if (!(ep.cmte in sv.tchr)) {
									sv.tchr[ep.cmte] = {types: new Set()};
									newCmte.add(ep.cmte);
								} sv.tchr[ep.cmte].types.add(ec);
							} ep.asgn.forEach(ea => {
								if (!(ea in sv.tchr)) {
									sv.tchr[ea] = {types: new Set()};
									newCmte.add(ea);
								} sv.tchr[ea].types.add(ec);
							}); */
						}); list += "</tbody>";
					}); ctn.append(list);
				}); app.UI.refineElements();
				app.UI.language.load();
				$("app[name=main] main .loading").toggle("blind", function() {
					this.remove();
					$("app[name=main] main .afterLoad").fadeIn(1e3, function() { $(this).removeClass("afterLoad"); });
				});
			});
		},
		filter = function() {
			var query = $("app[name=main] input[name=filter]").val();
			w3.filterHTML("app[name=main] .projects", "tr:not(:has(th))", query);
		},
		showTab = function(what) {
			var tab = (parseInt(what) - 1).toString();
			$("app[name=main] .assign > .tabs div.active").removeClass("active");
			$(`app[name=main] .assign > .tabs div[onClick="page.showTab(${what})"]`).addClass("active");
			$("app[name=main] .assign > .tabs + span.bar-responsive").css("--show", tab);
			$("app[name=main] .assign > .space > div").hide();
			$(`app[name=main] .assign > .space > div[order="${tab}"]`).show();
			optionBtnState();
		},
		optionBtnState = function() {
			const currentTab = parseInt($("app[name=main] .assign > .tabs + span.bar-responsive").css("--show")) + 1,
				isShowingForm = d.querySelector("app[name=main] main .card").open;
			$(`app[name=main] .projects [data-type]:not([data-type=${sv.typeC}]) [data-holding^="cmte:"] button:where(.lime, .teal)`).attr("hidden", "");
			$(`app[name=main] .projects [data-type=${sv.typeC}] [data-holding^="cmte:"] button:where(.lime, .teal)`).removeAttr("hidden");
			$(`app[name=main] .projects [data-type]:not([data-type=${sv.typeA}]) [data-holding^="asgn:"] button:where(.teal, .lime)`).hide();
			$(`app[name=main] .projects [data-type=${sv.typeA}] [data-holding^="asgn:"] button:where(.teal, .lime)`).show();
			if (!isShowingForm) {
				$(`app[name=main] .projects [data-holding^="cmte:"] button:where(.lime, .teal)`).parent().attr("hidden", "");
				$(`app[name=main] .projects [data-holding^="asgn:"] .chooser`).hide();
				$("app[name=main] .projects [data-step=1]").show();
			} else switch (currentTab) {
				case 1: {
					$(`app[name=main] .projects [data-holding^="cmte:"] button:where(.lime, .teal)`).parent().attr("hidden", "");
					$(`app[name=main] .projects [data-holding^="asgn:"] .chooser`).hide();
					$("app[name=main] .projects [data-step=1]").show();
				} break;
				case 2: {
					$(`app[name=main] .projects [data-holding^="cmte:"] button:where(.lime, .teal)`).parent().removeAttr("hidden");
					$(`app[name=main] .projects [data-holding^="asgn:"] .chooser`).hide();
					$("app[name=main] .projects [data-step=1]").show();
				} break;
				case 3: {
					$(`app[name=main] .projects [data-holding^="cmte:"] button:where(.lime, .teal)`).parent().attr("hidden", "");
					$(`app[name=main] .projects [data-holding^="asgn:"] .chooser`).show();
					$("app[name=main] .projects [data-step=1]").hide();
				} break;
			}
		};
		var formC = (function() {
			var fv = {targetProj: []};
			var buttonState = function() {
				d.querySelector("app[name=main] .set-cmte").disabled = !fv.targetProj.length;
				d.querySelector('app[name=main] [order="1"] button.secondary').disabled = !fv.targetProj.length;
			},
			reset = function() {
				var referee = $("app[name=main] select[name=pointer] option:checked"),
					branch = referee.attr("data-branch");
				if (fv.targetProj.length && fv.type != branch && !confirm(app.UI.language.getMessage("start-over"))) {
					$("app[name=main] select[name=pointer]").val(fv.targetRfr.val());
					return;
				} if (fv.type != branch) {
					fv.type = sv.typeC = branch;
					clearSelected(true);
				} fv.targetRfr = referee;
			},
			clearSelected = function(imediately = false) {
				fv.targetProj = [];
				$(`app[name=main] .projects [data-type] [data-holding^="cmte:"] button.teal`).removeAttr("disabled").toggleClass("lime teal");
				optionBtnState();
				setTimeout(() => {
					$("app[name=main] .to-proj button").fadeOut(1e3, function() { this.remove(); });
				}, imediately ? 0 : 250);
				buttonState();
			},
			add = function(me) {
				var code = $(me.parentNode.parentNode).attr("data-holding").substring(5);
				var nameTag = $(`<button name="proj:${code}:cmte" class="black small hollow pill css-overflow-hidden" onClick="page.C.remove(this)" style="display: none;">${code}</button>`);
				$("app[name=main] .to-proj").append(nameTag);
				setTimeout(() => nameTag.toggle("clip"), 250);
				fv.targetProj.push(code);
				$(me).attr("disabled", "").toggleClass("lime teal");
				buttonState();
			},
			remove = function(me) {
				var reference = me.name.substring(5, 11);
				fv.targetProj.splice(fv.targetProj.indexOf(reference), 1);
				me = $(me);
				me.css("width", me.outerWidth() - 2)
					.animate({width: 0, padding: 0, borderWidth: 0, marginLeft: -10}, 5e2, "linear", function() {
						setTimeout(() => me.remove(), 1e2);
					});
				$(`app[name=main] .projects [data-holding="cmte:${reference}"] button.teal`).removeAttr("disabled").toggleClass("lime teal");
				buttonState();
			},
			assign = function() {
				if (!confirm(`${app.UI.language.getMessage("enlist-1")} ${fv.targetProj.length} ${app.UI.language.getMessage("enlist-2")}${fv.targetRfr.text()}${app.UI.language.getMessage("enlist-3")}?`)) return;
				$("app[name=main] .set-cmte").attr("disabled", "");
				app.Util.ajax(cv.API_URL + "committee", {act: "assign", cmd: "referee", param: {
					committee: fv.targetRfr.val(),
					projects: btoa(fv.targetProj.join("-")).replace(/=+$/, "")
				}}).then(function(dat) {
					$("app[name=main] .set-cmte").removeAttr("disabled");
					if (!dat) return;
					clearSelected();
					app.UI.notify(0, `${app.UI.language.getMessage("enlist-5")}${fv.targetRfr.text()}${app.UI.language.getMessage("enlist-3")}`);
					// Display updates
					dat.updated.forEach(es => {
						var holder = $(`app[name=main] .projects [data-holding="cmte:${es}"] > *`);
						holder.fadeOut(5e2, function() {
							holder.replaceWith(`<div class="group" style="display: none;"><label for="ref-null">${sv.tchr[dat.referee]} <button class="black small icon bare action ripple-click" onClick="page.C.unassign(this)"><i class="material-icons">clear</i></button></label></div>`);
							$(`app[name=main] .projects [data-holding="cmte:${es}"] .group`).fadeIn({complete: app.UI.refineElements});
						});
					});
				});
			},
			unassign = function(me) {
				var container = $(me.parentNode.parentNode.parentNode);
				var project = container.attr("data-holding").substring(5);
				me.disabled = true;
				app.Util.ajax(cv.API_URL + "committee", {act: "revoke", cmd: "referee", param: project}).then(function(dat) {
					me.disabled = false;
					if (!dat) return;
					app.UI.notify(0, `${app.UI.language.getMessage("delist-1")} ${project}${app.UI.language.getMessage("delist-3")}`);
					// Display updates
					var holder = $(`app[name=main] .projects [data-holding="cmte:${project}"] .group`);
					holder.fadeOut(5e2, function() {
						holder.replaceWith(`<div class="css-flex center"><button class="lime pill small ripple-click" onClick="page.C.add(this)" hidden><span class="text">select</span></button></div>`);
						app.UI.refineElements();
						app.UI.language.load();
						optionBtnState();
					});
				});
			},
			restart = function() {
				if (!confirm(app.UI.language.getMessage("deselect-all"))) return;
				clearSelected(true);
			};
			return {
				reset,
				add, remove,
				assign, unassign,
				restart
			};
		})(),
		formA = (function() {
			var fv = {targetProj: []};
			var buttonState = function() {
				d.querySelector("app[name=main] .asgn-cmte").disabled = !fv.targetProj.length;
				d.querySelector('app[name=main] [order="2"] button.secondary').disabled = !fv.targetProj.length;
			},
			reset = function() {
				var referee = $("app[name=main] select[name=bulk] option:checked"),
					branch = referee.attr("data-branch");
				if (fv.targetProj.length && fv.type != branch && !confirm(app.UI.language.getMessage("start-over"))) {
					$("app[name=main] select[name=bulk]").val(fv.targetRfr.val());
					return;
				} if (fv.type != branch) {
					fv.type = sv.typeA = branch;
					clearSelected(true);
				} fv.targetRfr = referee;
			},
			clearSelected = function(imediately = false) {
				fv.targetProj = [];
				$(`app[name=main] .projects [data-type] [data-holding^="asgn:"] button.lime`).removeAttr("disabled").toggleClass("teal lime");
				optionBtnState();
				setTimeout(() => {
					$("app[name=main] .with-proj button").fadeOut(1e3, function() { this.remove(); });
				}, imediately ? 0 : 250);
				buttonState();
			},
			add = function(me) {
				var code = $(me.parentNode.parentNode).attr("data-holding").substring(5);
				var nameTag = $(`<button name="proj:${code}:asgn" class="black small hollow pill css-overflow-hidden" onClick="page.A.remove(this)" style="display: none;">${code}</button>`);
				$("app[name=main] .with-proj").append(nameTag);
				setTimeout(() => nameTag.toggle("clip"), 250);
				fv.targetProj.push(code);
				$(me).attr("disabled", "").toggleClass("teal lime");
				buttonState();
			},
			remove = function(me) {
				var reference = me.name.substring(5, 11);
				fv.targetProj.splice(fv.targetProj.indexOf(reference), 1);
				me = $(me);
				me.css("width", me.outerWidth() - 2)
					.animate({width: 0, padding: 0, borderWidth: 0, marginLeft: -10}, 5e2, "linear", function() {
						setTimeout(() => me.remove(), 1e2);
					});
				$(`app[name=main] .projects [data-holding="asgn:${reference}"] button.lime`).removeAttr("disabled").toggleClass("teal lime");
				buttonState();
			},
			assign = function() {
				if (!confirm(`${app.UI.language.getMessage("enlist-1")} ${fv.targetProj.length} ${app.UI.language.getMessage("enlist-2")}${fv.targetRfr.text()}${app.UI.language.getMessage("enlist-4")}?`)) return;
				$("app[name=main] .asgn-cmte").attr("disabled", "");
				app.Util.ajax(cv.API_URL + "committee", {act: "assign", cmd: "project", param: {
					committee: fv.targetRfr.val(),
					projects: btoa(fv.targetProj.join("-")).replace(/=+$/, "")
				}}).then(function(dat) {
					$("app[name=main] .asgn-cmte").removeAttr("disabled");
					if (!dat) return;
					// clearSelected();
					app.UI.notify(0, `${app.UI.language.getMessage("enlist-5")}${fv.targetRfr.text()}${app.UI.language.getMessage("enlist-4")}`);
					// Display updates
					dat.updated.forEach(es => {
						remove(d.querySelector(`app[name=main] .with-proj button[name="proj:${es}:asgn"]`));
						var holder = $(`app[name=main] .projects [data-holding="asgn:${es}"]`),
							nameTag = $(`<div class="group" style="display: none;"><label for="ref-null">${sv.tchr[dat.referee]} <button class="black small icon bare action ripple-click" onClick="page.A.unassign('${dat.referee}', this)"><i class="material-icons">clear</i></button></label></div>`);
						if (!holder.children(".group").length) holder.append('<button class="clear-all red hollow tiny icon ripple-click" onClick="page.A.clear(this)" data-title="Unassign all"><i class="material-icons">clear</i></button>');
						nameTag.insertBefore(holder.children(".chooser")).toggle("blind", function() {
							app.UI.refineElements();
							app.UI.language.load();
						});
						if (holder.children(".group").length == cv.maxAsgn)
							holder.children(".chooser").fadeOut(5e2, function() { this.remove(); });
					});
				});
			},
			unassign = function(impact, me) {
				var container = $(me.parentNode.parentNode.parentNode);
				var project = container.attr("data-holding").substring(5);
				me.disabled = true;
				app.Util.ajax(cv.API_URL + "committee", {act: "revoke", cmd: "project", param: {
					project, impact
				}}).then(function(dat) {
					me.disabled = false;
					if (!dat) return;
					app.UI.notify(0, `${app.UI.language.getMessage("delist-2")} ${project}${app.UI.language.getMessage("delist-3")}`);
					// Display updates
					var holder = $(`app[name=main] .projects [data-holding="asgn:${project}"]`);
					if (holder.children(".group").length == cv.maxAsgn) {
						$(`<div class="chooser"><button class="teal pill small ripple-click" onClick="page.A.add(this)" style="display: none;">select</button></div>`).insertBefore(holder.children(".clear-all"));
						app.UI.refineElements();
						app.UI.language.load();
						optionBtnState();
					} me = $(me.parentNode.parentNode);
					me.css("width", me.outerWidth() - 2)
						.animate({width: 0, padding: 0, borderWidth: 0, marginLeft: -5}, 5e2, "linear", function() {
							setTimeout(() => me.remove(), 1e2);
						});
					if (!(holder.children(".group").length - 1))
						holder.children(".clear-all").fadeOut(5e2, function() { this.remove(); });
				});
			},
			clear = function(me, confirmed=null) {
				var project = $(me.parentNode).attr("data-holding").substring(5);
				if (confirmed == false) return app.UI.modal.close();
				if (confirmed == null && (app.IO.kbd.ctrl() || app.IO.kbd.alt())) return;
				if (confirmed == null && !app.IO.kbd.shift()) {
					app.UI.notify(1, app.UI.language.getMessage("hold-shift"), 15);
					return app.UI.modal(`${app.UI.language.getMessage("exonerate-1")} ${project}`, "confirm", {
						choices: [
							[app.UI.language.getMessage("option-yes"), true],
							[app.UI.language.getMessage("option-no"), false]
						], to: proceed => { clear(me, proceed); }
					});
				} if (confirmed != false) app.Util.ajax(cv.API_URL + "committee", {act: "revoke", cmd: "assignee", param: project}).then(function(dat) {
					me.disabled = false;
					if (!dat) return;
					app.UI.notify(0, `${app.UI.language.getMessage("exonerate-2")} ${project}${app.UI.language.getMessage("delist-3")}`);
					// Display updates
					var holder = $(`app[name=main] .projects [data-holding="asgn:${project}"]`);
					holder.children(":not(.chooser)").fadeOut(5e2, function() { this.remove(); });
					setTimeout(function() {
						if (holder.children(".chooser").length) return;
						holder.append(`<div class="chooser"><button class="teal pill small ripple-click" onClick="page.A.add(this)" style="display: none;">select</button></div>`);
						app.UI.refineElements();
						app.UI.language.load();
						optionBtnState();
					}, 5e2); 
				});
			},
			restart = function() {
				if (!confirm(app.UI.language.getMessage("deselect-all"))) return;
				clearSelected(true);
			};
			return {
				reset,
				add, remove,
				assign, unassign, clear,
				restart
			};
		})();
		return {
			init: initialize,
			showTab,
			C: formC,
			A: formA
		}
	}(document));
</script>
<script type="text/javascript" src="<?=$APP_CONST["baseURL"]?>resource/js/extend/find-search.js"></script>
<script type="text/javascript" src="<?=$APP_CONST["cdnURL"]?>static/script/lib/w3.min.js"></script>
<?php $APP_PAGE -> print -> nav(); ?>
<main>
	<section class="container">
		<h2><?=$header["title"]?></h2>
		<details class="card message cyan">
			<summary>มอบหมายคณะกรรมการ</summary>
			<div class="assign">
				<div class="tabs">
					<div onClick="page.showTab(1)" class="ripple-click"><span class="text">การแสดงผล</span></div>
					<div onClick="page.showTab(2)" class="ripple-click"><span class="text">ขั้นที่ 1</span></div>
					<div onClick="page.showTab(3)" class="ripple-click"><span class="text">ขั้นที่ 2</span></div>
				</div><span class="bar-responsive"></span>
				<div class="space">
					<div order="0">
						<div class="rights table static">
							<table><thead>
								<tr>
									<th rowspan="2">เงื่อนไข<br>(มีผลเฉพาะขั้นนั้น)</th>
									<th colspan="3">สิทธิ์การมองเห็น</th>
								</tr>
								<tr>
									<th>หัวหน้างาน</th>
									<th>หัวหน้าสาขา</th>
									<th>กรรมการสาขา</th>
								</tr>
							</thead><tbody>
								<tr>
									<td right>ไม่มีการกำหนดกรรมการ</td>
									<td center><i class="material-icons">check</i></td>
									<td center><i class="material-icons">check</i></td>
									<td center>ทุกคนในสาขา</td>
								</tr>
								<tr>
									<td right>กำหนดกรรมการอย่างน้อย 1 คน</td>
									<td center><i class="material-icons">check</i></td>
									<td center><i class="material-icons">check</i></td>
									<td center>เฉพาะคนที่ได้รับมอบหมาย</td>
								</tr>
							</tbody></table>
						</div>
					</div>
					<div order="1" class="form form-bs">
						<div class="table list"><table><tbody>
							<tr>
								<th class="css-text-left">1) เลือกกรรมการ</th>
								<td><div class="group">
									<select name="pointer">
										<option value selected disabled>— กรุณาเลือก —</option>
									</select>
								</div></td>
							</tr>
							<tr>
								<th class="css-text-left">2) เลือกโครงงาน<br>กดจากตารางด่านล่าง</th>
								<td>
									<div class="css-flex css-flex css-flex-split">
										<p>โครงงานละไม่เกิน 1 คน</p>
										<button class="secondary small ripple-click" onClick="page.C.restart()" disabled><span class="text">ลบทั้งหมด</span></button>
									</div>
									<fieldset><legend>รายชื่อโครงงาน (คลิกเพื่อนำออก)</legend>
										<div class="to-proj css-flex css-flex-inline css-flex-gap-10 css-flex-wrap"></div>
									</fieldset>
								</td>
							</tr>
							<tr>
								<th class="css-text-left">3) มอบหมายโครงงาน</th>
								<td><div class="css-flex center"><button onClick="page.C.assign()" class="set-cmte pink ripple-click" disabled><span class="text">ยืนยันการมอบหมาย</span></button></div></td>
							</tr>
						</tbody></table></div>
					</div>
					<div order="2" class="form form-bs">
						<div class="table list"><table><tbody>
							<tr>
								<th class="css-text-left">1) เลือกกรรมการ</th>
								<td><div class="group">
									<select name="bulk">
										<option value selected disabled>— กรุณาเลือก —</option>
									</select>
								</div></td>
							</tr>
							<tr>
								<th class="css-text-left">2) เลือกโครงงาน<br>กดจากตารางด่านล่าง</th>
								<td>
									<div class="css-flex css-flex css-flex-split">
										<p>โครงงานละไม่เกิน 5 คน</p>
										<button class="secondary small ripple-click" onClick="page.A.restart()" disabled><span class="text">ลบทั้งหมด</span></button>
									</div>
									<fieldset><legend>รายชื่อโครงงาน (คลิกเพื่อนำออก)</legend>
										<div class="with-proj css-flex css-flex-inline css-flex-gap-10 css-flex-wrap"></div>
									</fieldset>
								</td>
							</tr>
							<tr>
								<th class="css-text-left">3) มอบหมายโครงงาน</th>
								<td><div class="css-flex center"><button onClick="page.A.assign()" class="asgn-cmte purple ripple-click" disabled><span class="text">ยืนยันการมอบหมาย</span></button></div></td>
							</tr>
						</tbody></table></div>
					</div>
				</div>
			</div>
		</details>
		<div class="loading large" style="margin: 20px 0 10px; width: 100%;"></div>
		<div class="form afterLoad form-bs inline" style="display: none;"><div class="group">
			<label><i class="material-icons">filter_list</i></label>
			<input type="search" name="filter" placeholder="ค้นหา..." />
		</div></div>
		<div class="projects afterLoad table list responsive striped" style="display: none;"><table><thead>
			<tr>
				<th colspan="2">โครงงาน</th>
				<th colspan="2">กรรมการที่มอบหมาย</th>
			</tr>
			<tr>
				<th>รหัส</th>
				<th>ชื่อ</th>
				<th>ขั้นที่ 1</th>
				<th>ขั้นที่ 2</th>
			</tr>
		</thead></table></div>
	</section>
</main>
<?php
	$APP_PAGE -> print -> materials();
	$APP_PAGE -> print -> footer();
?>