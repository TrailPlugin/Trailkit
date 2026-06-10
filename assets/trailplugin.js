/* TrailKit Frontend JS — Leaflet map integration + AJAX filters */
(function () {
  'use strict';

  /* Fix Leaflet default marker icon paths — auto-detection breaks when bundled locally */
  if (typeof L !== 'undefined' && typeof tkData !== 'undefined') {
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
      iconUrl:       tkData.pluginUrl + 'assets/images/marker-icon.png',
      iconRetinaUrl: tkData.pluginUrl + 'assets/images/marker-icon-2x.png',
      shadowUrl:     tkData.pluginUrl + 'assets/images/marker-shadow.png',
    });
  }

  /* ── Map initializer (called from [tk_map] shortcode) ─── */
  window.tkInitMap = function (cfg) {
    if (typeof L === 'undefined') return;

    var map = L.map(cfg.id, {
      center: [cfg.lat, cfg.lng],
      zoom:   cfg.zoom,
      scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 19,
    }).addTo(map);

    // Enable scroll zoom on focus
    map.on('focus', function () { map.scrollWheelZoom.enable(); });
    map.on('blur',  function () { map.scrollWheelZoom.disable(); });

    // Load markers via AJAX
    var data = {
      action: 'tk_get_map_markers',
      nonce:  tkData.nonce,
      type:   cfg.type,
      region: cfg.region || '',
    };

    fetch(tkData.ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(data),
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      if (!res.success) return;
      res.data.forEach(function (m) {
        var color   = m.type === 'route' ? tkDiffColor(m.difficulty) : '#6366f1';
        var icon    = L.divIcon({
          className: 'tk-map-pin',
          html: '<span style="background:' + color + '"></span>',
          iconSize: [16, 16],
          iconAnchor: [8, 8],
        });

        var popup = '<div class="tk-popup">'
          + (m.thumb ? '<img src="' + tkEscape(m.thumb) + '" alt="">' : '')
          + '<strong>' + tkEscape(m.title) + '</strong>'
          + (m.distance ? '<span>' + tkEscape(String(m.distance)) + ' km</span> ' : '')
          + '<a href="' + tkEscape(m.url) + '">' + (typeof tkStrings !== 'undefined' ? tkStrings.view : 'View →') + '</a>'
          + '</div>';

        L.marker([m.lat, m.lng], { icon: icon })
          .addTo(map)
          .bindPopup(popup);
      });
    })
    .catch(function () {});
  };

  function tkEscape(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function tkDiffColor(diff) {
    var map = { easy: '#22c55e', moderate: '#f59e0b', hard: '#ef4444', extreme: '#7c3aed' };
    return map[diff] || '#0df246';
  }

  /* ── Weather widget — Pro only ─────────────────── */
  (function () {
    var widget = document.querySelector('.tk-weather-stat');
    if (!widget) return;

    var lat = widget.dataset.lat;
    var lon = widget.dataset.lon;
    if (!lat || !lon) return;

    // WMO Weather Interpretation Codes → label + emoji
    var wmo = {
      0:  ['☀',  'Clear'],
      1:  ['🌤', 'Mostly clear'],
      2:  ['⛅', 'Partly cloudy'],
      3:  ['☁',  'Overcast'],
      45: ['🌫', 'Fog'],
      48: ['🌫', 'Icy fog'],
      51: ['🌦', 'Light drizzle'],
      53: ['🌦', 'Drizzle'],
      55: ['🌧', 'Heavy drizzle'],
      61: ['🌧', 'Light rain'],
      63: ['🌧', 'Rain'],
      65: ['🌧', 'Heavy rain'],
      71: ['🌨', 'Light snow'],
      73: ['❄',  'Snow'],
      75: ['❄',  'Heavy snow'],
      80: ['🌦', 'Rain showers'],
      81: ['🌧', 'Showers'],
      82: ['⛈',  'Heavy showers'],
      95: ['⛈',  'Thunderstorm'],
      96: ['⛈',  'Thunderstorm + hail'],
      99: ['⛈',  'Heavy thunderstorm'],
    };

    var url = 'https://api.open-meteo.com/v1/forecast'
      + '?latitude=' + encodeURIComponent(lat)
      + '&longitude=' + encodeURIComponent(lon)
      + '&current=temperature_2m,weathercode,windspeed_10m'
      + '&wind_speed_unit=kmh&timezone=auto';

    fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var c    = data.current;
        var code = c.weathercode;
        var temp = Math.round(c.temperature_2m);
        var wind = Math.round(c.windspeed_10m);
        var info = wmo[code] || ['🌡', 'Weather'];

        var display = document.getElementById('tk-weather-display');
        if (!display) return;
        display.innerHTML =
          '<span class="tk-weather-icon" aria-hidden="true">' + info[0] + '</span> ' +
          '<span class="tk-weather-temp">' + temp + '°C</span>';
        widget.title = info[1] + ' · Wind: ' + wind + ' km/h';
        widget.setAttribute('aria-label', info[1] + ', ' + temp + ' degrees, wind ' + wind + ' km/h');
      })
      .catch(function () {
        var display = document.getElementById('tk-weather-display');
        if (display) display.textContent = '—';
      });
  }());

  /* ── Gallery lightbox — Pro only ───────────────── */
  if (typeof tkData !== 'undefined' && !tkData.lite) {
    document.addEventListener('click', function (e) {
      var item = e.target.closest('.tk-gallery__item');
      if (!item) return;
      e.preventDefault();
      var gallery = item.closest('.tk-gallery');
      var items   = Array.from(gallery.querySelectorAll('.tk-gallery__item'));
      tkOpenLightbox(items.map(function (a) { return a.href; }), items.indexOf(item));
    });
  }

  function tkOpenLightbox(urls, idx) {
    var lb = document.getElementById('tk-lightbox');
    if (!lb) {
      lb = document.createElement('div');
      lb.id = 'tk-lightbox';
      lb.innerHTML =
        '<div class="tk-lb__overlay"></div>' +
        '<div class="tk-lb__wrap">' +
          '<button class="tk-lb__close" aria-label="Close">&#x2715;</button>' +
          '<button class="tk-lb__prev" aria-label="Previous">&#x2039;</button>' +
          '<img class="tk-lb__img" src="" alt="">' +
          '<button class="tk-lb__next" aria-label="Next">&#x203A;</button>' +
          '<div class="tk-lb__counter"></div>' +
        '</div>';
      document.body.appendChild(lb);
      lb.querySelector('.tk-lb__overlay').addEventListener('click', tkCloseLightbox);
      lb.querySelector('.tk-lb__close').addEventListener('click', tkCloseLightbox);
      lb.querySelector('.tk-lb__prev').addEventListener('click', function () { tkLbNav(-1); });
      lb.querySelector('.tk-lb__next').addEventListener('click', function () { tkLbNav(1); });
      document.addEventListener('keydown', function (e) {
        if (!lb.classList.contains('is-open')) return;
        if (e.key === 'Escape')      tkCloseLightbox();
        if (e.key === 'ArrowLeft')   tkLbNav(-1);
        if (e.key === 'ArrowRight')  tkLbNav(1);
      });
    }
    lb._urls = urls;
    lb._idx  = idx;
    tkLbShow();
  }

  function tkLbShow() {
    var lb  = document.getElementById('tk-lightbox');
    var img = lb.querySelector('.tk-lb__img');
    img.src = lb._urls[lb._idx];
    lb.querySelector('.tk-lb__counter').textContent = (lb._idx + 1) + ' / ' + lb._urls.length;
    lb.querySelector('.tk-lb__prev').style.display = lb._urls.length > 1 ? '' : 'none';
    lb.querySelector('.tk-lb__next').style.display = lb._urls.length > 1 ? '' : 'none';
    lb.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function tkCloseLightbox() {
    var lb = document.getElementById('tk-lightbox');
    if (!lb) return;
    lb.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function tkLbNav(dir) {
    var lb = document.getElementById('tk-lightbox');
    lb._idx = (lb._idx + dir + lb._urls.length) % lb._urls.length;
    tkLbShow();
  }

})();

