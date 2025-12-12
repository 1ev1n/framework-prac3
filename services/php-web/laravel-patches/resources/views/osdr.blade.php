@extends('layouts.app')

@section('content')
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">NASA OSDR</h3>
    <div class="small text-muted">Источник: {{ $src }}</div>
  </div>

  {{-- Фильтры и поиск --}}
  <div class="card mb-3">
    <div class="card-body">
      <form id="filterForm" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label small">Поиск</label>
          <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Ключевые слова...">
        </div>
        <div class="col-md-2">
          <label class="form-label small">Столбец</label>
          <select class="form-select form-select-sm" id="sortColumn">
            <option value="id">ID</option>
            <option value="dataset_id">Dataset ID</option>
            <option value="title">Название</option>
            <option value="updated_at" selected>Дата обновления</option>
            <option value="inserted_at">Дата добавления</option>
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
          <label class="form-label small">Лимит</label>
          <input type="number" class="form-control form-control-sm" id="limitInput" value="20" min="1" max="100">
        </div>
        <div class="col-md-3">
          <button type="button" class="btn btn-primary btn-sm" id="applyFilters">Применить</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="resetFilters">Сбросить</button>
        </div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle table-hover">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>dataset_id</th>
          <th>title</th>
          <th>REST_URL</th>
          <th>updated_at</th>
          <th>inserted_at</th>
          <th>raw</th>
        </tr>
      </thead>
      <tbody id="osdrTableBody">
      @forelse($items as $row)
        <tr data-row-id="{{ $row['id'] }}" 
            data-dataset-id="{{ $row['dataset_id'] ?? '' }}"
            data-title="{{ strtolower($row['title'] ?? '') }}"
            data-updated="{{ $row['updated_at'] ?? '' }}"
            data-inserted="{{ $row['inserted_at'] ?? '' }}">
          <td>{{ $row['id'] }}</td>
          <td>{{ $row['dataset_id'] ?? '—' }}</td>
          <td style="max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            {{ $row['title'] ?? '—' }}
          </td>
          <td>
            @if(!empty($row['rest_url']))
              <a href="{{ $row['rest_url'] }}" target="_blank" rel="noopener">открыть</a>
            @else — @endif
          </td>
          <td>{{ $row['updated_at'] ?? '—' }}</td>
          <td>{{ $row['inserted_at'] ?? '—' }}</td>
          <td>
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#raw-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">JSON</button>
          </td>
        </tr>
        <tr class="collapse" id="raw-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">
          <td colspan="7">
            <pre class="mb-0" style="max-height:260px;overflow:auto">{{ json_encode($row['raw'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) }}</pre>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center text-muted">нет данных</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  
  <div id="noResults" class="alert alert-info d-none">Результаты не найдены</div>
</div>

<style>
  .table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.05);
    transition: background-color 0.2s ease;
  }
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  .table tbody tr {
    animation: fadeIn 0.3s ease;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchInput');
  const sortColumn = document.getElementById('sortColumn');
  const sortOrder = document.getElementById('sortOrder');
  const limitInput = document.getElementById('limitInput');
  const applyBtn = document.getElementById('applyFilters');
  const resetBtn = document.getElementById('resetFilters');
  const tableBody = document.getElementById('osdrTableBody');
  const noResults = document.getElementById('noResults');
  let allRows = Array.from(tableBody.querySelectorAll('tr[data-row-id]'));

  function filterAndSort() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const column = sortColumn.value;
    const order = sortOrder.value;
    const limit = parseInt(limitInput.value) || 20;

    let filtered = allRows.filter(row => {
      if (!searchTerm) return true;
      const title = row.getAttribute('data-title') || '';
      const datasetId = row.getAttribute('data-dataset-id') || '';
      return title.includes(searchTerm) || datasetId.includes(searchTerm);
    });

    filtered.sort((a, b) => {
      let aVal, bVal;
      if (column === 'id') {
        aVal = parseInt(a.getAttribute('data-row-id') || 0);
        bVal = parseInt(b.getAttribute('data-row-id') || 0);
      } else if (column === 'updated_at' || column === 'inserted_at') {
        aVal = new Date(a.getAttribute('data-' + column.replace('_', '-')) || 0).getTime();
        bVal = new Date(b.getAttribute('data-' + column.replace('_', '-')) || 0).getTime();
      } else {
        aVal = (a.getAttribute('data-' + column.replace('_', '-')) || '').toLowerCase();
        bVal = (b.getAttribute('data-' + column.replace('_', '-')) || '').toLowerCase();
      }

      if (order === 'asc') {
        return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
      } else {
        return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
      }
    });

    tableBody.innerHTML = '';
    const limited = filtered.slice(0, limit);
    limited.forEach(row => tableBody.appendChild(row));
    
    if (limited.length === 0) {
      noResults.classList.remove('d-none');
    } else {
      noResults.classList.add('d-none');
    }
  }

  applyBtn.addEventListener('click', filterAndSort);
  resetBtn.addEventListener('click', function() {
    searchInput.value = '';
    sortColumn.value = 'updated_at';
    sortOrder.value = 'desc';
    limitInput.value = '20';
    filterAndSort();
  });

  searchInput.addEventListener('keyup', function(e) {
    if (e.key === 'Enter') filterAndSort();
  });

  filterAndSort();
});
</script>
@endsection
