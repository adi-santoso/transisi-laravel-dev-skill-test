@extends('layouts.app')

@section('content')
    <div class="">
        <h3>Edit Employee</h3>

        <form class="mt-3" method="POST" action="{{route('employees.update', $employee->id)}}">
            @csrf
            @method('PUT')
            @include('employees._form')
        </form>
    </div>
@endsection

@push('scripts')
@endpush