/* ── Admin: gallery media picker & guide photo picker ─── */
(function ($) {
  if (typeof wp === 'undefined' || !wp.media) return;

  // Gallery picker
  $(document).on('click', '.tk-gallery-btn', function (e) {
    e.preventDefault();
    var btn     = $(this);
    var target  = btn.data('target');
    var preview = btn.data('preview');

    var frame = wp.media({
      title:    'Select Images',
      button:   { text: 'Use these images' },
      multiple: true,
    });

    frame.on('select', function () {
      var ids  = frame.state().get('selection').map(function (a) { return a.id; });
      var urls = frame.state().get('selection').map(function (a) { return a.attributes.sizes.thumbnail ? a.attributes.sizes.thumbnail.url : a.attributes.url; });
      $('#' + target).val(JSON.stringify(ids));
      var html = '';
      urls.forEach(function (url) {
        html += '<img src="' + url + '" width="60" height="60" style="object-fit:cover;border-radius:4px">';
      });
      $('#' + preview).html(html);
    });

    frame.open();
  });

  // Single photo picker
  $(document).on('click', '.tk-photo-btn', function (e) {
    e.preventDefault();
    var btn     = $(this);
    var target  = btn.data('target');
    var preview = btn.data('preview');

    var frame = wp.media({
      title:    'Select Photo',
      button:   { text: 'Use this photo' },
      multiple: false,
      library:  { type: 'image' },
    });

    frame.on('select', function () {
      var a = frame.state().get('selection').first();
      var url = a.attributes.sizes && a.attributes.sizes.thumbnail
        ? a.attributes.sizes.thumbnail.url : a.attributes.url;
      $('#' + target).val(a.id);
      $('#' + preview).html('<img src="' + url + '" style="width:100%;border-radius:6px;margin-bottom:8px">');
    });

    frame.open();
  });

  // Remove photo
  $(document).on('click', '.tk-photo-remove', function (e) {
    e.preventDefault();
    var btn     = $(this);
    var target  = btn.data('target');
    var preview = btn.data('preview');
    $('#' + target).val('');
    $('#' + preview).html('<div style="width:100%;height:120px;background:#f0f0f0;border-radius:6px;margin-bottom:8px;display:flex;align-items:center;justify-content:center;color:#999">No photo</div>');
  });

}(window.jQuery));

