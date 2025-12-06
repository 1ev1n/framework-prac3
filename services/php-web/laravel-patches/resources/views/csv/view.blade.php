@extends('layouts.app')

@section('content')
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Просмотр CSV: {{ $filename }}</h3>
    <a href="/csv/export/{{ $filename }}" class="btn btn-success">Экспорт в XLSX</a>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered table-hover">
      <thead class="table-light">
        <tr>
          @foreach($headers as $header)
            <th>{{ $header }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $row)
          <tr>
            @foreach($row as $cell)
              <td>
                @if(preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $cell))
                  <span class="badge bg-info">{{ $cell }}</span>
                @elseif(in_array(strtoupper($cell), ['ИСТИНА', 'ЛОЖЬ', 'TRUE', 'FALSE']))
                  <span class="badge {{ strtoupper($cell) === 'ИСТИНА' || strtoupper($cell) === 'TRUE' ? 'bg-success' : 'bg-danger' }}">
                    {{ strtoupper($cell) === 'ИСТИНА' || strtoupper($cell) === 'TRUE' ? 'ИСТИНА' : 'ЛОЖЬ' }}
                  </span>
                @elseif(is_numeric($cell))
                  <span class="text-primary">{{ number_format((float)$cell, 2) }}</span>
                @else
                  {{ $cell }}
                @endif
              </td>
            @endforeach
          </tr>
        @empty
          <tr><td colspan="{{ count($headers) }}" class="text-center text-muted">Нет данных</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<style>
  .table td {
    transition: background-color 0.2s ease;
  }
  .table tbody tr:hover {
    background-color: rgba(0,0,0,.05);
  }
</style>
@endsection


