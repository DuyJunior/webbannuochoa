<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;

class AdminController extends Controller
{

    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function products()
    {
        return app(ProductController::class)->index();
    }

    public function categories()
    {
        return app(CategoryController::class)->index();
    }
}
