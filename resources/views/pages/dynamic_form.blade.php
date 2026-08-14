@extends('admin.master_master')

@section('admin')
<div class="container-xxl">
 <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
    <div class="flex-grow-1">
        <h4 class="fs-18 fw-semibold m-0">Dynamic Form: {{ ucwords(str_replace('_', ' ', $table)) }}</h4>
    </div>

    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="javascript: void(0);">Form</a></li>
            <li class="breadcrumb-item active">{{ ucwords(str_replace('_', ' ', $table)) }}</li>
        </ol>
    </div>
</div>

                       <!-- Datatables  -->

<div class="container mt-5">
    <div class="row">
            <div class="col-12">
                <div class="card p-4">
                    <div class="container-fluid mt-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dynamic.index', $table) }}">{{ ucwords(str_replace('_', ' ', $table)) }} Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $record ? 'Modify Entry' : 'New Entry' }}</li>
                </ol>
            </nav>
            <h2 class="h3 mb-0">{{ $record ? 'Update' : 'Create' }} {{ Str::singular(ucwords(str_replace('_', ' ', $table))) }}</h2>
        </div>

        @if($record)
            <div class="d-flex gap-2">
                <a href="{{ route('dynamic.form.show', [$table, $record->id]) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center px-3">View Live Details</a>
                <form action="{{ route('dynamic.form.destroy', [$table, $record->id]) }}" method="POST" onsubmit="return confirm('Drop this structural row record entirely?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm px-3">Delete Record</button>
                </form>
            </div>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4">{{ session('success') }}</div>
    @endif

    <form action="{{ $record ? route('dynamic.form.update', [$table, $record->id]) : route('dynamic.form.store', $table) }}"
          method="POST" {!! $hasFiles ? 'enctype="multipart/form-data"' : '' !!}>
        @csrf
        @if($record) @method('PUT') @endif

        <div class="row g-4">
            <!-- LEFT MAIN COLUMNS: Text areas, inputs, scalars -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="card-title border-bottom pb-2 mb-3 text-muted">Primary Attributes</h5>
                    <div class="row g-3">
                        @foreach($fields as $field)
                            @if(in_array($field['type'], ['text', 'textarea', 'number', 'date', 'datetime-local']))
                                @php $currentValue = old($field['name'], $record->{$field['name']} ?? null); @endphp
                                <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}">
                                    <label for="{{ $field['name'] }}" class="form-label fw-bold text-secondary small">
                                        {{ $field['label'] }} @if($field['required'] && !$record) <span class="text-danger">*</span> @endif
                                    </label>

                                    @if($field['type'] === 'textarea')
                                        <textarea rows="5" class="form-control bg-light @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}">{{ $currentValue }}</textarea>
                                    @else
                                        <input type="{{ $field['type'] }}" class="form-control bg-light @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}" value="{{ $currentValue }}">
                                    @endif

                                    @error($field['name']) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- RIGHT SECONDARY COLUMNS: Selections, Checkboxes, Files, Previews -->
            <div class="col-lg-4">
                <!-- Status Configurations -->
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="card-title border-bottom pb-2 mb-3 text-muted">Categorization & Rules</h5>
                    <div class="row g-3">
                        @foreach($fields as $field)
                            @if(in_array($field['type'], ['select', 'checkbox']))
                                @php $currentValue = old($field['name'], $record->{$field['name']} ?? null); @endphp
                                <div class="col-12">
                                    @if($field['type'] === 'select')
                                        <label for="{{ $field['name'] }}" class="form-label fw-bold text-secondary small">
                                            {{ $field['label'] }}

                                            @if($field['required'] && !$record) <span class="text-danger">*</span> @endif
                                            @if($field['unique']) <span class="badge bg-light text-primary border border-primary px-1 py-0 style-mini-badge">Unique</span> @endif

                                        </label>
                                        <select class="form-select bg-light @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}">
                                            <option value="">Select choice...</option>
                                            @foreach($field['options'] as $option)
                                                <option value="{{ $option }}" {{ $currentValue === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($field['type'] === 'checkbox')
                                        <div class="form-check form-switch pt-2">
                                            <input class="form-check-input" type="checkbox" id="{{ $field['name'] }}" name="{{ $field['name'] }}" value="1" {{ $currentValue ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-secondary small" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                                        </div>
                                    @endif
                                    @error($field['name']) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Asset File Panel Layout -->
                @if($hasFiles)
                    <div class="card border-0 shadow-sm p-4">
                        <h5 class="card-title border-bottom pb-2 mb-3 text-muted">Media Attachments</h5>
                        @foreach($fields as $field)
                            @if($field['type'] === 'file')
                                @php $currentValue = old($field['name'], $record->{$field['name']} ?? null); @endphp
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-secondary small mb-1">{{ $field['label'] }}</label>
                                    <input type="file" accept="image/*" class="form-control dynamic-image-input @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}" data-preview-target="preview-{{ $field['name'] }}">

                                    <div id="container-preview-{{ $field['name'] }}" class="mt-3 text-center border p-2 bg-light rounded {{ $currentValue ? '' : 'd-none' }}">
                                        <img id="preview-{{ $field['name'] }}" src="{{ $currentValue ? asset('storage/' . $currentValue) : '#' }}" alt="Visual Preview" class="img-fluid rounded" style="max-height: 160px;">
                                    </div>
                                    @error($field['name']) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4 border-top pt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4 py-2">{{ $record ? 'Update Details' : 'Save Framework Details' }}</button>
            <a href="{{ route('dynamic.index', $table) }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
        </div>
    </form>
</div>



                </div>
            </div>
    </div>
</div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.dynamic-image-input').forEach(input => {
            input.addEventListener('change', function () {
                const targetId = this.getAttribute('data-preview-target');
                const previewImage = document.getElementById(targetId);
                const container = document.getElementById('container-' + targetId);
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImage.src = e.target.result;
                        container.classList.remove('d-none');
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });
    });
</script>
<style>
.style-mini-badge { font-size: 0.65rem; vertical-align: middle; }
</style>

@endsection
