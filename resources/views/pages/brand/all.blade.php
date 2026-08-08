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
                                    </div><!-- end card header -->

                                    <div class="card-body">
                                        <table id="datatable" class="table table-bordered dt-responsive table-responsive nowrap">
                                            <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Position</th>
                                                <th>Office</th>
                                                <th>Age</th>
                                                <th>Start date</th>
                                                <th>Salary</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>John Smith</td>
                                                    <td>Project Manager</td>
                                                    <td>Los Angeles</td>
                                                    <td>35</td>
                                                    <td>2023-08-10</td>
                                                    <td>$110,000</td>
                                                </tr>
                                                <tr>
                                                    <td>Emily Davis</td>
                                                    <td>Marketing Specialist</td>
                                                    <td>Chicago</td>
                                                    <td>29</td>
                                                    <td>2022-12-05</td>
                                                    <td>$85,000</td>
                                                </tr>
                                                <tr>
                                                    <td>Michael Brown</td>
                                                    <td>Software Engineer</td>
                                                    <td>San Francisco</td>
                                                    <td>31</td>
                                                    <td>2023-04-18</td>
                                                    <td>$120,000</td>
                                                </tr>
                                                <tr>
                                                    <td>Sarah Wilson</td>
                                                    <td>Financial Analyst</td>
                                                    <td>Houston</td>
                                                    <td>28</td>
                                                    <td>2023-10-30</td>
                                                    <td>$95,000</td>
                                                </tr>
                                                
                                           
                                                
                                               
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </div>
                        </div>



                    </div> <!-- container-fluid -->

@endsection