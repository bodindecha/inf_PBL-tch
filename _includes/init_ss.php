<style type="text/css">
	<?php
		$googleFonts = array(
			"Akaya+Telivigala",
			"Balsamiq+Sans",
			"Bitter",
			"Caladea:wght@700",
			"Charm",
			"Charmonman",
			"Cormorant+Upright:wght@700",
			"Dancing+Script:wght@400;600",
			"Dosis:wght@500",
			"Google+Sans",
			"IBM+Plex+Sans+Thai",
			"Itim",
			"Kanit:wght@200",
			"Kodchasan:wght@200;300",
			"Krub:wght@300",
			"Mali:wght@300",
			"Modak",
			"Open+Sans",
			"Oswald:wght@700",
			"Permanent+Marker",
			"Prompt",
			"Quicksand:wght@600",
			"Ranchers",
			"Roboto:wght@300",
			"Sarabun:wght@300",
			"Srisakdi"
		);
		echo "@import url('//fonts.googleapis.com/css2?family=".implode("&family=", $googleFonts)."&display=swap');";
	?>
	@import url('/resource/css/core/appfont.css');
	@import url('/resource/css/core/tclfont.css');
	@import url('//fonts.googleapis.com/icon?family=Material+Icons');
</style>
<style type="text/css" class="theme" media="only screen and (max-width: 0px)">
	:root, [data-dark="false"], .player, iframe { filter: invert(100%) hue-rotate(180deg); }
</style>
<script type="text/javascript">
	const isSafari = (navigator.userAgent.indexOf("Safari") > -1 && navigator.userAgent.indexOf("Chrome") < 0);
	$(function(){
		// SEO & lighthouse
		document.querySelector("html").setAttribute("lang", ppa.getCookie("set_lang"));
		document.querySelectorAll("html body img:not([alt])").forEach((ei) => {
			let alt = $(ei).attr("src").split("/").at(-1);
			ei.setAttribute("alt", alt);
		});
		// App
		<?php if($require_sso)echo"sys.auth.sso('".($_GET['return_url']??"")."');"; ?>
		var main_height = $("html body main").height();
		$("header div.head-item a").on("click", function(){setTimeout(function(){$(window).trigger("resize");},500);});
		// Resizing
		var $window = $(window).on('resize', function(){
			$("html body").css("--window-height", $(window).height().toString()+"px");
			var tlbw = [1.75, 0]; document.querySelectorAll("html body header section:nth-child(1) div.ocs div.head-item:not([hidden])").forEach((o) => { tlbw[0] += $(o).width(); }); $("html body header section:nth-child(1) div.ocs").css("min-width", tlbw[0].toString()+"px");
			// document.querySelectorAll("html body header section:nth-last-child(1) div.ocs div.head-item:not([hidden])").forEach((o) => { tlbw[1] += $(o).width(); }); $("html body header section:nth-last-child(1) div.ocs").css("min-width", tlbw[1].toString()+"px");
			document.querySelectorAll("html body header section:nth-last-child(1) div.ocs div.head-item:not([hidden])").forEach((o) => { tlbw[1] += $(o).width(); }); $("html body header section:nth-child(1)").css("max-width", ($("html body header").width()-tlbw[1]).toString()+"px");
		}).trigger('resize');
		ppa.check_lang(); ppa.check_theme(); ppa.color_up_codes(); sys.back.start();
		if (self != top) {
			$("html > body").addClass("nohbar");
			$("body > header").remove();
			$("body > aside.navigator_tab").remove();
			$("body > footer").remove();
		} else ppa.console_proof();
		document.querySelectorAll("header div.head-item:not(.logo) a[href]").forEach((menu) => {
			if ($(menu).attr("href").split("?")[0].split("#")[0]==location.pathname) menu.classList.add("ftcpm");
		}); $("header div.head-item.super div.menu").has("a.ftcpm").addClass("ftcpm");
		$("a:not([draggable]), img:not([draggable])").attr("draggable", "false");
		// $('button:not(.ripple-click):not([onClick^="return "]):not([data-title]):not(.dont-ripple), a[role="button"]:not(.ripple-click):not([onClick^="return "]):not([data-title]):not(.dont-ripple)').addClass("ripple-click"); ppa.ripple_click_program();
		/*if (!/^\/((s|t)\/)?$/.test(location.pathname))*/ $('aside.navigator_tab section.nav ul > li > a[href="'+location.pathname+'"]').parent().addClass("this-page");
		$("section.nav details:has(li.this-page)").attr("open", "");
		// Google Analytics
		window.dataLayer = window.dataLayer || [];
		function gtag() { dataLayer.push(arguments); }
		gtag("js", new Date());	gtag("config", "UA-36913607-3");
		// URL
		if (/(^\?|&)(openExternalBrowser=1|fbclid=([^&])+)/.test(location.search)) {
			let ns = location.search.substr(1).split("&").filter(ep => !(ep.startsWith("fbclid=") || ep=="openExternalBrowser=1")).join("&");
			history.replaceState(null, null, location.pathname+(ns.length ? "?"+ns : "")+location.hash);
		}
	});
	// Scrolling
	$(document).scroll(function() {
		// setHash($(document).scrollTop());
		$("html body aside.up").css("display", (($(document).scrollTop() > $(window).height() - 50)?"block":"none"));
		if ($(document).scrollTop()>0) $("html body header:not(.scrolled)").addClass("scrolled");
		else $("html body header.scrolled").removeClass("scrolled");
	});
	function smooth_scrolling(event) {
		if (this.hash !== "") {
			event.preventDefault();
			var hash = this.hash;
			$('html, body').animate({
				scrollTop: $(hash).offset().top
			}, 800, function(){
				window.location.hash = hash;
			});
		}
	}
	$("a").on('click', function(event) { smooth_scrolling(event); });
	function openInBrowser() {
		const TUL = top.location, userAgent = navigator.userAgent || navigator.vendor || window.opera;
		if (/(Line|line(-poker)?)/.test(userAgent)) {
			let URL = {
				page: TUL.protocol+"//"+TUL.hostname+TUL.pathname,
				query: TUL.search+(TUL.search.length ? "&" : "?")+"openExternalBrowser=1"
			}; TUL.assign(URL.page+URL.query+TUL.hash);
			delete URL;
		} else if (/(FBA(N|V)|facebookexternalhit)/.test(userAgent)) {

		} delete TUL; if (isSafari) {
			var actionCourse = document.createElement("script"), thisPage = location.href;
			actionCourse.onload = function() { location = "googlechromme://navigate?url=" + encodeURIComponent(thisPage); }
			actionCourse.onerror = function() { location = thisPage; }
			document.head.appendChild(actionCourse);
		}
	} <?php echo 'if ('.intval(isset($forceExternalBrowser) && $forceExternalBrowser).' || isSafari) openInBrowser();'; ?>
</script>