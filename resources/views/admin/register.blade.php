
@extends('admin.body.auth_layout')

@section('admin')
<form method="POST" action="{{ route('register') }}" class="my-4">
    <div class="form-group mb-3">
        <label for="username" class="form-label">Full Name</label>
        <input class="form-control" name="name" type="text" id="name" required="" placeholder="Enter your Username">
    </div>

    <div class="form-group mb-3">
        <label for="email" name="email" class="form-label">Email address</label>
        <input class="form-control" type="email" id="email" name="email" required="" placeholder="Enter your email">
    </div>
    <div class="form-group mb-3">
        <label for="photo" name="photo" class="form-label">Photo</label>
        <input class="form-control" type="file" id="photo" name="photo">
    </div>
    <div class="form-group mb-3">
        <label for="phone" name="phone" class="form-label">phone</label>
        <input class="form-control" type="text" id="phone" name="phone"  placeholder="Enter your phone number">
    </div>
    <div class="form-group mb-3">
        <label for="Address" name="Address" class="form-label">Address</label>
        <input class="form-control" type="text" id="Address" name="Address"  placeholder="Enter your Address">
    </div>
    <div class="form-group mb-3">
        <label for="password" name="password" class="form-label">Password</label>
        <input class="form-control" type="password" id="password" name="password"  placeholder="Enter your password">
    </div>
    <div class="form-group mb-3">
        <label for="password_confirmation" name="password_confirmation" class="form-label">Confirm Password</label>
        <input class="form-control" type="password" id="password_confirmation" name="password_confirmation"  placeholder="Confirm your password">
    </div>



    {{-- <div class="form-group d-flex mb-3">
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="checkbox-signin">
                <label class="form-check-label" for="checkbox-signin">I agree to the <a href="#" class="text-primary fw-medium"> Terms and Conditions</a></label>
            </div>
        </div><!--end col-->
    </div> --}}
    
    <div class="form-group mb-0 row">
        <div class="col-12">
            <div class="d-grid">
                <button class="btn btn-primary" type="submit"> Register</button>
            </div>
        </div>
    </div>
</form>
                            

<div class="text-center text-muted mb-4">
    <p class="mb-0">Already have an account ?<a class='text-primary ms-2 fw-medium' href='{{ route('login') }}'>Login here</a></p>
</div>

                                        
@endsection
