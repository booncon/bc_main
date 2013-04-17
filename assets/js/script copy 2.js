/* ///////////////////////////////////
//// author: Lukas Jakob Hafner //////
//// co-author: Sven Perkmann ////////
//// company: booncon oy /////////////
//// date: 2013/02/24 ////////////////
//// version: 2.0 ////////////////////
/////////////////////////////////// */

var anim_dur, url_divider, d3_force, d3_width, d3_height, d3_foci, d3_nodes;

anim_dur = 400;
url_divider = ' ● ';

//resizes the showcase blocks to fit the whole width
function resizeFizzy($container) {
  var fizzy_width, per_row;
  if ($container.width() <= 500) {
    per_row = 2;
  } else {
    per_row = 4;
  }
  fizzy_width = ($container.width() / per_row) - parseInt($('.fizzy').css('marginLeft')) - parseInt($('.fizzy').css('marginRight'));
  $('.fizzy').css('maxWidth', fizzy_width);
  $container.isotope('reLayout');
}
//resizes the slider to fit (almost) the whole screen
function resizeSlider() {
  var img_y, window_height;
  window_height = $(window).height();
  if ((navigator.userAgent.match(/iPhone/i) !== null) || (navigator.userAgent.match(/iPod/i) !== null)) {
    window_height += 60;  //fix window height on iphone / + top bar
  }
  //don't entirely fullscreen anymore
  window_height *= 0.87;
  $('#slide_wrapper').height(window_height);
  $(".explode").show();  
  img_y = window_height - $('.project_meta').outerHeight();  
  if (!$(".explode").hasClass('open')) {
    $(".explode").hide();
  }
  //set height of pictures in slider  
  $('.wallpaper').height(img_y).trigger('change');  
}
//scrolls smoothly to the given position
function scrollSmooth(position) {
  $('html,body').animate({scrollTop: position}, anim_dur);
}
//initialises some ui-elements
function initUI() {
  $('.main_menu a, .service_link').click(function () {
    if (typeof(_gaq)!="undefined") { _gaq.push(['_trackPageview', this.hash]); } //track ajax calls with google analytics
    scrollSmooth($(this.hash).offset().top - 60);
    return false;     
  });
  if((navigator.userAgent.match(/iPad/i) == null) && (navigator.userAgent.match(/iPhone/i) == null) && (navigator.userAgent.match(/iPod/i) == null)) {
    $('#sticky_menu').topLink({
      min: 100,
      fadeSpeed: 200
    });
    $('#logo-top-link').click(function(e) {
      e.preventDefault();
      scrollSmooth(0);
    });
  }
  $('.header_logo').click(function () {
    $.bbq.removeState();
    location.reload();
  });$('.retina').retina();
}
//initialises the filtering for the showcase blocks
function initFizzy($container) {
  $container.imagesLoaded( function(){
    $container.isotope({
      resizable: false, // disable normal resizing
      animationOptions: {
       duration: anim_dur,
       easing: 'linear',
       queue: false
      }
    });
    //resize boxes
    resizeFizzy($container);
    //connect the filters
    $('#fizzy_filters li').click(function(){
      $('#fizzy_filters li').removeClass('active');
      $(this).addClass('active');
      var selector = $(this).attr('data-filter');
      $container.isotope({ filter: selector });
      return false;
    }); 
  });    
}
//sets the page title
function setTitle(title) {
  document.title = title;
}
//handles key input on open slider
function initKeyHandler() {
  $(document).unbind('keydown').bind('keydown', function(e) {
    var code, no_key;
    code = (e.keyCode ? e.keyCode : e.which);
    no_key = false;
    //depending on keycode, trigger the click of some element
    switch (code) {
      case 27: //escape
        $('.close').trigger('click');
      break;
      case 37: //arrow left
        $('.wmuSliderPrev').trigger('click');
      break;
      case 39: //arrow right
        $('.wmuSliderNext').trigger('click');
      break;
      case 32: //space
        $('.wmuSliderNext').trigger('click');
      break;
      case 38: //arrow up
        $('.next_proj').trigger('click');
      break;
      case 40: //arrow down
        $('.prev_proj').trigger('click');
      break;
      default:
        no_key = true;
      break;   
    }
    if (!no_key) {
      e.preventDefault();
    }  
  });
  /* $('.wallpaper').click(function() {
    $('.wmuSliderNext').trigger('click');
  }); */
}
function closeSlider() {
  if ($('.explode:visible').length > 0) {
    $('.explode').removeClass('open').hide("blind", { direction: "vertical" }, anim_dur, function () {
      $('.fizzy').removeClass('active');
      setTitle($('#page_title').val());
      $(document).unbind('keydown');
    });
  }  
}
//loads the project with the given url and inits athe slider
function loadProject(url, first) {
  if (typeof(_gaq)!="undefined") { _gaq.push(['_trackPageview', url]); } //track ajax calls with google analytics
  $('.explode').load(url + ' #slide_wrapper', function () {
    var img_y, cur_proj, count_proj, next_proj, prev_proj, loop_proj, nav_dir;  
    //set the current project to active, save next and prev $(object)
    count_proj = 0;    
    prev_proj, loop_proj, next_proj, cur_proj = null;
    $('.fizzy:not(.isotope-hidden)').each(function () { //loop through all the visible elements
      count_proj += 1;  //count the projects
      if ((cur_proj !== null) && (next_proj === undefined)) { //if current-project set but next project not        
        next_proj = $(this); //save the element 
      }
      if ($(this).hasClass('active')) { //on currently shown project
        prev_proj = loop_proj;  //save the element prior to it    
        cur_proj = count_proj;  //save number
      }
      loop_proj = $(this);
    });
    //if no prev/next project deactivate button
    if (next_proj === undefined) {   
      $('.next_proj').addClass('inactive');
    }
    if (prev_proj === undefined) {   
      $('.prev_proj').addClass('inactive');
    }
    initKeyHandler(); //make key navigation active
    //refresh ui, page title
    setTitle($('#page_title').val() + url_divider + $('#project_title').val());    
    $('.cur_proj').text(cur_proj);
    $('.count_proj').text(count_proj);
    //prepare slider, slide in new content
    $(this).addClass('open');
    resizeSlider();
    if (first) { //on first project slide open
      $('.explode').show("blind", { direction: "vertical" }, anim_dur, function () {
      });
    } else {
      if($('.fizzy.clicked_last').index() < $('.fizzy.active').index()) { //navigate up or down
        nav_dir = 'down';
      } else {
        nav_dir = 'up';
      }
      $('.fizzy').removeClass('clicked_last');
      $('#slide_wrapper').show("drop", { direction: nav_dir }, anim_dur, function () {});
      $('.explode').height('auto'); //make wrapper responsive
    }      
    //scroll to the beginning of the slider
    scrollSmooth($('#project_wrapper').offset().top - 60);
    //init slider
    $('.wmuSlider').wmuSlider({
      animation: 'slide',
      slideshow: false,
      navigationControl: true,
      teasingControl: true,
      teasingFactor: 0.6
    });
    //close button
    $('.close').click(function () {      
      $.bbq.pushState({ show: 'none' });      
      return false;
    });
    //triggers next/ previous project
    $('.next_proj:not(.inactive)').click(function () {
      $('.showcase_block', next_proj).trigger('click');
      return false;
    });
    $('.prev_proj:not(.inactive)').click(function () {
      $('.showcase_block', prev_proj).trigger('click');
      return false;
    });    
  });  
}
//pre-animations before opening next project
function projectPreloader(url) {
  var nav_dir;
  if ($('.explode.open').length === 0) { //no project open
    $('.explode').empty();
    loadProject(url, true);
  } else { //some project is shown
    $('.explode').height($('.explode').height()); //keep wrapper open
    if($('.fizzy.clicked_last').index() < $('.fizzy.active').index()) { //navigate up or down
      nav_dir = 'up';
    } else {
      nav_dir = 'down';
    }
    $('body').animate({scrollTop: $('#project_wrapper').offset().top - 60}, anim_dur, function () {      
      $('#slide_wrapper').hide("drop", { direction: nav_dir }, anim_dur, function () { 
        loadProject(url);
      }); 
    });         
  }  
}
//initialize the showcase-blocks
function initShowcase() {
  $('.showcase_block').click(function () {
    var ajax_url;
    ajax_url = $(this).data('url');    
    $.bbq.pushState({ show: ajax_url });
    $('.fizzy.active').addClass('clicked_last');
    $('.fizzy').removeClass('active');
    $(this).closest('.fizzy').addClass('active');
    return false;  
  });
}
//initialize the d3 node highlighting
function initNodeHighlight() {
  $('.force_select').hover(
    function () {
      d3_highlight($(this).siblings('.cluster').data('cluster_name'));
    },
    function () {
      d3_highlight('node');
    });
}
//split up into two groups
d3_splitForce = function() {
  d3_force.on("tick", function(e) {
    // Push nodes toward their designated focus.
    var k = 0.1 * e.alpha;
    d3_nodes.each(function(d, i) {
      d.y += (d3_foci[d.packageId].y - d.y) * k;
      d.x += (d3_foci[d.packageId].x - d.x) * k;
    });
    d3_nodes.attr("transform", function(d) { return "translate(" + Math.max(d.r, Math.min(d3_width - d.r, d.x)) + "," + Math.max(d.r, Math.min(d3_height - d.r, d.y)) + ")"; })
  });
  d3_force.resume();
}
//gather around center
d3_gatherForce = function() {
  d3_force.on("tick", function(e) {
    // Pull nodes together again. We're distorting into two groups by factor z to get an oval effect.
    var k = 0.1 * e.alpha;
    var z = 0.04;
    d3_nodes.each(function(d, i) {
      d.y += (d3_height/2 - d.y) * k;
      if (i%2) {
        d.x += (d3_width/2 + d3_width*z - d.x) * k;
      }
      else {
        d.x += (d3_width/2 - d3_width*z - d.x) * k;
      }
    });
    d3_nodes.attr("transform", function(d) { return "translate(" + Math.max(d.r, Math.min(d3_width - d.r, d.x)) + "," + Math.max(d.r, Math.min(d3_height - d.r, d.y)) + ")"; })
  });
  d3_force.resume();
}
// Returns a flattened hierarchy containing all leaf nodes under the root.
function d3_classes(root) {
  var classes = [];
  function recurse(id, name, node) {
    if (node.children) node.children.forEach(function(child) { recurse(node.id, node.name, child); });
    else classes.push({packageId: id, packageName: name, className: node.name, r: node.size});
  }
  recurse(null, null, root);
  return {children: classes};
}
// Highlights certain nodes
function d3_highlight(targetClass) {
  d3.selectAll('.node')
    .classed('highlighted', function() { return d3.select(this).classed(targetClass); } );
}
//initialize d3
function initD() {
  $('.bullet').hide();

  var w = d3_width = $('.force_bubbles').width();
  var h = d3_height = $('.force_bubbles').height();
  d3_foci = [];

  var dataset = {
    "name": "flare",
    "children": []
  }
  var cluster_wrapper = d3.selectAll('.cluster');

  var foci_scale = d3.scale.linear().domain([0,cluster_wrapper.length]).range([0, w]);

  // Calculating deep level structure to keep it generalised (we might want to switch visualization at some point).
  cluster_wrapper.each(function(d,i) {
      var cluster_name = $(this).data('cluster_name');
      d3_foci.push( { x: foci_scale(i), y: h/2 } );
      dataset.children.push({"id": i, "name": cluster_name, "children": []});
      var cluster = dataset.children[dataset.children.length-1];
      d3.select(this).selectAll('li')
        .each(function() { 
          cluster.children.push({"name": this.innerHTML, "size": this.getAttribute('data-size')});
        });
    });

  // Now flattening down the dataset again.
  dataset = d3_classes(dataset).children;

  var vis = d3.select(".force_bubbles").append("svg:svg")
    .attr("viewBox", "0 0 " + w + " " + h )
    .attr("preserveAspectRatio", "xMidYMid meet")
    .on("mouseover", d3_splitForce)
    .on("mouseout", d3_gatherForce);

  // Bigger bobbles get a more negative charge.
  var min_r = d3.min(dataset, function(d) { return parseInt(d.r); } );
  var max_r = d3.max(dataset, function(d) { return parseInt(d.r); } );
  var charge_scale = d3.scale.linear().domain([min_r,max_r]).range([-900, -1800]);

  d3_force = d3.layout.force()
      .nodes(dataset)
      .links([])
      .size([w, h])
      .charge(function(d) { return charge_scale(d.r); } )
      .gravity(0.1)
      .friction(0.9)
      .start();

  d3_nodes = vis.selectAll(".node")
      .data(dataset)
    .enter().append("g")
      .attr("class", function(d) { return "node highlighted " + d.packageName; })
      .on("mouseover", function(d) { // "scared" nodes flee on mouseover
        d.x += (Math.random() - .5) * 60;
        d.y += (Math.random() - .5) * 60;
        d3_force.resume();
      });
      //.on("mouseover", function(d,i) { d3_highlight(d.packageName); } )
      //.on("mouseout", function(d,i) { d3_highlight('node'); } );
      //.call(d3_force.drag);

  d3_nodes.append("title")
      .text(function(d) { return d.className });

  d3_nodes.append("circle")
      .attr("r", function(d) { return d.r; })
      .style("fill", "#fff");

  d3_nodes.append("text")
      .attr("dy", ".3em")
      .style("text-anchor", "middle")
      .text(function(d) { return d.className; });

  d3_gatherForce();
}
//jquery is ready
function initJQ() {
  var $container; 
  $.ajaxSetup ({
    // Disable caching of AJAX responses
    cache: false
  });
  $container = $('.metafizzy');  
  initUI(); 
  initFizzy($container);  
  initShowcase();  
  // update columnWidth on window resize
  $(window).smartresize(function(){
    resizeFizzy($container);
    resizeSlider();    
    if ($('.explode.open').length > 0) {
      scrollSmooth($('#project_wrapper').offset().top - 60);
    }
  }); 
  // Bind a callback that executes when document.location.hash changes.
  $(window).bind( "hashchange", function(e) {   
    var url, hash;
    url = e.getState("show");
    if (url)  //if url is found in the hashtag
    {
      if (url !== 'none') {
        hash = location.hash.split('=')[1];
        //set the current project to active
        $('.showcase_block').each(function(){
          var that = $(this);
          var t_href = that.data('url');
          //style current project
          that.closest('.fizzy')[ t_href === hash ? 'addClass' : 'removeClass' ]( 'active' );      
        });
        //load the project
        projectPreloader(url);
      } else {
        closeSlider();
      }
    } else {
      closeSlider();  //no hashtag  
    }
  });   
  // Trigger hash-reload in case page is visited with active hash
  $(window).trigger("hashchange");
  if(!jQuery.browser.mobile){
    initNodeHighlight();
    initD();
  }  
}