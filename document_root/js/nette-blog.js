
// efekt při překreslení snippetu
jQuery.nette.updateSnippet = function (id, html) {
	$("#" + id).fadeTo("fast", 0.01, function () {
		$(this).html(html).fadeTo("fast", 1);
	});
};


// skrývání flash zpráviček
$("div.flash").livequery(function () {
	var el = $(this);
	setTimeout(function () {
		el.animate({"opacity": 0}, 2000);
		el.slideUp();
	}, 7000);
});


$(function () {
	// vhodně nastylovaný div vložím po načtení stránky
	$('<div id="ajax-spinner"></div>').appendTo("body").ajaxStop(function () {
		// a při události ajaxStop spinner schovám a nastavím mu původní pozici
		$(this).hide().css({
			position: "fixed",
			left: "50%",
			top: "50%"
		});
	}).hide();
});

// zajaxovatění odkazů provedu takto
$("a.ajax").live("click", function (event) {
	event.preventDefault();

	$.get(this.href);

	// zobrazení spinneru a nastavení jeho pozice
	$("#ajax-spinner").show().css({
		position: "absolute",
		left: event.pageX + 20,
		top: event.pageY + 40
	});
});


// odeslání na formulářích
$("form.ajax").livequery('submit', function (e) {
	$(this).ajaxSubmit(e);

	// zobrazení spinneru
	$("#ajax-spinner").show();

	return false;
});

// odeslání pomocí tlačítek
$("form.ajax:submit").livequery('click', function (e) {
	$(this).form.submit(e);
});