@extends('layouts.admin')
@section('title', __('Chỉnh sửa video'))
@section('page_title', __('Chỉnh sửa video'))
@section('content')
    @include('admin.videos.form')
@endsection
