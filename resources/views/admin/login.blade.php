
@extends('admin.body.auth_layout')

@section('admin')
        <form method="POST" action="{{ route('login') }}" class="my-4">
        @csrf
            <div class="form-group mb-3">
                <label for="emailaddress" class="form-label">Email address</label>
                <input class="form-control" type="email" id="email" name="email" required="" placeholder="Enter your email">

                @error('email')
                    <p class="text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label for="password" class="form-label">Password</label>
                <input class="form-control" type="password" required="" id="password" name="password" placeholder="Enter your password">
                @error('password')
                    <p class="text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group d-flex mb-3">

                <div class="col-sm-6">
                    <a class='text-muted fs-14' href='{{ route('password.request') }}'>Forgot password?</a>                             
                </div>
            </div>
            
            <div class="form-group mb-0 row">
                <div class="col-12">
                    <div class="d-grid">
                        <button class="btn btn-primary" type="submit"> Log In </button>
                    </div>
                </div>
            </div>
    </form>
    
                            
    
<div class="text-center text-muted mb-4">
        <p class="mb-0">Don't have an account ?<a class='text-primary ms-2 fw-medium' href='{{ route('register') }}'>Sing up</a></p>
</div>
    
                                        
@endsection
