// Early/late boot glue: manages page loader timing and data-load coordination
(function(){
  var html = document.documentElement;
  var timer = null;
  var shown = false;

  function showLoaderDelayed() {
    if (timer) return;
    // Only show if loading lasts > 250ms (prevents flash on fast pages)
    timer = setTimeout(function(){
      html.classList.add('is-loading');
      shown = true;
    }, 250);
  }
  function hideLoader() {
    if (timer) { clearTimeout(timer); timer = null; }
    if (shown) { html.classList.remove('is-loading'); shown = false; }
  }

  // App pages that fetch data should announce load states
  window.addEventListener('rewarity:data-loading', showLoaderDelayed);
  window.addEventListener('rewarity:data-ready', hideLoader);

  // Fallback: if DOM itself takes long, show after 600ms then hide on ready
  var domTimer = setTimeout(function(){ html.classList.add('is-loading'); shown = true; }, 600);
  window.addEventListener('load', function(){ clearTimeout(domTimer); hideLoader(); });
})();
