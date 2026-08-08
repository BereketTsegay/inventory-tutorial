<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BrandController extends Controller
{
    //

    public function AllBrand()
    {
        return view('pages.brand.all');
    }

    public function AddBrand()
    {
        return view('pages.brand.add');
    }
}
