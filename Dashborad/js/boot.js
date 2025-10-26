// Early/late boot glue: manages page loader timing and data-load coordination
(function(){
  var html = document.documentElement;
  // On first parse (head inline script should already set theme + is-loading). Fallback here.
  if (!html.classList.contains('is-loading')) { html.classList.add('is-loading'); }

  var waitingForData = false;
  var done = false;

  function hideLoader(){
    if (done) return; done = true;
    html.classList.remove('is-loading');
  }

  window.addEventListener('rewarity:data-loading', function(){ waitingForData = true; });
  window.addEventListener('rewarity:data-ready', function(){ hideLoader(); });

  // If no data-loading announced, hide on DOM ready
  document.addEventListener('DOMContentLoaded', function(){
    if (!waitingForData) hideLoader();
  });

  // Safety timeout
  setTimeout(hideLoader, 6000);
})();

