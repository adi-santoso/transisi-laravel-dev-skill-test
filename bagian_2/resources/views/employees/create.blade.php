@extends('layouts.app')

@section('content')
    <div class="">
        <h3>Tambah Employee</h3>

        <form class="mt-3" method="POST" action="{{route('employees.store')}}">
            @csrf
            @include('employees._form')
        </form>
    </div>
@endsection

@push('scripts')
@endpush
