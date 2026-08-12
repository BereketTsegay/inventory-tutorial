@extends('admin.master_master')

@section('admin')
     <!-- Start Content-->
                    <div class="container-xxl">

                        <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                            <div class="flex-grow-1">
                                <h4 class="fs-18 fw-semibold m-0">All Warehouses</h4>
                            </div>

                            <div class="text-end">
                                <ol class="breadcrumb m-0 py-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Tables</a></li>
                                    <li class="breadcrumb-item active">All Warehouses</li>
                                </ol>
                            </div>
                        </div>

                       <!-- Datatables  -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card ">

                                    <div class="card-header d-flex align-items-sm-center flex-sm-row flex-column">
                                        <h5 class="card-title mb-0 flex-grow-1">Basic Datatable</h5>
                                        <div class="text-end">
                                            <a href="{{ route('dynamic.form.create', ['table' => (new \App\Models\WareHouse)->getTable()]) }}" class="btn btn-primary align-self-end">Add Warehouse</a>
                                        </div>

                                    </div><!-- end card header -->

                                    <div class="card-body">
                                        <table id="datatable" class="table table-bordered dt-responsive table-responsive nowrap">
                                            <thead>
                                            <tr>
                                                @foreach ($headers as $comlumn)


                                                        <th>{{ ucfirst(preg_replace('/_/', ' ', $comlumn)) }}</th>

                                                @endforeach
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                                    @foreach ($warehouses as $warehouse)
                                                        <tr>
                                                            @foreach ($headers as $comlumn)
                                                                 @if(str_contains($comlumn, 'image') && $warehouse->$comlumn)
                                                                    <td>
                                                                        <img src="{{ asset($warehouse->$comlumn) }}" alt="Warehouse Image" class="avatar avatar-sm rounded-2 me-3">
                                                                    </td>
                                                                @else
                                                                    <td>{{ $warehouse->$comlumn }}</td>
                                                                @endif
                                                            @endforeach
                                                            <td>
                                                                <a href="{{ route('dynamic.form.edit', [(new \App\Models\Warehouse)->getTable(), $warehouse->id]) }}" class="btn btn-sm btn-outline-primary">Edit</a>

                                                                <form action="{{ route('dynamic.form.destroy', [(new \App\Models\Warehouse)->getTable(), $warehouse->id]) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this warehouse?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                                </form>

                                                            </td>
                                                        </tr>
                                                    @endforeach


                                                  @if (count($warehouses) === 0)
                                                    <tr>
                                                        <td colspan="{{ count($headers) + 1 }}" class="text-center">No warehouse found.</td>
                                                    </tr>

                                                  @endif
                                            </tbody>
                                            <table class="table table-bordered">
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="{{ count($headers) + 1 }}">
                                                            {{ $warehouses->links() }}
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                        </table>
                                    </div>

                                </div>
                            </div>
                        </div>



                    </div> <!-- container-fluid -->

@endsection
