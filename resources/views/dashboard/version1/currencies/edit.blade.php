@extends('dashboard.version1.layouts.main')

@section('title', 'Update Currency')
@section('top-header', 'Update Currency')

@section('content')
    <form class="forms-sample" action="{{ route('admin.currencies.update',["id" =>$currency->id]) }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @method("PUT")

        @include("dashboard.version1.currencies.form")
    </form>
@stop
