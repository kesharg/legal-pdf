
@extends('dashboard.version1.layouts.main')

@section('title', 'Update Country')
@section('top-header', 'Update Country')

@section('content')
    <form class="forms-sample" action="{{ route('admin.countries.update',["country" =>$country->id]) }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @method("PUT")

        @include("dashboard.version1.countries.form")
    </form>
@stop
