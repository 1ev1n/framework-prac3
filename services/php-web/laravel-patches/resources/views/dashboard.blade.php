@extends('layouts.app')

@section('content')
<div class="container pb-5">
  {{-- верхние карточки --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="border rounded p-3 text-center card-hover">
        <div class="small text-muted">Скорость МКС</div>
        <div class="fs-4" id="iss-speed">{{ isset(($iss['payload'] ?? [])['velocity']) ? number_format($iss['payload']['velocity'],0,'',' ') : '—' }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="border rounded p-3 text-center card-hover">
        <div class="small text-muted">Высота МКС</div>
        <div class="fs-4" id="iss-alt">{{ isset(($iss['payload'] ?? [])['altitude']) ? number_format($iss['payload']['altitude'],0,'',' ') : '—' }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="border rounded p-3 text-center card-hover">
        <div class="small text-muted">Широта</div>
        <div class="fs-4" id="iss-lat">{{ isset(($iss['payload'] ?? [])['latitude']) ? number_format($iss['payload']['latitude'],2,'.',' ') : '—' }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="border rounded p-3 text-center card-hover">
        <div class="small text-muted">Долгота</div>
        <div class="fs-4" id="iss-lon">{{ isset(($iss['payload'] ?? [])['longitude']) ? number_format($iss['payload']['longitude'],2,'.',' ') : '—' }}</div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    {{-- карта МКС --}}
    <div class="col-lg-7">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title">МКС — положение и движение</h5>
          <div id="map" class="rounded mb-2 border" style="height:300px"></div>
          <div class="row g-2">
            <div class="col-6"><canvas id="issSpeedChart" height="110"></canvas></div>
            <div class="col-6"><canvas id="issAltChart"   height="110"></canvas></div>
          </div>
          <div class="mt-3">
            <a href="/iss" class="btn btn-primary btn-sm">Подробнее о МКС</a>
          </div>
        </div>
      </div>
    </div>

    {{-- быстрые ссылки --}}
    <div class="col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title">Быстрый доступ</h5>
          <div class="list-group">
            <a href="/iss" class="list-group-item list-group-item-action">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">МКС данные</h6>
              </div>
              <p class="mb-1">Подробная информация о Международной космической станции</p>
            </a>
            <a href="/osdr" class="list-group-item list-group-item-action">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">NASA OSDR</h6>
              </div>
              <p class="mb-1">Открытые научные данные NASA</p>
            </a>
            <a href="/jwst" class="list-group-item list-group-item-action">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">JWST галерея</h6>
              </div>
              <p class="mb-1">Изображения с телескопа Джеймса Уэбба</p>
            </a>
            <a href="/astro" class="list-group-item list-group-item-action">
              <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">Астрономические события</h6>
              </div>
              <p class="mb-1">События в астрономии на ближайшие дни</p>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .card-hover {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .card {
    animation: fadeIn 0.5s ease;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', async function () {
  if (typeof L !== 'undefined' && typeof Chart !== 'undefined') {
    const last = @json(($iss['payload'] ?? []));
    let lat0 = Number(last.latitude || 0), lon0 = Number(last.longitude || 0);
    const map = L.map('map', { attributionControl:false }).setView([lat0||0, lon0||0], lat0?3:2);
    L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', { noWrap:true }).addTo(map);
    const trail  = L.polyline([], {weight:3}).addTo(map);
    const marker = L.marker([lat0||0, lon0||0]).addTo(map).bindPopup('МКС');

    const speedChart = new Chart(document.getElementById('issSpeedChart'), {
      type: 'line', data: { labels: [], datasets: [{ label: 'Скорость', data: [], borderColor: 'rgb(75, 192, 192)', tension: 0.1 }] },
      options: { responsive: true, scales: { x: { display: false } } }
    });
    const altChart = new Chart(document.getElementById('issAltChart'), {
      type: 'line', data: { labels: [], datasets: [{ label: 'Высота', data: [], borderColor: 'rgb(255, 99, 132)', tension: 0.1 }] },
      options: { responsive: true, scales: { x: { display: false } } }
    });

    async function loadTrend() {
      try {
        const r = await fetch('/api/iss/trend?limit=240');
        const js = await r.json();
        const pts = Array.isArray(js.points) ? js.points.map(p => [p.lat, p.lon]) : [];
        if (pts.length) {
          trail.setLatLngs(pts);
          marker.setLatLng(pts[pts.length-1]);
        }
        const t = (js.points||[]).map(p => new Date(p.at).toLocaleTimeString());
        speedChart.data.labels = t;
        speedChart.data.datasets[0].data = (js.points||[]).map(p => p.velocity);
        speedChart.update();
        altChart.data.labels = t;
        altChart.data.datasets[0].data = (js.points||[]).map(p => p.altitude);
        altChart.update();
      } catch(e) {}
    }
    
    async function updateMetrics() {
      try {
        const r = await fetch('/api/iss/last');
        const js = await r.json();
        if (js.payload) {
          document.getElementById('iss-speed').textContent = number_format(js.payload.velocity || 0, 0, '', ' ');
          document.getElementById('iss-alt').textContent = number_format(js.payload.altitude || 0, 0, '', ' ');
          document.getElementById('iss-lat').textContent = number_format(js.payload.latitude || 0, 2, '.', ' ');
          document.getElementById('iss-lon').textContent = number_format(js.payload.longitude || 0, 2, '.', ' ');
        }
      } catch(e) {}
    }
    
    function number_format(number, decimals, dec_point, thousands_sep) {
      number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
      var n = !isFinite(+number) ? 0 : +number,
          prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
          sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
          dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
          s = '',
          toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
          };
      s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
      if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
      }
      if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
      }
      return s.join(dec);
    }
    
    loadTrend();
    updateMetrics();
    setInterval(loadTrend, 15000);
    setInterval(updateMetrics, 10000);
  }
});
</script>
@endsection