/* ── Admin: GPX file → JSON points (Pro) ────────────
   Reads the .gpx file client-side, extracts trkpt/rtept
   coordinates, simplifies with RDP if > 500 points,
   and writes JSON into the _tk_points textarea.       */
(function () {
  // Script runs in WP footer — DOM is already complete, no DOMContentLoaded needed.
  var trigger   = document.getElementById('tk-gpx-trigger');
  var fileInput = document.getElementById('tk-gpx-file');
  var textarea  = document.querySelector('textarea[name="_tk_points"]');
  var status    = document.getElementById('tk-gpx-status');
  if (!trigger || !fileInput || !textarea) return;

  // Button click → open native file picker
  trigger.addEventListener('click', function () {
    fileInput.value = ''; // reset so same file can be re-selected
    fileInput.click();
  });

  fileInput.addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;

    setStatus('⏳ ' + file.name + '…', '#6b7280');
    trigger.disabled = true;

    var reader = new FileReader();
    reader.onload = function (ev) {
      try {
        var xml    = new DOMParser().parseFromString(ev.target.result, 'text/xml');
        var points = parseGpx(xml);

        if (points.length === 0) {
          setStatus('⚠ No GPS points found. Check the file contains <trkpt> elements.', '#b45309');
          trigger.disabled = false;
          return;
        }

        var original = points.length;
        if (points.length > 500) {
          points = rdpSimplify(points, 0.00004);
        }

        textarea.value = JSON.stringify(points, null, 2);

        // Auto-fill lat/lng/elevation from first point when fields are empty
        var first = points[0];
        var latInput = document.querySelector('input[name="_tk_lat"]');
        var lngInput = document.querySelector('input[name="_tk_lng"]');
        if (latInput && lngInput && (!latInput.value || !lngInput.value)) {
          latInput.value = first.lat;
          lngInput.value = first.lng;
          latInput.style.background = '#f0fff4';
          lngInput.style.background = '#f0fff4';
        }
        var eleInput = document.querySelector('input[name="_tk_elevation"]');
        if (eleInput && !eleInput.value && first.ele) {
          eleInput.value = first.ele;
          eleInput.style.background = '#f0fff4';
        }

        var msg = '✓ ' + points.length + ' points imported';
        if (original > 500) msg += ' (simplified from ' + original + ')';
        setStatus(msg, '#166534');
        trigger.disabled = false;
        textarea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } catch (err) {
        setStatus('✗ Error: ' + err.message, '#991b1b');
        trigger.disabled = false;
      }
    };
    reader.onerror = function () {
      setStatus('✗ Could not read the file.', '#991b1b');
      trigger.disabled = false;
    };
    reader.readAsText(file);
  });

  function setStatus(msg, color) {
    if (!status) return;
    status.textContent = msg;
    status.style.color = color;
  }

  function parseGpx(xml) {
    var pts = [];
    // getElementsByTagNameNS('*', ...) handles files with or without XML namespaces.
    // querySelectorAll('trkpt') silently returns empty when xmlns is declared.
    var trkpts = xml.getElementsByTagNameNS('*', 'trkpt');
    if (!trkpts.length) trkpts = xml.getElementsByTagNameNS('*', 'rtept');

    Array.from(trkpts).forEach(function (pt) {
      var lat = parseFloat(pt.getAttribute('lat'));
      var lng = parseFloat(pt.getAttribute('lon'));
      if (isNaN(lat) || isNaN(lng)) return;
      var obj = { lat: Math.round(lat * 1e6) / 1e6, lng: Math.round(lng * 1e6) / 1e6 };
      var eleEls = pt.getElementsByTagNameNS('*', 'ele');
      if (eleEls.length) {
        var ele = parseFloat(eleEls[0].textContent);
        if (!isNaN(ele)) obj.ele = Math.round(ele);
      }
      pts.push(obj);
    });
    return pts;
  }

  // Ramer–Douglas–Peucker simplification (operates on lat/lng degrees)
  function rdpSimplify(pts, eps) {
    if (pts.length <= 2) return pts;
    var dmax = 0, idx = 0;
    var end = pts.length - 1;
    for (var i = 1; i < end; i++) {
      var d = perpDist(pts[i], pts[0], pts[end]);
      if (d > dmax) { dmax = d; idx = i; }
    }
    if (dmax > eps) {
      var r1 = rdpSimplify(pts.slice(0, idx + 1), eps);
      var r2 = rdpSimplify(pts.slice(idx), eps);
      return r1.slice(0, -1).concat(r2);
    }
    return [pts[0], pts[end]];
  }

  function perpDist(pt, a, b) {
    var dlat = b.lat - a.lat;
    var dlng = b.lng - a.lng;
    if (dlat === 0 && dlng === 0) {
      return Math.sqrt(Math.pow(pt.lat - a.lat, 2) + Math.pow(pt.lng - a.lng, 2));
    }
    var t = ((pt.lat - a.lat) * dlat + (pt.lng - a.lng) * dlng) / (dlat * dlat + dlng * dlng);
    t = Math.max(0, Math.min(1, t));
    return Math.sqrt(Math.pow(pt.lat - (a.lat + t * dlat), 2) + Math.pow(pt.lng - (a.lng + t * dlng), 2));
  }
}());
