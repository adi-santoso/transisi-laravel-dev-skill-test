@extends('layouts.app')

@section('content')
    <div class="">
        <h3>Edit Company</h3>

        <form class="mt-3" method="POST" action="{{route('companies.update', $company->id)}}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('companies._form')
        </form>
    </div>
@endsection

@push('scripts')
@endpush
