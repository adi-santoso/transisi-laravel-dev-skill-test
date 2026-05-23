@extends('layouts.app')

@section('content')
    <div class="d-flex flex-column">
        <div class="d-flex justify-content-end">
            <a href="{{route('companies.create')}}"  class=" btn btn-primary justify-content-end">Tambah Company</a>
        </div>

        <table class="table table-hover">
            <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Jumlah Employee</th>
                <th>Logo</th>
                <th>Website</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse($companies as $company)
                <tr>
                    <td>{{ ($companies->currentPage() - 1) * $companies->perPage() + $loop->iteration }}
                    </td>
                    <td>{{$company->name}}</td>
                    <td>{{$company->email}}</td>
                    <td>{{$company->employees_count}}</td>
                    <td>
                        <img src="{{ route('companies.logo', $company) }}"
                             style="max-height: 50px;" class="d-block mb-1">
                    </td>
                    <td>{{$company->website}}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{route('companies.edit', $company->id)}}" class="btn btn-primary">
                                <i class="mdi mdi-pencil"></i>
                            </a>

                            <form method="post" action="{{route('companies.destroy', $company->id)}}">
                                @csrf
                                @method('DELETE')
                                @php
                                    $count = $company->employees_count;
                                    $confirmMsg = "Hapus company {$company->name}?";
                                    if ($count > 0) {
                                        $confirmMsg .= "\\n\\n{$count} employee terkait juga akan ikut terhapus.";
                                    }
                                    $confirmMsg .= "\\n\\nLanjutkan?";
                                @endphp
                                <button type="submit" class="btn btn-danger" onclick="return confirm('{{ $confirmMsg }}');">
                                    <i class="mdi mdi-trash-can"></i>
                                </button>
                            </form>

                            <a href="{{ route('companies.export-employees', $company->id) }}" class="btn btn-info" target="_blank">
                                Export Employee
                            </a>

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Belum ada data</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="d-flex justify-content-center mt-3">
            {{ $companies->links() }}
        </div>
    </div>
@endsection

@push('scripts')
@endpush
