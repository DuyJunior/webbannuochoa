@extends('layouts.admin')
@section('title', 'Thêm video')
@section('page_title', 'Thêm video')
@section('content')
    @include('admin.videos.form', ['video' => new \App\Models\Video()])
@endsection
