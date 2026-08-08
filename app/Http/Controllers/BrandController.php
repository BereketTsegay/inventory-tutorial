<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Http\Requests\BrandRequest;
use Illuminate\Support\Facades\Schema;

class BrandController extends Controller
{
    //

    public function AllBrand()
    {
        $headers = Schema::getColumnListing((new Brand)->getTable());

        $brands = Brand::all(); // Adjust the number of items per page as needed

        return view('pages.brand.all', compact('brands', 'headers'));
    }

    public function AddBrand()
    {
        return view('pages.brand.add');
    }

    public function StoreBrand(BrandRequest $request)
    {
        
        $brand = $request->id ? Brand::find($request->id) : new \App\Models\Brand();
        $brand->brand_name = $request->input('name');

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/brands'), $imageName);
            $brand->brand_image = 'uploads/brands/' . $imageName;
        }

        $brand->save();

        return redirect()->route('all.brand')->with('success', 'Brand ' . $brand->brand_name . ' saved successfully.');
    }

    public function DeleteBrand($id)
    {
        $brand = Brand::findOrFail($id);
        
        //delete the brand image file if it exists
        if ($brand->brand_image && file_exists(public_path($brand->brand_image))) {
            unlink(public_path($brand->brand_image));
        }

        $brand->delete();

        return redirect()->route('all.brand')->with('success', 'Brand deleted successfully.');
    }
}
