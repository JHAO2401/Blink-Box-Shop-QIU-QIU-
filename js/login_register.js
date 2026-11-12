
$(function () {

  //  Tab switching 
  $('.lr-tab').on('click', function () {
    var target = $(this).data('tab');
    $('.lr-tab').removeClass('active');
    $(this).addClass('active');
    $('.lr-panel').removeClass('active');
    $('#' + target).addClass('active');
  });

  // Links inside panels that switch tabs
  $(document).on('click', '.lr-switch-link', function (e) {
    e.preventDefault();
    $('[data-tab="' + $(this).data('tab') + '"]').trigger('click');
  });

  //  Password strength 
  $('#regPassword').on('input', function () {
    var pw  = $(this).val();
    var lvl = getStrength(pw);
    var $g  = $(this).closest('.form-group');
    $g.removeClass('pw-weak pw-medium pw-strong');
    var $lbl = $g.find('.pw-strength-label');
    if (!pw) { $lbl.text(''); return; }
    if (lvl === 1) { $g.addClass('pw-weak');   $lbl.text('Weak');     }
    else if (lvl === 2) { $g.addClass('pw-medium'); $lbl.text('Medium'); }
    else           { $g.addClass('pw-strong');  $lbl.text('Strong ✓'); }
  });

  function getStrength(pw) {
    var s = 0;
    if (pw.length >= 8)           s++;
    if (/[A-Z]/.test(pw))         s++;
    if (/[0-9]/.test(pw))         s++;
    if (/[^A-Za-z0-9]/.test(pw))  s++;
    if (pw.length >= 12)          s++;
    return s <= 2 ? 1 : s <= 3 ? 2 : 3;
  }

  //  Confirm password match 
  $('#regConfirm').on('input', function () {
    var bad = $(this).val().length > 0 && $(this).val() !== $('#regPassword').val();
    $(this).toggleClass('error', bad);
  });

});