<?php
    $dirPWroot = str_repeat("../", substr_count($_SERVER['PHP_SELF'], "/")-1);
	require_once($dirPWroot."resource/hpe/init_ps.php");

	if (isset($_REQUEST["furl"]) && !empty(trim($_REQUEST["furl"]))) {
		$path = ltrim(trim($_REQUEST["furl"]), "/");
		$real = $dirPWroot.$path;
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		if (!in_array($extension, array("png", "jpg", "jpeg", "heic", "heif", "gif", "pdf"))) $error = "905";
		else if (file_exists($real)) {
			if ($extension <> "pdf") $size = getimagesize($real);
			$header_title = "File viewer";
		} else $error = "900";
		$name = trim($_REQUEST["name"] ?? basename($path));
	} else $error = "902";

	if (isset($error)) $header_title = "Error: $error";
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require($dirPWroot."resource/hpe/heading.php"); require($dirPWroot."resource/hpe/init_ss.php"); ?>
		<?php if ($extension == "pdf") { ?>
			<style type="text/css">
				main > iframe { position: absolute; top: 0px; z-index: 1; }
			</style>
		<?php } else if (!isset($error)) { ?>
		<style type="text/css">
			main {
				min-height: 100% !important; height: var(--window-height) !important;
				overflow: hidden;
			}
			main div.container {
				padding: 0 !important;
				width: <?=$size[0]??"0";?>px; height: <?=$size[1]??"0";?>px; max-height: 100%;
				overflow: visible;
			}
			main div.container::-webkit-scrollbar { width: 0; height: 0; }
			main div.container div.wrapper {
				width: 100%; height: 100%;
				background-image: linear-gradient(45deg,#EFEFEF 25%,rgba(239,239,239,0) 25%,rgba(239,239,239,0) 75%,#EFEFEF 75%,#EFEFEF),linear-gradient(45deg,#EFEFEF 25%,rgba(239,239,239,0) 25%,rgba(239,239,239,0) 75%,#EFEFEF 75%,#EFEFEF); background-position: 0 0,10px 10px; background-size: 21px 21px;
				transition: var(--time-tst-fast);
			}
			main div.container div.wrapper > * {
				position: absolute; top: 0px;
				width: 100%; height: 100%;
			}
			main div.container div.wrapper div { opacity: 0.5; filter: opacity(0.5); }
			main div.container div.wrapper div img { opacity: 0; filter: opacity(0); }
			main div.container div.wrapper span {
				background-image: url("/<?=$path?>"); background-size: contain;
				/* backdrop-filter: blur(7.5px); */ background-repeat: no-repeat; background-position: center;
			}
			main div.controller, main div.controller div.sgt {
				position: absolute; top: 0px;
				width: 100%; height: 100%;
			}
			main div.controller div.bar {
				--h: 50px; --pd-s: 50px;
				padding: 175px var(--pd-s) 25px;
				position: absolute; bottom: 0px; z-index: 1;
				width: calc(100% - var(--pd-s) * 2); height: var(--h);
				transition: var(--time-tst-fast);
			}
			main div.controller div.bar.force {
				transform: translateY(0px);
				opacity: 1; filter: opacity(1);
				pointer-events: auto;
			}
			main div.controller div.bar ul {
				margin: 0px auto; padding: 0px 5px;
				width: 550px; height: calc(100% - 5px);
				background-color: var(--clr-bs-light);
				border-radius: calc(var(--h) / 2); border: 2.5px solid var(--clr-bs-dark);
				display: flex; justify-content: space-around;
				overflow: visible; transition: var(--time-tst-medium);
			}
			main div.controller div.bar ul li {
				height: calc(var(--h) - 5px);
				white-space: nowrap;
				display: flex; list-style-type: none;
			}
			main div.controller div.bar ul > * { transform: translateY(-0.5px); }
			main div.controller div.bar ul li > * {
				min-width: 30px; height: 100%;
				font-size: 24px; line-height: calc(var(--h) - 5px);
				text-align: center;
			}
			main div.controller div.bar ul li a {
				--mg-f: 7.5px;
				margin: var(--mg-f) 0px;
				height: calc(var(--h) - 5px - var(--mg-f) * 2);
				font-size: 24px; line-height: calc(var(--h) - 5px - var(--mg-f) * 2);
				border-radius: calc((var(--h) - 5px - var(--mg-f) * 2) / 2);
				transition: var(--time-tst-xfast);
			}
			main div.controller div.bar ul li a:link, main div.controller div.bar ul li a:hover { color: var(--clr-bs-gray); }
			main div.controller div.bar ul li a:active, main div.controller div.bar ul li a:focus { color: var(--clr-bs-gray-dark); }
			main div.controller div.bar ul li a:hover { background-color: rgba(0, 0, 0, 0.125); }
			main div.controller div.bar ul li a i { position: relative; top: 50%; left: 50%; transform: translate(-62.5%, -50%); }
			main div.controller div.bar ul li a span {
				position: absolute; top: var(--mg-f); transform: translateX(calc(-100% + 2.5px));
				min-width: inherit; height: calc(100% - var(--mg-f) * 2);
				border-radius: calc((var(--h) - 5px - var(--mg-f) * 2) / 2);
				display: inline-block;
			}
			main div.controller div.bar ul li label { padding: 0px 6.25px; }
			main div.controller div.bar ul li label select {
				border-radius: 3.75px; border: 1px solid var(--clr-bs-gray);
				background-color: var(--clr-gg-grey-100);
				font-size: 20px;
			}
			main div.controller div.bar ul li input[type="checkbox"] { transform: scale(0.75); }
			main div.controller div.bar ul > span {
				position: relative; top: 50%; transform: translateY(-50%);
				width: 1.25px; height: 75%;
				background-color: var(--clr-gg-grey-500);
				display: block;
			}
			main *[data-title]:before {
				padding: 7.5px;
				position: absolute; top: -33.5px; left: 50%; transform: translateX(-50%);
				height: 10px;
				background-color: var(--clr-bs-dark); border-radius: 5px; border: 1px solid var(--clr-bs-light);
				box-shadow: 0px 0px 2.5px 2.5px rgba(127, 127, 127, 0.375);
				color: var(--clr-bs-light); white-space: nowrap;
				font-size: 12.5px; line-height: 10px; font-family: "Balsamiq Sans";
				display: none; content: attr(data-title); pointer-events: none;
			}
			main *[data-title]:after {
				position: absolute; top: -12.5px; left: 50%; transform: translateX(-50%) rotate(45deg);
				width: 10px; height: 10px;
				background-color: var(--clr-bs-dark);
				border-right: 1px solid var(--clr-bs-light); border-bottom: 1px solid var(--clr-bs-light);
				box-shadow: 2.25px 2.25px 0.25px 0.75px rgba(127, 127, 127, 0.09375);
				display: none; content: ""; pointer-events: none;
			}
			main *[data-title]:hover:before, main *[data-title]:active:before, main *[data-title]:hover:after, main *[data-title]:active:after { display: block; }
			@media only screen and (min-width: 768.003px) {
				main div.controller div.bar {
					transform: translateY(25px);
					opacity: 0; filter: opacity(0);
				}
				main div.controller div.bar:hover {
					transform: translateY(0px);
					opacity: 1; filter: opacity(1);
				}
			}
			@media only screen and (max-width: 768px) {
				main div.controller div.bar {
					--pd-s: 25px; --h: 30px;
					padding: 12.5px var(--pd-s);
				}
				main div.controller div.bar {
					bottom: 75px;
					opacity: 0.25; filter: opacity(0.25);
					pointer-events: none;
				}
				main div.controller div.bar.on { opacity: 1; filter: opacity(1); pointer-events: initial; }
				main div.controller div.bar ul { padding: 0px 1.25px; width: 350px; }
				main div.controller div.bar ul li > * { min-width: 20px; font-size: 12.5px; }
				main div.controller div.bar ul li a { --mg-f: 2.5px; font-size: 12px; width: 20px; }
				main div.controller div.bar ul li a i { transform: translate(-50%, -50%) scale(0.75); }
				main div.controller div.bar ul li label { padding: 0px 2.5px; }
				main div.controller div.bar ul li label select { font-size: 10px; }
				main *[data-title]:before { transform: translateX(calc(-50% - 7.5px)); }
				main *[data-title]:after { transform: translateX(calc(-50% - 7.5px)) rotate(45deg); }
			}
			@media only print {
				main div.container div.wrapper { background: transparent; }
				main div.controller { display: none; }
			}
		</style>
		<script type="text/javascript">
			const zoom = {
				level: [12.5,25,35,50,65,75,80,90,100,110,120,125,150,175,200,300,400,500], now: 8,
				inc: function() { if (zoom.now+1<zoom.level.length) { zoom.now++; zoom.init(); } },
				dec: function() { if (zoom.now-1>=0) { zoom.now--; zoom.init(); } },
				init: function(sel=true) {
					let percent = zoom.level[zoom.now];
					$("main div.container div.wrapper").css("transform", "scale(" + (percent / 100).toString() + ") rotate(var(--rot))");
					if (sel) $('[name="zoom"] option[value="' + percent.toString() + '"]').prop("selected", true);
				}
			}, rot = {
				now: 0,
				cw: function() { /* rot.now = (rot.now+90==360) ? 0 : rot.now+90; */ rot.now+=90; rot.init(); },
				cc: function() { /* rot.now = (rot.now-90==-360) ? 0 : rot.now-90; */ rot.now-=90; rot.init(); },
				init: function() { $("main div.container div.wrapper").css("--rot", rot.now.toString()+"deg"); }
			}; var prevkey = [], cooldownload = { t: null, s: 5 };
			$(document).ready(function() {
				zoom.level.forEach(percent => {
					let this_opt = $('<option value="' + percent.toString() + '">' + percent.toString() + ' %</option>');
					$('[name="zoom"]').append(this_opt);
				}); zoom.init(); rot.init();
				$('[name="zoom"]').on("change", function() {
					zoom.now = zoom.level.indexOf(parseFloat(this.value));
					zoom.init(false);
				});
				$('[name="asc"]').on("change", function() { $("main div.controller div.bar").toggleClass("force"); });
				$("main div.controller div.sgt").on("click", function() { $("main div.controller div.bar").toggleClass("on"); });
				setTimeout(function() {
					// Grade(document.querySelectorAll("main div.container div.wrapper div"));
					setTimeout(function() { $("main div.container div.wrapper div img").remove(); }, 250);
				}, 250);
				$(document).on("keypress keydown", function(e) {
					let prik = e.which || e.keyCode, ckeyp = String.fromCharCode(prik) || e.key || e.code, isCrtling = e.ctrlKey, isShifting = e.shiftKey, isAlting = e.altKey;
					prevkey.push(prik); if (prevkey.length > 3) prevkey.shift();
					if (prik == 38) { e.preventDefault(); zoom.inc(); }
					else if (prik == 40) { e.preventDefault(); zoom.dec(); }
					else if (prik == 39) { e.preventDefault(); rot.cw(); }
					else if (prik == 37) { e.preventDefault(); rot.cc(); }
					else if (ckeyp == "c") { document.querySelector('[name="asc"]').checked = !document.querySelector('[name="asc"]').checked; $('[name="asc"]').trigger("change"); }
					else if (ckeyp == "D" && isCrtling) download_file(e);
					// else app.ui.notify(1, [1, prik.toString()]);
				}); $("html body").css("--h", $(window).height().toString()+"px");
			});
			function download_file(e) {
				if (typeof e !== "undefined") e.preventDefault();
				if (cooldownload.t == null) {
					cooldownload.t = setInterval(function() {
						if (!--cooldownload.s) { clearInterval(cooldownload.t); cooldownload.t = null; cooldownload.s = 5; }
					}, 1000);
					app.ui.notify(1, [0, "Download is starting ..."]);
					// var adlder = document.querySelector("a.adlder");
					let getfileurl = "/resource/file/download?furl=<?=substr($path, 9, strlen($path) - 9).($name<>basename($path) ? "&name=$name" : "")?>";
					// $(adlder).attr("href", getfileurl); adlder.click(); $(adlder).removeAttr("href");
					document.querySelector('main iframe[name="dlframe"]').src = getfileurl;
				} else app.ui.notify(1, [1, "Please wait ... You can download again in ("+cooldownload.s+")"]);
			}
		</script><?php } ?>
		<!--script type="text/javascript" src="/resource/js/lib/grade.min.js"></script-->
	</head>
	<body class="nohbar">
		<main>
			<?php
				if (isset($error)) echo '<iframe src="/error/'.$error.'">Error: '.$error.'</iframe>';
				else if ($extension == "pdf") {
			?>
				<div class="container">
                    <div class="message yellow"><?=$_COOKIE['set_lang']=="th"?'หากไม่มีไฟล์ปรากฏขึ้นใน 10 วินาที กรุณากดปิดหน้านี้และเปิดใหม่':'If the nothing shows up within 10 seconds. Please re-open this viewer.'?></div>
                </div>
				<iframe src="https://docs.google.com/gview?embedded=true&url=https%3A%2F%2F<?=urlencode($_SERVER['SERVER_NAME']."/".$path)?>">Loading...</iframe>
			<?php } else { ?>
			<div class="container">
				<div class="wrapper">
					<div><img src="/<?=$path?>"></div>
					<span></span>
				</div>
			</div>
			<div class="controller">
				<div class="sgt"></div>
				<div class="bar"><ul>
					<li>
						<a onClick="rot.cc()" href="javascript:"><i class="material-icons">rotate_left</i><span data-title="Rotate counter-clockwise (←)"></span></a>
						<label>Rotate</label>
						<a onClick="rot.cw()" href="javascript:"><i class="material-icons">rotate_right</i><span data-title="Rotate clockwise (→)"></span></a>
					</li>
					<span></span>
					<li>
						<a onClick="zoom.dec()" href="javascript:"><i class="material-icons">zoom_out</i><span data-title="Zoom Out (↓)"></span></a>
						<label>Zoom <select name="zoom"></select></label>
						<a onClick="zoom.inc()" href="javascript:"><i class="material-icons">zoom_in</i><span data-title="Zoom In (↑)"></span></a>
					</li>
					<span></span>
					<li data-title="always show controller (c)">
						<input name="asc" type="checkbox" id="prv-asc"><label for="prv-asc">ASC</label>
					</li>
					<span></span>
					<li>
						<a onClick="window.print()" href="javascript:"><i class="material-icons">print</i><span data-title="Print (ctrl+P)"></span></a>
						<a <?=(preg_match('/^resource\/(css|file|fonts|images|js|json|upload)\/.+\.(png|jpg|jpeg|heic|heif|gif|pdf)$/', $path) ? "" : "disabled")?> onClick="download_file()" href="javascript:"><i class="material-icons">download</i><span data-title="Download (ctrl+D)"></span></a>
						<a class="adlder" target="dlframe" download="<?=$name?>" hidden></a>
						<iframe name="dlframe" hidden></iframe>
					</li>
				</ul></div>
				<iframe name="dlframe" hidden></iframe>
			</div><?php } ?>
		</main>
		<?php require($dirPWroot."resource/hpe/material.php"); ?>
		<footer>
			<?php require($dirPWroot."resource/hpe/footer.php"); ?>
		</footer>
	</body>
</html>