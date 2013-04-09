var doc_height, window_height;

/*  */
function parallaxScroll(scroll_pos) {
  "use strict";  
  $('.parallax_wrapper:not(.offset_init)').each(function () {
    var $par_wrap, par_window, par_window_top, par_window_bot, par_height, par_top;
    $par_wrap = $(this);
    par_top = $par_wrap.offset().top;
    par_height = $par_wrap.height();
    par_window = window_height - par_height;
    par_window_top = par_top - window_height;
    par_window_bot = par_top + par_height;
    if (par_window_top < 0) {
      par_window_top = 0;
    }
    if (doc_height - par_window_bot <= window_height) {
      $par_wrap.data('par_window_is_bottom', true);
    }
    $par_wrap.data('par_window_top', par_window_top).data('par_window_bot', par_window_bot).addClass('offset_init');
    $par_wrap.before('<div class="par_offset" style="top: ' + par_window_top + 'px; height: ' + (par_window_bot - par_window_top) + 'px;" />');    
  });
  $('.par_offset:in-viewport').each(function () {
    $('.parallax', $(this).next()).each(function () {
      var $par_wrap, $par_el, par_window_top, par_window_bot, par_height, par_wrap_height, par_wrap_width, scroll_dist, scroll_perc, parallax_shift, zoom, par_window_is_bottom, anim_type, anim_start_value, anim_end_value;
      $par_el = $(this);
      $par_wrap = $(this).closest('.parallax_wrapper');
      par_height = $par_el.height();
      par_wrap_height =  $par_wrap.height();
      par_wrap_width =  $par_wrap.width();
      par_window_top = $par_wrap.data('par_window_top');
      par_window_bot = $par_wrap.data('par_window_bot');
      par_window_is_bottom = $par_wrap.data('par_window_is_bottom');
      anim_type = $par_el.data('anim_type');
      anim_start_value = $par_el.data('anim_start_value');
      anim_end_value = $par_el.data('anim_end_value');
      if (anim_type !== undefined) {
        anim_type = anim_type.split(' ');
        scroll_dist = scroll_pos - par_window_top;
        if (scroll_dist >= 0) {
          if (par_window_is_bottom) {
            scroll_dist = scroll_dist + window_height - 5;
          }
          scroll_perc = scroll_dist / (par_window_bot - par_window_top);

          //console.log(anim_end_value);

          anim_end_value = anim_end_value !== undefined ? anim_end_value/100 : 1;

          //console.log(anim_end_value);
          if (scroll_perc > anim_end_value) {
            scroll_perc = anim_end_value;
          }
          if (scroll_perc <= anim_end_value) {
            for (var i=0; i<anim_type.length; i++) {              
              switch(anim_type[i]) {
                case 'zoom_in':
                  parallax_shift = 100 + 100 * scroll_perc + '%';
                  zoom = -(100 + 100 * scroll_perc) / 2 + '%';
                  $par_el.css('width', parallax_shift).css('left', '50%').css('margin-left',  zoom);
                break;
                case 'zoom_out':
                  parallax_shift = 200 - 100 * scroll_perc + '%';
                  zoom = -(200 - 100 * scroll_perc) / 2 + '%';
                  $par_el.css('width', parallax_shift).css('left', '50%').css('margin-left',  zoom);
                break;
                case 'anti':
                  parallax_shift = -(par_height - par_wrap_height) * scroll_perc;
                  $par_el.css('top', parallax_shift + 'px');
                break;
                case 'same':
                  parallax_shift = (par_wrap_height - par_height) * scroll_perc;
                  $par_el.css('bottom', parallax_shift + 'px');
                break;
                case 'left_in':
                  parallax_shift = par_wrap_width - par_wrap_width * scroll_perc;
                  $par_el.css('left', parallax_shift + 'px');
                break;
                case 'right_in':
                  parallax_shift = par_wrap_width - par_wrap_width * scroll_perc;
                  $par_el.css('right', parallax_shift);
                break;
                case 'left_out':
                  parallax_shift = -par_wrap_width * scroll_perc;
                  $par_el.css('left', parallax_shift + 'px');
                break;
                case 'right_out':
                  parallax_shift = -par_wrap_width * scroll_perc;
                  $par_el.css('right', parallax_shift + 'px');
                break;
                case 'fade_out':
                  $par_el.css('opacity', 1 - scroll_perc);
                break;
                case 'fade_in':
                  $par_el.css('opacity', scroll_perc);
                break;
              }
            }
          }  
        }  
      }  
    });
  });
}
function initSmoothScroll() {
  $('.anchor_link').click(function () {
    $.smoothScroll({
      scrollTarget: $(this).attr('href'),
      speed: 1200,
      offset: -100
    });
    return false;
  });
}
function initContainerSizes() {
  window_height = $(window).height();
  $('.parallax_wrapper').height(window_height / 3);
  $('.parallax_wrapper.welcome').height(window_height - $('#intro').height());
  $('.parallax_wrapper.aside').height(window_height / 5);
  $('.parallax_wrapper.divisions').height(window_height / 6);
  doc_height = $(document).height();
}
function initScrollListener() {
  "use strict";
  var $container, scrollUpdate;
  $container = $(window);
  scrollUpdate = function () {
    parallaxScroll($container.scrollTop());
  };
  $container.bind("touchmove", scrollUpdate).bind("scroll", scrollUpdate).trigger('scroll');
  $('.hover').hover(function(){
      $(this).addClass('flip');
  },function(){
      $(this).removeClass('flip');
  });
}
function initResizeActions() {
  $(window).resize(function() {
    initContainerSizes();
    $('.par_offset').remove();
    $('.offset_init').removeClass('offset_init'); 
  });
}
$(document).ready(function () {
  "use strict";  
  initSmoothScroll();
  initContainerSizes();
  initScrollListener();
  initResizeActions();
});