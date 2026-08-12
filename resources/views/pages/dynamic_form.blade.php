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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>{{ $record ? 'Update' : 'Create' }} Entry: {{ ucwords(str_replace('_', ' ', $table)) }}</h2>


                        @if($record)
                            <!-- Structural DELETE Action Component Form -->
                            <form action="{{ route('dynamic.form.destroy', ['table' => $table, 'id' => $record->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete Record</button>
                            </form>
                        @endif

                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ $record ? route('dynamic.form.update', [$table, $record->id]) : route('dynamic.form.store', $table) }}"
                        method="POST"
                        {!! $hasFiles ? 'enctype="multipart/form-data"' : '' !!}>
                        @csrf
                        @if($record)
                            @method('PUT')
                        @endif

                        @foreach($fields as $field)
                            @php
                                // Fetch previous data either from historical entry errors, database storage, or fallback null
                                $currentValue = old($field['name'], $record->{$field['name']} ?? null);
                            @endphp

                            <div class="mb-3">
                                <label for="{{ $field['name'] }}" class="form-label">
                                    {{ $field['label'] }} @if($field['required'] && !$record) <span class="text-danger">*</span> @endif
                                </label>

                                {{-- SELECT RENDERING --}}
                                @if($field['type'] === 'select')
                                    <select class="form-select @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}">
                                        <option value="">Select option...</option>
                                        @foreach($field['options'] as $option)
                                            <option value="{{ $option }}" {{ $currentValue === $option ? 'selected' : '' }}>
                                                {{ ucfirst($option) }}
                                            </option>
                                        @endforeach
                                    </select>

                                {{-- FILE RENDERING WITH SAVED IMAGE SOURCE CHECKS --}}
                                @elseif($field['type'] === 'file')
                                    <input type="file" accept="image/*" class="form-control dynamic-image-input @error($field['name']) is-invalid @enderror"
                                        id="{{ $field['name'] }}" name="{{ $field['name'] }}" data-preview-target="preview-{{ $field['name'] }}">

                                    <!-- Pre-existing DB File Entry Display or Frontend Runtime Preview Frame -->
                                    <div id="container-preview-{{ $field['name'] }}" class="mt-2 {{ $currentValue ? '' : 'd-none' }}">
                                        <p class="text-muted small mb-1">Active File / Preview:</p>
                                        <img id="preview-{{ $field['name'] }}"
                                            src="{{ $currentValue ? asset('storage/' . $currentValue) : '#' }}"
                                            alt="Preview Panel" class="img-thumbnail" style="max-height: 180px; object-fit: contain;">
                                    </div>

                                {{-- TEXTAREA RENDERING --}}
                                @elseif($field['type'] === 'textarea')
                                    <textarea class="form-control @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}">{{ $currentValue }}</textarea>

                                {{-- CHECKBOX RENDERING --}}
                                @elseif($field['type'] === 'checkbox')
                                    <div class="form-check form-switch">
                                        <input class="form-check-input @error($field['name']) is-invalid @enderror" type="checkbox"
                                            id="{{ $field['name'] }}" name="{{ $field['name'] }}" value="1" {{ $currentValue ? 'checked' : '' }}>
                                    </div>

                                {{-- DEFAULT FALLBACK SCALAR INPUTS --}}
                                @else
                                    <input type="{{ $field['type'] }}" class="form-control @error($field['name']) is-invalid @enderror"
                                        id="{{ $field['name'] }}" name="{{ $field['name'] }}" value="{{ $currentValue }}">
                                @endif

                                @error($field['name'])
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">{{ $record ? 'Update Details' : 'Save Details' }}</button>
                            <a href="{{ route('dynamic.form.create', $table) }}" class="btn btn-secondary">Clear / Reset Form</a>
                        </div>
                    </form>
                </div>
            </div>
    </div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInputs = document.querySelectorAll('.dynamic-image-input');
    fileInputs.forEach(input => {
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


@endsection
