@extends('admin.master_master')

@section('admin')
    <!-- Start Content-->
    <div class="container-xxl">
        <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
            <div class="flex-grow-1">

                {{-- return back button --}}
                <a href="{{ route('all.brand') }}" class="btn btn-outline-secondary mb-3">Back</a>

                <h4 class="fs-18 fw-semibold m-0">{{ $brand->id ? ucfirst($brand->brand_name).' Open from Edit' : 'Add New Brand' }}</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ $brand->id ? $brand->brand_name : 'Add New' }} Brand</h5>
                    </div>

                   {{-- Display validation errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
              
                    <div class="card-body">
                        <form action="{{ route('store.brand') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="brand_name" class="form-label">Brand Name</label>
                                <input type="text" value="{{ $brand->brand_name }}" class="form-control" id="brand_name" name="brand_name" required>
                           
                                {{-- display validation error for brand_name --}}
                                @if ($errors->has('brand_name'))
                                    <div class="text-danger">{{ $errors->first('brand_name') }}</div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label for="brand_image" class="form-label">Brand Image</label>
                                <input type="file" class="form-control" id="brand_image" value="{{ $brand->brand_image }}" name="brand_image" accept="image/*">
                            
                                {{-- display validation error for brand_image --}}
                                @if ($errors->has('brand_image'))
                                    <div class="text-danger">{{ $errors->first('brand_image') }}</div>
                                @endif
                            </div>

                            @if ($brand->brand_image)
                                <div class="mb-3">
                                    <label class="form-label">Current Brand Image</label>
                                    <div>
                                        <img src="{{ asset($brand->brand_image) }}" alt="Brand Image" style="max-width: 200px; max-height: 200px;">
                                    </div>
                                </div>
                            @endif

                            <input type="hidden" name="id" value="{{ $brand->id }}">
                            <button type="submit" class="btn btn-primary">{{ $brand->id ? 'Update' : 'Add' }} Brand</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection