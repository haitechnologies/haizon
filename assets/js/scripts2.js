(function($) {
	"use strict";
		
	// ______________ Cover-image
	$(".cover-image").each(function() {
		var attr = $(this).attr('data-bs-image-src');
		if (typeof attr !== typeof undefined && attr !== false) {
			$(this).css('background', 'url(' + attr + ') center center');
		}
	});	
	
	// ______________ Color-skin
	$(document).ready(function() {
      $("a[data-bs-theme]").click(function() {
        $("head link#theme").attr("href", $(this).data("theme"));
        $(this).toggleClass('active').siblings().removeClass('active');
      });
      $("a[data-bs-effect]").click(function() {
        $("head link#effect").attr("href", $(this).data("effect"));
        $(this).toggleClass('active').siblings().removeClass('active');
      });
    });
	
		
})(jQuery);	
	

