
$(function () {

  // Hamburger 
  $('#navHamburger').on('click', function () {
    $('#navLinks').toggleClass('open');
  });
  $(document).on('click', function (e) {
    if (!$(e.target).closest('.navbar').length) $('#navLinks').removeClass('open');
  });

  // User dropdown 
  $('#userMenuBtn').on('click', function (e) {
    e.stopPropagation();
    $('#userDropdown').toggleClass('open');
  });
  $(document).on('click', function () { $('#userDropdown').removeClass('open'); });

  //  Active nav link 
  var curPage = window.location.pathname.split('/').pop();
  $('.nav-link').each(function () {
    var linkPage = ($(this).attr('href') || '').split('/').pop().split('?')[0];
    if (linkPage && linkPage === curPage) {
      $(this).addClass('active');
    }
  });

  //  Password show/hide 
  $(document).on('click', '.input-toggle', function () {
    var $inp  = $(this).closest('.input-wrap').find('input');
    var hide  = $inp.attr('type') === 'password';
    $inp.attr('type', hide ? 'text' : 'password');
    $(this).text(hide ? '🙈' : '👁️');
  });

});

// Toast 
window.showToast = function (msg, type) {
  type = type || 'info';
  var icons = { success: '✅', error: '❌', info: '💕' };
  if (!$('#toast-wrap').length) $('body').append('<div id="toast-wrap"></div>');
  var $t = $('<div class="toast toast-' + type + '">').html((icons[type]||'') + ' ' + msg);
  $('#toast-wrap').append($t);
  setTimeout(function () { $t.fadeOut(300, function () { $(this).remove(); }); }, 3500);
};