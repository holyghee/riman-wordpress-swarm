(function(){
  function setup(root){
    var slides = root.querySelectorAll('.riman-hero-slide');
    var dots = root.querySelectorAll('.riman-hero-indicators .dot');
    var prev = root.querySelector('.riman-hero-nav.prev');
    var next = root.querySelector('.riman-hero-nav.next');
    var track = root.querySelector('.riman-hero-track');
    var n = slides.length; if (!n) return;
    var i = 0, timer = null;
    var interval = parseInt(root.getAttribute('data-interval')||'6000', 10) || 6000;
    var auto = root.getAttribute('data-auto') === '1';
    var anim = root.getAttribute('data-anim') || 'fade';
    var parallax = root.getAttribute('data-parallax') === '1';
    var pStrength = parseFloat(root.getAttribute('data-parallax-strength')||'0.25') || 0.25;
    var pMode = root.getAttribute('data-parallax-mode') || 'transform';
    // Read overscan from CSS variable (e.g. "30%") and convert to fraction (0.3)
    var overscanStr = (getComputedStyle(root).getPropertyValue('--riman-parallax-overscan') || '').trim();
    var overscanFrac = 0.3;
    if (overscanStr.endsWith('%')) {
      var f = parseFloat(overscanStr.replace('%',''));
      if (!isNaN(f)) overscanFrac = Math.max(0, Math.min(0.6, f/100));
    }

    // Parallax function (must be defined before show())
    function applyParallax(){
      if (!parallax) return;
      var viewportH = window.innerHeight || document.documentElement.clientHeight;
      for (var s=0; s<slides.length; s++) {
        var slide = slides[s];
        var bg = slide.querySelector('.riman-hero-bg');
        var video = slide.querySelector('.riman-hero-video');
        var target = video || bg; // Prefer video, fallback to background
        
        if (!target) continue;
        var rect = slide.getBoundingClientRect();
        
        if (pMode === 'scroll') {
          // translate relative to scroll position around the slide center
          var pageY = window.scrollY || window.pageYOffset || 0;
          var slideTopAbs = rect.top + pageY;
          var slideCenterAbs = slideTopAbs + rect.height/2;
          var viewportCenterAbs = pageY + viewportH/2;
          var delta = viewportCenterAbs - slideCenterAbs; // positive when slide center is above viewport center
          var maxAbs = Math.max(0, rect.height * overscanFrac - 2);
          var translate = Math.max(-maxAbs, Math.min(maxAbs, -delta * pStrength));
          var scale1 = 1 + Math.abs(translate) * 0.0005;
          target.style.transform = 'translateY(' + translate + 'px) scale(' + scale1 + ')';
        } else {
          // transform mode relative to viewport center
          var slideCenter = rect.top + rect.height/2;
          var viewportCenter = viewportH/2;
          var delta2 = slideCenter - viewportCenter;
          var maxAbs2 = Math.max(0, rect.height * overscanFrac - 2);
          var translate2 = Math.max(-maxAbs2, Math.min(maxAbs2, -delta2 * pStrength));
          var scale = 1 + Math.abs(translate2) * 0.0005; // Subtle scaling effect
          target.style.transform = 'translateY(' + translate2 + 'px) scale(' + scale + ')';
        }
      }
    }

    // Ensure only active slide's video plays
    function setVideoState(activeIndex){
      for (var a=0; a<n; a++){
        var v = slides[a].querySelector('.riman-hero-video');
        if (!v) continue;
        try {
          if (a === activeIndex){
            v.muted = true; v.setAttribute('muted',''); v.playsInline = true; v.setAttribute('playsinline','');
            // mark loaded state and try to play on various readiness events
            var onLoaded = function(){ this.setAttribute('data-loaded','true'); };
            v.removeEventListener('loadeddata', onLoaded); v.addEventListener('loadeddata', onLoaded, { once:true });
            var tryPlay = function(){
              try { v.play().catch(function(){}); } catch(e){}
            };
            v.removeEventListener('canplay', tryPlay); v.addEventListener('canplay', tryPlay, { once:true });
            v.removeEventListener('canplaythrough', tryPlay); v.addEventListener('canplaythrough', tryPlay, { once:true });
            // force a load and nudge currentTime to kickstart headless playback
            try { v.load(); v.currentTime = (v.currentTime || 0) + 0.01; } catch(e){}
            tryPlay();
          } else {
            v.pause();
          }
        } catch(e){}
      }
    }

    function show(k){
      if (anim === 'slide' && track && pMode !== 'fixed') {
        track.style.transform = 'translateX(' + (-k*100) + '%)';
        for (var a=0;a<n;a++){ if (dots[a]) dots[a].classList.remove('active'); }
        if (dots[k]) dots[k].classList.add('active');
      } else {
        for (var a=0;a<n;a++){ slides[a].classList.remove('active'); if (dots[a]) dots[a].classList.remove('active'); }
        if (slides[k]) slides[k].classList.add('active'); if (dots[k]) dots[k].classList.add('active');
      }
      i = k;
      setVideoState(i);
      if (parallax) applyParallax();
    }
    function nextFn(){ show((i+1)%n); }
    function prevFn(){ show((i-1+n)%n); }
    function start(){ if (!auto || n<2) return; stop(); timer = setInterval(nextFn, interval); }
    function stop(){ if (timer) { clearInterval(timer); timer=null; } }

    if (prev) prev.addEventListener('click', function(){ prevFn(); start(); });
    if (next) next.addEventListener('click', function(){ nextFn(); start(); });
    for (var a=0;a<dots.length;a++){ (function(idx){ dots[idx].addEventListener('click', function(){ show(idx); start(); }); })(a); }
    root.addEventListener('mouseenter', stop); root.addEventListener('mouseleave', start);

    // Initialize
    show(0); start();

    // Parallax scrolling for images and videos
    if (parallax) {
      var ticking = false;
      function onScroll(){ 
        if (!ticking) { 
          window.requestAnimationFrame(function(){
            applyParallax();
            ticking = false;
          }); 
          ticking = true; 
        } 
      }
      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', onScroll);
      applyParallax();
    }
  }
  function init(){
    var roots = document.querySelectorAll('.riman-hero-slider');
    for (var r=0; r<roots.length; r++) setup(roots[r]);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
