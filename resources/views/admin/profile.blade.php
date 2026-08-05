@extends('admin.master_master')

@section('admin')

    <!-- Start Content-->
                    <div class="container-xxl">
                        <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                            <div class="flex-grow-1">
                                <h4 class="fs-18 fw-semibold m-0">Profile</h4>
                            </div>
            
                            <div class="text-end">
                                <ol class="breadcrumb m-0 py-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">User</a></li>
                                    <li class="breadcrumb-item active">Profile</li>
                                </ol>
                            </div>
                        </div>

                        

                        <div class="row">
                            <div class="col-12">
                                <div class="card">

                                    <div class="card-body">

                                        <div class="align-items-center">
                                            <div class="d-flex align-items-center">
                                                <img src="{{ empty($profile->photo) ? url('uploads/user.jpg') : url('uploads/user_images/' . $profile->photo) }}" class="rounded-circle avatar-xxl img-thumbnail float-start" alt="image profile">
            
                                                <div class="overflow-hidden ms-4">
                                                    <h4 class="m-0 text-dark fs-20">{{ $profile->name }}</h4>
                                                    <p class="my-1 text-muted fs-16">{{ $profile->email }}</p>
                                                    
                                                </div>
                                            </div>
                                        </div>

                                        

                                        <div class="tab-content text-muted bg-white">
                                            

                                            <div class="pt-4">
                                                <div class="row">

                                                    <div class="row">
                                                        <div class="col-lg-6 col-xl-6">
                                                            <div class="card border mb-0">

                                                                <div class="card-header">
                                                                    <div class="row align-items-center">
                                                                        <div class="col">                      
                                                                            <h4 class="card-title mb-0">Personal Information</h4>                      
                                                                        </div><!--end col-->                                                       
                                                                    </div>
                                                                </div>

                                                                <div class="card-body">

                                                                @session('message')
                                                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                                        <strong>{{ session('message') }}</strong>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                                    </div>
                                                                @endsession

                                                                <form method="POST" action="{{ route('admin.profileUpdate') }}" enctype="multipart/form-data">
                                                                    @csrf
                                                                    <div class="form-group mb-3 row">
                                                                        <label class="form-label">Full Name</label>
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <input class="form-control" name="name" type="text" value="{{ $profile->name }}">
                                                                            @error('name')
                                                                                <p class="text-danger">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        
                                                                    </div>

                                                                    

                                                                    <div class="form-group mb-3 row">
                                                                        <label class="form-label">Contact Phone</label>
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <div class="input-group">
                                                                                <span class="input-group-text"><i class="mdi mdi-phone-outline"></i></span>
                                                                                <input class="form-control" type="text" name="phone" placeholder="Phone" aria-describedby="basic-addon1" value="{{ $profile->phone }}">
                                                                                @error('phone')
                                                                                    <p class="text-danger">{{ $message }}</p>
                                                                                @enderror
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="form-group mb-3 row">
                                                                        <label class="form-label">Email Address</label>
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <div class="input-group">
                                                                                <span class="input-group-text"><i class="mdi mdi-email"></i></span>
                                                                                <input disabled type="text" name="email" class="form-control" value="{{ $profile->email }}" placeholder="Email" aria-describedby="basic-addon1">
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    

                                                                    <div class="form-group mb-3 row">
                                                                        <label class="form-label">Address</label>
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <textarea class="form-control" placeholder="Address" name="address" value="{{ $profile->address }}" aria-describedby="basic-addon1">{{ $profile->address }}</textarea>
                                                                             @error('address')
                                                                                <p class="text-danger">{{ $message }}</p>
                                                                             @enderror
                                                                        </div>
                                                                       
                                                                    </div>
                                                                    <div class="form-group mb-3 row">
                                                                        <label class="form-label" for="photo">Photo</label>
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <div class="input-group">
                                                                                <span class="input-group-text"><i class="mdi mdi-camera"></i></span>
                                                                                <input type="file" id="photo" name="photo" class="form-control" placeholder="Photo" aria-describedby="basic-addon1">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group mb-3 row">
                                                        
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <img id="showImage" src="{{ empty($profile->photo) ? url('uploads/user.jpg') : url('uploads/user_images/' . $profile->photo) }}" class="rounded-circle avatar-xxl img-thumbnail float-start" alt="image profile">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group row">
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                                </div><!--end card-body-->
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-6 col-xl-6">
                                                            <div class="card border mb-0">

                                                                <div class="card-header">
                                                                    <div class="row align-items-center">
                                                                        <div class="col">                      
                                                                            <h4 class="card-title mb-0">Change Password</h4>                      
                                                                        </div><!--end col-->                                                       
                                                                    </div>
                                                                </div>
                                                            
                                                                <div class="card-body mb-0">
                                                                <form method="POST" action="{{ route('admin.profile.change-password') }}">
                                                                @csrf
                                                                    <div class="form-group mb-3 row">
                                                                        <label class="form-label">Old Password</label>
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <input class="form-control" type="password" placeholder="Old Password" name="old_password">
                                                                            @error('old_password')
                                                                                <p class="text-danger">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group mb-3 row">
                                                                        <label class="form-label">New Password</label>
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <input class="form-control" type="password" placeholder="New Password" name="new_password">
                                                                            @error('new_password')
                                                                                <p class="text-danger">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group mb-3 row">
                                                                        <label class="form-label">Confirm Password</label>
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <input class="form-control" type="password" placeholder="Confirm Password" name="new_password_confirmation">
                                                                            @error('new_password_confirmation')
                                                                                <p class="text-danger">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                    </div>

                                                                    <div class="form-group row">
                                                                        <div class="col-lg-12 col-xl-12">
                                                                            <button type="submit" class="btn btn-primary">Change Password</button>
                                                                            <button type="button" class="btn btn-danger">Cancel</button>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                                </div><!--end card-body-->
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div> <!-- end education -->

                                        </div> <!-- Tab panes -->
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div> 
                    <!-- container-fluid -->

                    <script type="text/javascript">
                        $(document).ready(function() {
                             $('#photo').change(function(e) {
                                
                                var reader = new FileReader();
                                reader.onload = function(e) {
                                    $('#showImage').attr('src', e.target.result);
                                }
                                reader.readAsDataURL(e.target.files['0']);
                            });
                        });
                    </script>
@endsection
