@extends('layouts.app')

@section('content')
<div class="container py-3">
  <h3 class="mb-3">CSV файлы</h3>
  
  <div class="table-responsive">
    <table class="table table-striped table-hover">
      <thead class="table-light">
        <tr>
          <th>Имя файла</th>
          <th>Размер</th>
          <th>Дата изменения</th>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
        @forelse($files as $file)
          <tr>
            <td>{{ $file['name'] }}</td>
            <td>{{ number_format($file['size'] / 1024, 2) }} KB</td>
            <td>{{ date('Y-m-d H:i:s', $file['modified']) }}</td>
            <td>
              <a href="/csv/view/{{ $file['name'] }}" class="btn btn-sm btn-primary">Просмотр</a>
              <a href="/csv/export/{{ $file['name'] }}" class="btn btn-sm btn-success">Экспорт XLSX</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center text-muted">Нет CSV файлов</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection


