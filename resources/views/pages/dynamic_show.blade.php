@extends('admin.master_master')
@section('admin')
<div class="container-xxl">
    <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
        <div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dynamic.index', $table) }}">{{ ucwords(str_replace('_', ' ', $table)) }} Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Details</li>
                </ol>
            </nav>
            <h2 class="h3 mb-0">Record Inspector: #{{ $record->id }}</h2>
        </div>
        <a href="{{ route('dynamic.form.edit', [$table, $record->id]) }}" class="btn btn-primary px-4">Edit This Record</a>
    </div>

    <div class="row g-4">
        <!-- Fields Details Readout Layout Grid Column -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <h5 class="text-muted border-bottom pb-2 mb-3">Stored Entity Values</h5>
                <dl class="row mb-0">
                    @foreach($fields as $field)
                        @if($field['type'] !== 'file')
                            <dt class="col-sm-4 text-secondary mb-3 small uppercase tracking-wider">{{ $field['label'] }}</dt>
                            <dd class="col-sm-8 mb-3">
                                @if($field['type'] === 'checkbox')
                                    <span class="badge {{ $record->{$field['name']} ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $record->{$field['name']} ? 'Enabled / True' : 'Disabled / False' }}
                                    </span>
                                @elseif($field['type'] === 'textarea')
                                    <div class="p-3 bg-light rounded text-dark text-break style-ws-pre-line">{{ $record->{$field['name']} ?: '—' }}</div>
                                @else
                                    <span class="text-dark fw-bold">{{ $record->{$field['name']} ?: '—' }}</span>
                                @endif
                            </dd>
                        @endif
                    @endforeach
                </dl>
            </div>
        </div>

        <!-- High-Res File View Grid Column -->
        <div class="col-md-5">
            @php $hasFiles = collect($fields)->contains('type', 'file'); @endphp
            @if($hasFiles)
                <div class="card border-0 shadow-sm p-4 bg-white h-100">
                    <h5 class="text-muted border-bottom pb-2 mb-3">Saved Files</h5>
                    @foreach($fields as $field)
                        @if($field['type'] === 'file')
                            <div class="mb-4 text-center border rounded p-3 bg-light">
                                <h6 class="text-start text-secondary mb-2 small fw-bold">{{ $field['label'] }}</h6>
                                @if(!empty($record->{$field['name']}))
                                    <a href="{{ asset('storage/' . $record->{$field['name']}) }}" target="_blank" title="View Fullsize Asset">
                                        <img src="{{ asset('storage/' . $record->{$field['name']}) }}" alt="Database Asset Frame" class="img-fluid rounded shadow-sm hover-zoom" style="max-height: 250px; object-fit: contain;">
                                    </a>
                                @else
                                    <div class="py-4 text-muted small">No file uploaded for this property entry</div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>


    </div>
</div>
<style>
.style-ws-pre-line { white-space: pre-line; }
.hover-zoom { transition: transform .2s ease; }
.hover-zoom:hover { transform: scale(1.02); }
</style>
@endsection
