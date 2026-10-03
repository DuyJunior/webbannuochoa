@extends('layouts.admin')
@section('title', __('Thêm video'))
@section('page_title', __('Thêm video'))
@section('content')
    @include('admin.videos.form', ['video' => new \App\Models\Video()])
@endsection
