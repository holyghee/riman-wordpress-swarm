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

    // Parallax scrolling
    if (parallax) {
      var ticking = false;
      function applyParallax(){
        ticking = false;
        var viewportH = window.innerHeight || document.documentElement.clientHeight;
        for (var s=0; s<slides.length; s++) {
          var bg = slides[s].querySelector('.riman-hero-bg');
          if (!bg) continue;
          var rect = slides[s].getBoundingClientRect();
          if (pMode === 'scroll') {
            // translate relative to scroll position around the slide center
            var pageY = window.scrollY || window.pageYOffset || 0;
            var slideTopAbs = rect.top + pageY;
            var slideCenterAbs = slideTopAbs + rect.height/2;
            var viewportCenterAbs = pageY + viewportH/2;
            var delta = viewportCenterAbs - slideCenterAbs; // positive when slide center is above viewport center
            var translate = Math.max(-300, Math.min(300, -delta * pStrength));
            bg.style.transform = 'translateY(' + translate + 'px)';
          } else {
            // transform mode relative to viewport center
            var slideCenter = rect.top + rect.height/2;
            var viewportCenter = viewportH/2;
            var delta2 = slideCenter - viewportCenter;
            var translate2 = Math.max(-300, Math.min(300, -delta2 * pStrength));
            bg.style.transform = 'translateY(' + translate2 + 'px)';
          }
        }
      }
      function onScroll(){ if (!ticking) { window.requestAnimationFrame(applyParallax); ticking = true; } }
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
