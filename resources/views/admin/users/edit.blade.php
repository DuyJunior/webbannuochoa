@extends('layouts.admin')
@section('title', __('Chỉnh sửa tài khoản'))
@section('page_title', __('Chỉnh sửa tài khoản'))
@section('content')
@include('admin.users._form')
@endsection
