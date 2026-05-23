@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Import Employees</h4>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
        <div class="card-body">

            <div class="alert alert-info">
                <strong>Format file Excel:</strong>
                <ul class="mb-0 mt-2">
                    <li>Format: <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code></li>
                    <li>Maksimal ukuran 5MB</li>
                    <li>Heading row di baris pertama</li>
                    <li>Kolom yang diperlukan: <code>name</code>, <code>email</code>, <code>company_name</code></li>
                    <li><code>company_name</code> harus sama persis dengan nama company yang sudah ada di sistem</li>
                </ul>
                <hr class="my-2">
                <small class="text-muted">
                    <strong>Catatan:</strong> Import bersifat <em>all-or-nothing</em>.
                    Jika ada minimal satu baris yang gagal validasi, seluruh import akan dibatalkan
                    dan tidak ada data yang masuk ke database.
                </small>
            </div>

            <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">File Excel</label>
                    <input type="file" name="file"
                           class="form-control @error('file') is-invalid @enderror"
                           accept=".xlsx,.xls,.csv" required>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Upload & Import</button>
            </form>

            @if (session('importErrors'))
                <hr class="my-4">
                <h5 class="text-danger">Detail Error per Baris</h5>
                <p class="text-muted">
                    Total <strong>{{ count(session('importErrors')) }}</strong> baris dengan error.
                    Karena import bersifat all-or-nothing, <strong>tidak ada</strong> data yang masuk ke database.
                    Silakan perbaiki file Excel dan upload ulang.
                </p>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Baris</th>
                                <th>Field</th>
                                <th>Error</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (session('importErrors') as $error)
                                @foreach ($error['errors'] as $field => $messages)
                                    <tr>
                                        <td>{{ $error['row'] }}</td>
                                        <td><code>{{ $field }}</code></td>
                                        <td>
                                            @foreach ($messages as $msg)
                                                <div>{{ $msg }}</div>
                                            @endforeach
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $error['values'][$field] ?? '(kosong)' }}
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>
@endsection
