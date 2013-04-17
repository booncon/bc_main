/*!
 * jQuery wmuSlider v2.1
 * 
 * Copyright (c) 2011 Brice Lechatellier
 * http://brice.lechatellier.com/
 *
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Modified by 2013 Sven Perkmann:
 * + incorporating horizontal teasers of previous / next slide
 * - "items" parameter not supported anymore
 */

(function($) {
    
    $.fn.wmuSlider = function(options) {

        /* Default Options
        ================================================== */       
        var defaults = {
            animation: 'fade',
            animationDuration: 600,
            slideshow: true,
            slideshowSpeed: 7000,
            slideToStart: 0,
            navigationControl: true,
            paginationControl: true,
            teasingControl: false,
            previousText: 'Previous',
            nextText: 'Next',
            touch: false,
            slide: 'article',
            teasingFactor: 0.4, // how much of the content margin is used to show teasers of the previous/next slide
            teasingOverlapLimit: 50 // teasing overlap may not exceed this limit (useful is teasingFactor is above 1 and hence overlapping the content region)
        };
        var options = $.extend(defaults, options);
        
        return this.each(function() {

            /* Variables
            ================================================== */
            var $this = $(this);
            var $content = $('#content'); // to align image width on the rest of the content
            var currentIndex = options.slideToStart;
            var wrapper = $this.find('.wmuSliderWrapper');
            var slides = $this.find(options.slide);
            var slidesCount = slides.length;
            var slideshowTimeout;
            var paginationControl;
            var isAnimating;           
            

            /* Load Slide
            ================================================== */ 
            var loadSlide = function(index, infinite, touch) {
                if (isAnimating) {
                    return false;
                }
                isAnimating = true;
                currentIndex = index;
                var slide = $(slides[index]);
                $this.animate({ height: slide.innerHeight() });
                if (options.animation == 'fade') {
                    slides.css({
                        position: 'absolute',
                        opacity: 0
                    });
                    slide.css('position', 'relative');
                    slide.animate({ opacity:1 }, options.animationDuration, function() {
                        isAnimating = false;
                    });
                } else if (options.animation == 'slide') {
                    var ref = $('article:last-child', wrapper); // To calculate the right margin, last one is guaranteed only to have right margin in our current setup.
                    var computedMarginX = ref.outerWidth(true)-ref.innerWidth();
                    if (!infinite) {
                        wrapper.animate({ marginLeft: -($content.width() + computedMarginX) * index }, options.animationDuration, function() {
                            isAnimating = false;
                        });
                    } else {
                        if (index == 0) {
                            wrapper.animate({ marginLeft: 0 }, options.animationDuration, function() {
                                isAnimating = false;
                            });
                        } else {
                            wrapper.animate({ marginLeft: -($content.width() + computedMarginX) * index }, options.animationDuration, function() {
                                isAnimating = false;
                            });
                        }
                    }
                }

                if (paginationControl) {
                    paginationControl.find('a').each(function(i) {
                        if(i == index) {
                            $(this).addClass('wmuActive');
                        } else {
                            $(this).removeClass('wmuActive');
                        }
                    });
                }   

                if (options.teasingControl) {
                    slides.css('cursor', 'pointer');
                    slide.css('cursor', 'default');
                } 

                // add active class
                slides.removeClass('active');
                slide.addClass('active');
                                                    
                // Trigger Event
                $this.trigger('slideLoaded', index);             
            };
            
            
            /* Teasing Control
            ================================================== */ 
            if (options.teasingControl) {
                paginationControl = $('<ul class="wmuSliderPagination"></ul>');
                $.each(slides, function(i) {
                    slide = $(slides[i]);
                    slide.click(function(e) {    
                        //e.preventDefault(); -> links not clickable, ljh
                        clearTimeout(slideshowTimeout);   
                        loadSlide(i);
                    });                
                });
            }


            /* Navigation Control
            ================================================== */ 
            if (options.navigationControl) {
                var navigationControlUpdate = function() {
                    // Hide prev button on first slide
                    if (currentIndex == 0) {
                        $('.wmuSliderPrev', $this).hide();
                    } else {
                        $('.wmuSliderPrev', $this).show();
                    }
                    // Hide next button on last slide
                    if (currentIndex == slidesCount-1) {
                        $('.wmuSliderNext', $this).hide();
                    } else {
                        $('.wmuSliderNext', $this).show();
                    }
                }

                var prev = $('<a class="wmuSliderPrev">' + options.previousText + '</a>');
                prev.click(function(e) {
                    e.preventDefault();
                    clearTimeout(slideshowTimeout);
                    if (currentIndex == 0) {
                        loadSlide(slidesCount - 1, true);
                    } else {
                        loadSlide(currentIndex - 1);
                    }
                    navigationControlUpdate();
                });
                $this.append(prev);
                
                var next = $('<a class="wmuSliderNext">' + options.nextText + '</a>');
                next.click(function(e) {
                    e.preventDefault();
                    clearTimeout(slideshowTimeout);
                    if (currentIndex + 1 == slidesCount) {    
                        loadSlide(0, true);
                    } else {
                        loadSlide(currentIndex + 1);
                    }
                    navigationControlUpdate();
                });                
                $this.append(next);

                navigationControlUpdate();
            }
            

            /* Pagination Control
            ================================================== */ 
            if (options.paginationControl) {
                paginationControl = $('<ul class="wmuSliderPagination"></ul>');
                $.each(slides, function(i) {
                    paginationControl.append('<li><a href="#">' + i + '</a></li>');
                    paginationControl.find('a:eq(' + i + ')').click(function(e) {    
                        e.preventDefault();
                        clearTimeout(slideshowTimeout);   
                        loadSlide(i);
                    });                
                });
                $this.append(paginationControl);
            }
            
            
            /* Slideshow
            ================================================== */ 
            if (options.slideshow) {
                var slideshow = function() {
                    if (currentIndex + 1 < slidesCount) {
                        loadSlide(currentIndex + 1);
                    } else {
                        loadSlide(0, true);
                    }
                    slideshowTimeout = setTimeout(slideshow, options.slideshowSpeed);
                }
                slideshowTimeout = setTimeout(slideshow, options.slideshowSpeed);
            }
            
                        
            /* Resize Slider
            ================================================== */ 
            var resize = function() {
                var slide = $(slides[currentIndex]);
                $this.animate({ height: slide.innerHeight() });
                if (options.animation == 'slide') {
                    slides.css({
                        width: $content.width(),
                        marginRight: Math.max( ($('body').width() - $content.width())  / 2 * (1-options.teasingFactor), -options.teasingOverlapLimit),
                    });
                    slides.first().css({
                        marginLeft: ($this.width() - $content.width()) / 2,
                    });
                    wrapper.css({
                        marginLeft: -$this.width() * currentIndex,
                        width: ($this.width()) * slides.length
                    });                 
                }   
            };
            
                        
            /* Touch
            ================================================== */
            var touchSwipe = function(event, phase, direction, distance) {
                clearTimeout(slideshowTimeout);              
                if(phase == 'move' && (direction == 'left' || direction == 'right')) {
                    if (direction == 'right') {
                        if (currentIndex == 0) {
                            wrapper.css('marginLeft', (-slidesCount * $this.width()) + distance);
                        } else {
                            wrapper.css('marginLeft', (-currentIndex * $this.width()) + distance);
                        }
                    } else if (direction == 'left') {
                        wrapper.css('marginLeft', (-currentIndex * $this.width()) - distance);
                    }
                } else if (phase == 'cancel' ) {
                    if (direction == 'right' && currentIndex == 0) {
                        wrapper.animate({ marginLeft: -slidesCount * $this.width() }, options.animationDuration);                
                    } else {
                        wrapper.animate({ marginLeft: -currentIndex * $this.width() }, options.animationDuration);  
                    }
                } else if (phase == 'end' ) {
                    if (direction == 'right') {
                        if (currentIndex == 0) {
                            loadSlide(slidesCount - 1, true, true);
                        } else {
                            loadSlide(currentIndex - 1);
                        }
                    } else if (direction == 'left')    {        
                        if (currentIndex + 1 == slidesCount) {
                            loadSlide(0, true);
                        } else {
                            loadSlide(currentIndex + 1);
                        }
                    } else {
                        wrapper.animate({ marginLeft: -currentIndex * $this.width() }, options.animationDuration);
                    }
                }            
            };
            if (options.touch && options.animation == 'slide') {
                if ($.isFunction($.fn.swipe)) {
                    $this.swipe({ triggerOnTouchEnd:true, swipeStatus:touchSwipe, allowPageScroll:'vertical' });
                }
            }
            
            
            /* Init Slider
            ================================================== */ 
            var init = function() {
                var slide = $(slides[currentIndex]);
                var img = slide.find('img');
                img.load(function() {
                    wrapper.show();
                    $this.animate({ height: slide.innerHeight() });
                });
                if (options.animation == 'fade') {
                    slides.css({
                        position: 'absolute',
                        width: '100%',
                        opacity: 0
                    });
                    $(slides[currentIndex]).css('position', 'relative');
                } else if (options.animation == 'slide') {
                    slides.css('float', 'left');                
                    slides.each(function(i){
                        var slide = $(this);
                        slide.attr('data-index', i);
                    });
                    slides = $this.find(options.slide);
                }
                resize();
                
                $this.trigger('hasLoaded');

                loadSlide(currentIndex);
            }
            init();
            
                                                
            /* Bind Events
            ================================================== */
            // Resize
            //$(window).resize(resize);
            $(window).smartresize(function(){
                resize();
            });    


            // Load Slide
            $this.bind('loadSlide', function(e, i) {
                clearTimeout(slideshowTimeout);
                loadSlide(i);
            });
                        
        });
    }
    
})(jQuery);