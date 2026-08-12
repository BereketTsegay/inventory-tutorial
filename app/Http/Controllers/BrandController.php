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
        $headers = array_filter($headers, function ($header) {
            return $header !== 'created_at' && $header !== 'updated_at';
        });

        $brands = Brand::all(); // Adjust the number of items per page as needed

        return view('pages.brand.all', compact('brands', 'headers'));
    }

    public function FormBrand($id = null)
    {
        $brand = !!$id ? Brand::find($id) : new Brand();
        return view('pages.brand.add', compact('brand'));
    }


    public function StoreBrand(BrandRequest $request)
    {



        $brand = $request->input('id') ? Brand::whereId($request->input('id'))->first() : new Brand();

        $brand->brand_name = $request->input('brand_name');

        if ($request->hasFile('brand_image')) {
            $image = $request->file('brand_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/brands'), $imageName);
            $brand->brand_image = 'uploads/brands/' . $imageName;
        }

        $brand->save();

        $notification = array(
            'message' => 'Brand ' . $brand->brand_name . ' saved successfully.',
            'alert-type' => 'success'
        );

        return redirect()->route('all.brand')->with($notification);
    }

    public function DeleteBrand(string $id)
    {

        $brand = Brand::whereId($id)->first();

        //delete the brand image file if it exists
        if ($brand->brand_image && file_exists(public_path($brand->brand_image))) {
            unlink(public_path($brand->brand_image));
        }

        $brand->delete();

        $notification = array(
            'message' => 'Brand deleted successfully.',
            'alert-type' => 'success'
        );

        return redirect()->route('all.brand')->with($notification);
    }
}
