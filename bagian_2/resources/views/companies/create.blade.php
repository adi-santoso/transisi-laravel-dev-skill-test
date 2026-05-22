@extends('layouts.app')

@section('content')
    <div class="">
        <h3>Tambah Company</h3>

        <form class="mt-3" method="POST" action="{{route('companies.store')}}" enctype="multipart/form-data">
            @csrf
            @include('companies._form')
        </form>
    </div>
@endsection

@push('scripts')
@endpush
