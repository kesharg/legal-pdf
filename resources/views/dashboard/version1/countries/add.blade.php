
@extends('dashboard.version1.layouts.main')

@section('title', ' - Create Country')
@section('top-header', 'New Country')

@section('content')
    <form class="forms-sample" action="{{ route('admin.countries.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include("dashboard.version1.countries.form")
    </form>
@stop
