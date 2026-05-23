@extends('layouts.app')

@section('content')
    <div class="d-flex flex-column">
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('employees.import.form') }}" class="btn btn-success">
                <i class="mdi mdi-file-excel"></i> Import Excel
            </a>
            <a href="{{route('employees.create')}}"  class=" btn btn-primary">Tambah Employee</a>
        </div>

        <table class="table table-hover">
            <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Company</th>
                <th>Email</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse($employees as $employee)
                <tr>
                    <td>{{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
                    </td>
                    <td>{{$employee->name}}</td>
                    <td>{{$employee->company->name}}</td>
                    <td>{{$employee->email}}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{route('employees.edit', $employee->id)}}" class="btn btn-primary">
                                <i class="mdi mdi-pencil"></i>
                            </a>

                            <form method="post" action="{{route('employees.destroy', $employee->id)}}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin ingin Hapus?');">
                                    <i class="mdi mdi-trash-can"></i>
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Belum ada data</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="d-flex justify-content-center mt-3">
            {{ $employees->links() }}
        </div>
    </div>
@endsection

@push('scripts')
@endpush
