
$(function () {

  //  Product detail: image gallery 
  $(document).on('click', '.detail-thumb', function () {
    var src = $(this).data('src') || $(this).attr('src');
    $('#mainImg').attr('src', src);
    $('.detail-thumb').removeClass('active');
    $(this).addClass('active');
  });

  //  Product detail: qty +/- 
  $(document).on('click', '.qty-btn-plus', function () {
    var $inp = $(this).closest('.qty-wrap').find('.qty-input, input[name="qty"]');
    var max  = parseInt($inp.attr('max')) || 999;
    var val  = parseInt($inp.val()) || 1;
    if (val < max) $inp.val(val + 1);
  });
  $(document).on('click', '.qty-btn-minus', function () {
    var $inp = $(this).closest('.qty-wrap').find('.qty-input, input[name="qty"]');
    var val  = parseInt($inp.val()) || 1;
    if (val > 1) $inp.val(val - 1);
  });

  //  Cart: prevent qty below 1 
  $(document).on('change', '.qty-input', function () {
    if (parseInt($(this).val()) < 1) $(this).val(1);
  });

  //  Wishlist: heart animation 
  $(document).on('click', '.wishlist-btn', function () {
    $(this).addClass('beating');
    setTimeout(function () { $('.wishlist-btn').removeClass('beating'); }, 400);
  });

  // Checkout: highlight selected address 
  $(document).on('change', 'input[name="address_id"]', function () {
    $('.address-card').css({'border-color': '', 'box-shadow': ''});
    $(this).closest('.address-card').css({
      'border-color': 'var(--pink-400)',
      'box-shadow':   '0 0 0 3px rgba(240,80,138,.12)'
    });
  });
  // Trigger on load for default checked
  $('input[name="address_id"]:checked').trigger('change');

  // Filter pills: category active state 
  var catParam = new URLSearchParams(window.location.search).get('cat') || '';
  $('.filter-pill').each(function () {
    var href = $(this).attr('href') || '';
    var linkCat = new URLSearchParams(href.split('?')[1] || '').get('cat') || '';
    if (catParam === linkCat) $(this).addClass('active');
  });

});

// Heart beat animation 
var style = document.createElement('style');
style.textContent = '.wishlist-btn.beating { transform: scale(1.3); transition: transform .2s; }';
document.head.appendChild(style);

// Malaysia State → City dropdown 
var MY_CITIES = {
  'Johor':                              ['Johor Bahru','Batu Pahat','Kluang','Kota Tinggi','Mersing','Muar','Pontian','Segamat','Kulai','Ayer Hitam'],
  'Kedah':                              ['Alor Setar','Sungai Petani','Kulim','Langkawi','Baling','Pendang','Yan','Kubang Pasu'],
  'Kelantan':                           ['Kota Bharu','Pasir Mas','Tanah Merah','Gua Musang','Kuala Krai','Machang','Tumpat','Pasir Puteh'],
  'Melaka':                             ['Melaka City','Alor Gajah','Jasin','Masjid Tanah','Merlimau','Ayer Keroh'],
  'Negeri Sembilan':                    ['Seremban','Port Dickson','Nilai','Rembau','Tampin','Kuala Pilah','Jelebu'],
  'Pahang':                             ['Kuantan','Temerloh','Bentong','Raub','Jerantut','Pekan','Rompin','Maran'],
  'Perak':                              ['Ipoh','Taiping','Teluk Intan','Sitiawan','Batu Gajah','Kuala Kangsar','Seri Manjung','Manjung'],
  'Perlis':                             ['Kangar','Arau','Padang Besar'],
  'Pulau Pinang':                       ['George Town','Butterworth','Bukit Mertajam','Bayan Lepas','Nibong Tebal','Balik Pulau'],
  'Sabah':                              ['Kota Kinabalu','Sandakan','Tawau','Lahad Datu','Keningau','Semporna','Kudat','Ranau'],
  'Sarawak':                            ['Kuching','Miri','Sibu','Bintulu','Sarikei','Sri Aman','Kapit','Limbang'],
  'Selangor':                           ['Shah Alam','Seri Serdang','Petaling Jaya','Klang','Subang Jaya','Ampang','Sepang','Hulu Langat','Kuala Selangor','Rawang','Kajang','Puchong','Cyberjaya','Putrajaya'],
  'Terengganu':                         ['Kuala Terengganu','Kemaman','Dungun','Marang','Besut','Hulu Terengganu'],
  'Wilayah Persekutuan Kuala Lumpur':   ['Kuala Lumpur','Chow Kit','Cheras','Bangsar','Mont Kiara','Kepong','Setapak','Wangsa Maju','Titiwangsa'],
  'Wilayah Persekutuan Labuan':         ['Labuan'],
  'Wilayah Persekutuan Putrajaya':      ['Putrajaya']
};

window.updateCities = function (state) {
  var $city  = $('#citySelect');
  var cities = MY_CITIES[state] || [];
  $city.empty();
  if (!cities.length) {
    $city.append('<option value="">Select state first</option>');
    return;
  }
  $city.append('<option value="">Select city</option>');
  $.each(cities, function (i, c) {
    $city.append('<option value="' + c + '">' + c + '</option>');
  });
};

$(function () {
  // Trigger on state change
  $('#stateSelect').on('change', function () {
    updateCities($(this).val());
  });
  // On page load (edit mode) — restore city options then select saved city
  var initState = $('#stateSelect').val();
  var initCity  = '';  // set by page
  if (initState) {
    updateCities(initState);
    if (initCity) $('#citySelect').val(initCity);
  }
});