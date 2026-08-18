@extends('admin.master_master')
@section('admin')
<div class="container-xxl">
    <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
        <div class="flex-grow-1">
            <h4 class="fs-18 fw-semibold m-0">Dynamic Dashboard: {{ ucwords(str_replace('_', ' ', $model)) }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dynamic.form.index', $model) }}">Dashboard</a></li>
                <li class="breadcrumb-item active">{{ ucwords(str_replace('_', ' ', $model)) }}</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card p-3">
                <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Data Matrix: {{ ucwords(str_replace('_', ' ', $model)) }}</h2>
        <a href="{{ route('dynamic.form.create', $model) }}" class="btn btn-success">+ Add New Entry</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <!-- Interactive Filtration Control Block Panel -->
    <div class="card bg-light mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ route('dynamic.form.index', $model) }}" method="GET" class="row g-3 align-items-end">
                <!-- Global Keyword Input Component -->
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Search Table Fields</label>
                    <input type="text" name="search" class="form-control" placeholder="Search values..." value="{{ request('search') }}">
                </div>

                <!-- Loop Dynamic Select/Enum Field Filters -->
                @foreach($fields as $field)
                    @if($field['type'] === 'select')
                        <div class="col-md-2">
                            <label class="form-label">{{ $field['label'] }} Filter</label>
                            <select name="filter_{{ $field['name'] }}" class="form-select">
                                <option value="">All options</option>
                                @foreach($field['options'] as $option)
                                    <option value="{{ $option }}" {{ request('filter_'.$field['name']) === $option ? 'selected' : '' }}>
                                        {{ ucfirst($option) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @endforeach

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                    <a href="{{ route('dynamic.form.index', $model) }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    <div class="table-responsive bg-white shadow-sm rounded">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    @foreach($fields as $field)
                        <th>{{ $field['label'] }}</th>
                    @endforeach
                    <th class="text-end">#</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $row)
                    <tr>
                        <td><strong>#{{ $row->id }}</strong></td>
                       <!-- Inside the fields foreach loop in dynamic_index.blade.php -->
<!-- Inside the fields foreach loop in dynamic_index.blade.php -->
@foreach($fields as $field)
    <td>
        @if(!empty($field['is_relation']))
            @php 
                $relName = $field['relation_name']; 
            @endphp
            
            {{-- FIX: Verify the dynamic Eloquent relationship object is hydrated and loaded --}}
            @if(!empty($relName) && isset($row->$relName))
                <span class="badge bg-info text-dark shadow-sm px-2">
                    {{ $row->$relName->name ?? $row->$relName->title ?? $row->$relName->label ?? "ID: " . $row->getAttribute($field['name']) }}
                </span>
            @elseif($field['type'] === 'many_to_many' && isset($row->$relName))
                <div class="d-flex flex-wrap gap-1">
                    @foreach($row->$relName as $pivotItem)
                        <span class="badge bg-secondary text-white small px-1">{{ $pivotItem->name ?? $pivotItem->title }}</span>
                    @endforeach
                </div>
            @else
                <!-- Fallback to raw foreign key index integer printout if method doesn't exist -->
                <span class="text-muted small">{{ $row->getAttribute($field['name']) ?: '—' }}</span>
            @endif

        @elseif($field['type'] === 'file')
            @if(!empty($row->{$field['name']}))
                <img src="{{ asset('storage/' . $row->{$field['name']}) }}" class="rounded img-thumbnail" style="max-height: 40px;">
            @else
                <span class="text-muted small">No file</span>
            @endif
        @else
            {{ Str::limit($row->getAttribute($field['name']), 50) }}
        @endif
    </td>
@endforeach


                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <!-- add view -->
                                <a href="{{ route('dynamic.form.show', [$model, $row->id]) }}" class="btn btn-outline-info">View</a>
                                <a href="{{ route('dynamic.form.edit', [$model, $row->id]) }}" class="btn btn-outline-primary">Edit</a>



                                    <form action="{{ route('dynamic.form.destroy', [$model, $row->id]) }}" method="POST"  onsubmit="return confirm('Erase item permanently?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($fields) + 2 }}" class="text-center text-muted py-4">No structural records found inside this engine table payload.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Interface Pagination Interceptor Controls -->
    <div class="mt-3">
        {{ $records->links() }}
    </div>
</div>
</div>
            </div>
        </div>
    </div>
</div>

@endsection
