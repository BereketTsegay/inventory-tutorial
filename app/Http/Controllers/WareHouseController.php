<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WareHouseController extends Controller
{
    //

    public function AllWareHouse()
    {
        $headers = \Illuminate\Support\Facades\Schema::getColumnListing((new \App\Models\WareHouse)->getTable());
        $headers = array_filter($headers, function ($header) {
            return $header !== 'created_at' && $header !== 'updated_at';
        });
        $warehouses = \App\Models\WareHouse::paginate(10);
        return view('pages.warehouse.all', compact('warehouses', 'headers'));
    }
}
