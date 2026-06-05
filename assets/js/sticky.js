(function($) {
  'use strict';

  if (!$ || !$.fn) {
    return;
  }

  // Lightweight sticky helper that avoids plugin/runtime crashes.
  $(function() {
    var $sticky = $('.sticky');
    if (!$sticky.length) {
      return;
    }

    var stickyClass = 'sticky-pin';
    var stickyTop = 0;

    function recalcTop() {
      var offset = $sticky.first().offset();
      if (!offset || typeof offset.top === 'undefined') {
        return;
      }
      stickyTop = offset.top;
    }

    function updateStickyState() {
      var winTop = $(window).scrollTop() || 0;
      if (winTop >= stickyTop) {
        $sticky.addClass(stickyClass);
      } else {
        $sticky.removeClass(stickyClass);
      }
    }

    recalcTop();
    updateStickyState();

    $(window).on('resize', function() {
      recalcTop();
      updateStickyState();
    });

    $(window).on('scroll', function() {
      updateStickyState();
    });
  });
})(window.jQuery);