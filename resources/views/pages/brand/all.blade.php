@extends('admin.master_master')

@section('admin')
     <!-- Start Content-->
                    <div class="container-xxl">

                        <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                            <div class="flex-grow-1">
                                <h4 class="fs-18 fw-semibold m-0">All Brands</h4>
                            </div>
            
                            <div class="text-end">
                                <ol class="breadcrumb m-0 py-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Tables</a></li>
                                    <li class="breadcrumb-item active">All Brands</li>
                                </ol>
                            </div>
                        </div>

                       <!-- Datatables  -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">

                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Basic Datatable</h5>

                                        <a href="{{ route('add.brand') }}" class="btn btn-primary align-self-end">Add Brand</a>
                                    </div><!-- end card header -->

                                    <div class="card-body">
                                        <table id="datatable" class="table table-bordered dt-responsive table-responsive nowrap">
                                            <thead>
                                            <tr>
                                                @foreach ($headers as $comlumn)
                                                    <th>{{ $comlumn }}</th>
                                                @endforeach
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    @foreach ($brands as $brand)
                                                        @foreach ($headers as $comlumn)
                                                            <td>{{ $brand->$comlumn }}</td>
                                                        @endforeach
                                                         <td>
                                                        <a href="{{ route('edit.brand', $brand->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                                        <form action="{{ route('delete.brand', $brand->id) }}" method="POST" style="display: inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button id="delete" type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this brand?')">Delete</button>
                                                        </form>
                                                    </td>
                                                    @endforeach
                                                   
                                                </tr>
                                                  @if (count($brands) === 0)
                                                    <tr>
                                                        <td colspan="{{ count($headers) }}" class="text-center">No brands found.</td>
                                                    </tr>
                                                      
                                                  @endif
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </div>
                        </div>



                    </div> <!-- container-fluid -->

@endsection