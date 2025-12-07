@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3 class="mb-3">МКС данные</h3>

  {{-- Фильтры и поиск --}}
  <div class="card mb-3">
    <div class="card-body">
      <form id="filterForm" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label small">Поиск по данным</label>
          <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Ключевые слова...">
        </div>
        <div class="col-md-2">
          <label class="form-label small">Сортировка</label>
          <select class="form-select form-select-sm" id="sortColumn">
            <option value="none">Без сортировки</option>
            <option value="altitude">Высота</option>
            <option value="velocity">Скорость</option>
            <option value="latitude">Широта</option>
            <option value="longitude">Долгота</option>
            <option value="time">Время</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small">Порядок</label>
          <select class="form-select form-select-sm" id="sortOrder">
            <option value="asc">По возрастанию</option>
            <option value="desc" selected>По убыванию</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="button" class="btn btn-primary btn-sm" id="applyFilters">Применить</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="resetFilters">Сбросить</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card shadow-sm card-hover">
        <div class="card-body">
          <h5 class="card-title">Последний снимок</h5>
          @if(!empty($last['payload']))
            <ul class="list-group" id="issDataList">
              <li class="list-group-item" data-field="latitude">
                <strong>Широта:</strong> <span>{{ $last['payload']['latitude'] ?? '—' }}</span>
              </li>
              <li class="list-group-item" data-field="longitude">
                <strong>Долгота:</strong> <span>{{ $last['payload']['longitude'] ?? '—' }}</span>
              </li>
              <li class="list-group-item" data-field="altitude">
                <strong>Высота (км):</strong> <span>{{ $last['payload']['altitude'] ?? '—' }}</span>
              </li>
              <li class="list-group-item" data-field="velocity">
                <strong>Скорость (км/ч):</strong> <span>{{ $last['payload']['velocity'] ?? '—' }}</span>
              </li>
              <li class="list-group-item" data-field="time">
                <strong>Время:</strong> <span>{{ $last['fetched_at'] ?? '—' }}</span>
              </li>
            </ul>
          @else
            <div class="text-muted">нет данных</div>
          @endif
          <div class="mt-3"><code>{{ $base }}/last</code></div>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow-sm card-hover">
        <div class="card-body">
          <h5 class="card-title">Тренд движения</h5>
          @if(!empty($trend))
            <ul class="list-group">
              <li class="list-group-item">
                <strong>Движение:</strong> {{ ($trend['movement'] ?? false) ? 'да' : 'нет' }}
              </li>
              <li class="list-group-item">
                <strong>Смещение (км):</strong> {{ number_format($trend['delta_km'] ?? 0, 3, '.', ' ') }}
              </li>
              <li class="list-group-item">
                <strong>Интервал (сек):</strong> {{ $trend['dt_sec'] ?? 0 }}
              </li>
              <li class="list-group-item">
                <strong>Скорость (км/ч):</strong> {{ $trend['velocity_kmh'] ?? '—' }}
              </li>
            </ul>
          @else
            <div class="text-muted">нет данных</div>
          @endif
          <div class="mt-3"><code>{{ $base }}/iss/trend</code></div>
          <div class="mt-3">
            <a class="btn btn-outline-primary" href="/osdr">Перейти к OSDR</a>
            <a class="btn btn-outline-primary" href="/dashboard">На главную</a>
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  .list-group-item {
    transition: background-color 0.2s ease;
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
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchInput');
  const sortColumn = document.getElementById('sortColumn');
  const sortOrder = document.getElementById('sortOrder');
  const applyBtn = document.getElementById('applyFilters');
  const resetBtn = document.getElementById('resetFilters');
  const dataList = document.getElementById('issDataList');
  
  if (!dataList) return;

  let allItems = Array.from(dataList.querySelectorAll('li'));

  function filterAndSort() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const column = sortColumn.value;
    const order = sortOrder.value;

    let filtered = allItems.filter(item => {
      if (!searchTerm) return true;
      const text = item.textContent.toLowerCase();
      return text.includes(searchTerm);
    });

    if (column !== 'none') {
      filtered.sort((a, b) => {
        const aField = a.getAttribute('data-field');
        const bField = b.getAttribute('data-field');
        if (aField !== column || bField !== column) {
          if (aField === column) return -1;
          if (bField === column) return 1;
          return 0;
        }
        const aVal = parseFloat(a.querySelector('span').textContent) || 0;
        const bVal = parseFloat(b.querySelector('span').textContent) || 0;
        return order === 'asc' ? aVal - bVal : bVal - aVal;
      });
    }

    dataList.innerHTML = '';
    filtered.forEach(item => dataList.appendChild(item));
  }

  applyBtn.addEventListener('click', filterAndSort);
  resetBtn.addEventListener('click', function() {
    searchInput.value = '';
    sortColumn.value = 'none';
    sortOrder.value = 'desc';
    filterAndSort();
  });

  searchInput.addEventListener('keyup', function(e) {
    if (e.key === 'Enter') filterAndSort();
  });
});
</script>
@endsection
