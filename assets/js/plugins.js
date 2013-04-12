// Avoid `console` errors in browsers that lack a console.
(function() {
    var method;
    var noop = function noop() {};
    var methods = [
        'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
        'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
        'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
        'timeStamp', 'trace', 'warn'
    ];
    var length = methods.length;
    var console = (window.console = window.console || {});

    while (length--) {
        method = methods[length];

        // Only stub undefined methods.
        if (!console[method]) {
            console[method] = noop;
        }
    }
}());

// Place any jQuery/helper plugins in here.

/*! Smooth Scroll - v1.4.7 - 2012-10-29
* Copyright (c) 2012 Karl Swedberg; Licensed MIT, GPL */
(function(a){function f(a){return a.replace(/(:|\.)/g,"\\$1")}var b="1.4.7",c={exclude:[],excludeWithin:[],offset:0,direction:"top",scrollElement:null,scrollTarget:null,beforeScroll:function(){},afterScroll:function(){},easing:"swing",speed:400,autoCoefficent:2},d=function(b){var c=[],d=!1,e=b.dir&&b.dir=="left"?"scrollLeft":"scrollTop";return this.each(function(){if(this==document||this==window)return;var b=a(this);b[e]()>0?c.push(this):(b[e](1),d=b[e]()>0,d&&c.push(this),b[e](0))}),c.length||this.each(function(a){this.nodeName==="BODY"&&(c=[this])}),b.el==="first"&&c.length>1&&(c=[c[0]]),c},e="ontouchend"in document;a.fn.extend({scrollable:function(a){var b=d.call(this,{dir:a});return this.pushStack(b)},firstScrollable:function(a){var b=d.call(this,{el:"first",dir:a});return this.pushStack(b)},smoothScroll:function(b){b=b||{};var c=a.extend({},a.fn.smoothScroll.defaults,b),d=a.smoothScroll.filterPath(location.pathname);return this.unbind("click.smoothscroll").bind("click.smoothscroll",function(b){var e=this,g=a(this),h=c.exclude,i=c.excludeWithin,j=0,k=0,l=!0,m={},n=location.hostname===e.hostname||!e.hostname,o=c.scrollTarget||(a.smoothScroll.filterPath(e.pathname)||d)===d,p=f(e.hash);if(!c.scrollTarget&&(!n||!o||!p))l=!1;else{while(l&&j<h.length)g.is(f(h[j++]))&&(l=!1);while(l&&k<i.length)g.closest(i[k++]).length&&(l=!1)}l&&(b.preventDefault(),a.extend(m,c,{scrollTarget:c.scrollTarget||p,link:e}),a.smoothScroll(m))}),this}}),a.smoothScroll=function(b,c){var d,e,f,g,h=0,i="offset",j="scrollTop",k={},l={},m=[];typeof b=="number"?(d=a.fn.smoothScroll.defaults,f=b):(d=a.extend({},a.fn.smoothScroll.defaults,b||{}),d.scrollElement&&(i="position",d.scrollElement.css("position")=="static"&&d.scrollElement.css("position","relative"))),d=a.extend({link:null},d),j=d.direction=="left"?"scrollLeft":j,d.scrollElement?(e=d.scrollElement,h=e[j]()):e=a("html, body").firstScrollable(),d.beforeScroll.call(e,d),f=typeof b=="number"?b:c||a(d.scrollTarget)[i]()&&a(d.scrollTarget)[i]()[d.direction]||0,k[j]=f+h+d.offset,g=d.speed,g==="auto"&&(g=k[j]||e.scrollTop(),g=g/d.autoCoefficent),l={duration:g,easing:d.easing,complete:function(){d.afterScroll.call(d.link,d)}},d.step&&(l.step=d.step),e.length?e.stop().animate(k,l):d.afterScroll.call(d.link,d)},a.smoothScroll.version=b,a.smoothScroll.filterPath=function(a){return a.replace(/^\//,"").replace(/(index|default).[a-zA-Z]{3,4}$/,"").replace(/\/$/,"")},a.fn.smoothScroll.defaults=c})(jQuery);



function d3xTimeline (data, options) {
  var defaults = {
    container: 'body',
    w: 500,
    h: 700,
    pad: 50,
    r: 20,
    scale_factor: 2,
    line_length: 50,
    box_pos: 130,
    duration_in: 200,
    duration_out: 500
  };

  function merge(obj1, obj2) {
    for(attr in obj1)
      obj2[attr]=obj1[attr];
    return obj2;
  };
  options = merge(options, defaults);

  var svg = d3.select(options.container).append("svg")
      .attr("viewBox", "0 0 " + options.w + " " + options.h )
    .attr("preserveAspectRatio", "xMidYMin meet");
    //  .attr("width", options.w)
     // .attr("height", options.h);

  var timeline = svg.append('g')
      .attr('transform', function(d,i) { return "translate("+options.w/2+","+0+")"; });

  timeline.append('line')
      .attr('y2', options.h);

  var nodes = timeline.selectAll('g.node')
      .data(data).enter()
    .append('g')
      .attr('class', 'node')
      .attr('transform', function(d,i) { 
        var pos = options.pad + ((options.h-options.pad*2) / (data.length-1)) * i; 
        return "translate("+0+","+pos+")"; 
      });

  var boxes = nodes.append('g')
      .attr('transform', function(d,i) {
        if (i%2)  return "translate("+options.box_pos+","+0+")";
        else      return "translate("+(-options.box_pos)+","+0+")";
      });

  boxes.append('text')
      .attr('dy', '-1em')
      .attr('class', 'head')
      .text(function(d) { return d.head; });

  boxes.append('text')
      .attr('dy', '1em')
      .attr('class', 'body')
      .text(function(d) { return d.body; });

  var circles = nodes.append('g');

  circles.append('line')
      .attr('x2', function(d,i) { 
        if (i%2)  return options.line_length;
        else      return -options.line_length;
      });

  circles.append('image')
      .attr('xlink:href', function(d) { return d.img; })
      .attr('x', -options.r)
      .attr('y', -options.r)
      .attr('width', options.r*2)
      .attr('height', options.r*2)
      .on('mouseover', function(d,i) {
        d3.select(this).transition().duration(options.duration_in).attr('transform', 'scale(' + options.scale_factor + ')');
      })
      .on('mouseout', function(d,i) {
        d3.select(this).transition().duration(options.duration_out).attr('transform', 'scale(1)');
      });
}