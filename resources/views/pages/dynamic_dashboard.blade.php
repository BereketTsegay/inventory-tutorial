@extends('admin.master_master')
@section('admin')
<div class="container-xxl">
    <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
        <div class="flex-grow-1">
            <h4 class="fs-18 fw-semibold m-0">Dynamic Dashboard: {{ ucwords(str_replace('_', ' ', $table)) }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                <li class="breadcrumb-item active">{{ ucwords(str_replace('_', ' ', $table)) }}</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card p-3">
                <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Data Matrix: {{ ucwords(str_replace('_', ' ', $table)) }}</h2>
        <a href="{{ route('dynamic.form.create', $table) }}" class="btn btn-success">+ Add New Entry</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <!-- Interactive Filtration Control Block Panel -->
    <div class="card bg-light mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ route('dynamic.index', $table) }}" method="GET" class="row g-3 align-items-end">
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
                    <a href="{{ route('dynamic.index', $table) }}" class="btn btn-outline-secondary w-100">Reset</a>
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
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $row)
                    <tr>
                        <td><strong>#{{ $row->id }}</strong></td>
                        @foreach($fields as $field)
                            <td>
                                {{-- RENDER SAVED IMAGES INSIDE THE DATAGRID GRID --}}
                                @if($field['type'] === 'file')
                                    @if(!empty($row->{$field['name']}))
                                        <img src="{{ asset($row->{$field['name']}) }}"
                                             class="img-thumbnail" style="max-height: 50px; width: 50px; object-fit: cover;">
                                    @else
                                        <span class="text-muted text-uppercase small">None</span>
                                    @endif

                                {{-- RENDER BOOLEAN CHECKBOX VALUES --}}
                                @elseif($field['type'] === 'checkbox')
                                    <span class="badge {{ $row->{$field['name']} ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $row->{$field['name']} ? 'Active' : 'Inactive' }}
                                    </span>

                                {{-- PLAIN TEXT EXPORT STRINGS --}}
                                @else
                                    {{ Str::limit($row->{$field['name']}, 50, '...') }}
                                @endif
                            </td>
                        @endforeach

                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <!-- add view -->
                                <a href="{{ route('dynamic.form.show', [$table, $row->id]) }}" class="btn btn-outline-info">View</a>
                                <a href="{{ route('dynamic.form.edit', [$table, $row->id]) }}" class="btn btn-outline-primary">Edit</a>



                                    <form action="{{ route('dynamic.form.destroy', [$table, $row->id]) }}" method="POST"  onsubmit="return confirm('Erase item permanently?');">
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
