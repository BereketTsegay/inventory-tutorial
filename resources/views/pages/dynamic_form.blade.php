@extends('admin.master_master')

@section('admin')
<div class="container-xxl">
 <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
    <div class="flex-grow-1">
        <h4 class="fs-18 fw-semibold m-0">Dynamic Form: {{ ucwords(str_replace('_', ' ', $model)) }}</h4>
    </div>

    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="javascript: void(0);">Form</a></li>
            <li class="breadcrumb-item active">{{ ucwords(str_replace('_', ' ', $model)) }}</li>
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
                                    <li class="breadcrumb-item"><a href="{{ route('dynamic.form.index', $model) }}">{{ ucwords(str_replace('_', ' ', $model)) }} Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">{{ $record ? 'Modify Entry' : 'New Entry' }}</li>
                                </ol>
                            </nav>
                            <h2 class="h3 mb-0">{{ $record ? 'Update' : 'Create' }} {{ Str::singular(ucwords(str_replace('_', ' ', $model))) }}</h2>
                        </div>

                        @if($record)
                            <div class="d-flex gap-2">
                                <a href="{{ route('dynamic.form.show', [$model, $record->id]) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center px-3">View Live Details</a>
                                <form action="{{ route('dynamic.form.destroy', [$model, $record->id]) }}" method="POST" onsubmit="return confirm('Drop this structural row record entirely?');">
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

                    <form action="{{ $record ? route('dynamic.form.update', [$model, $record->id]) : route('dynamic.form.store', $model) }}"
                        method="POST" {!! $hasFiles ? 'enctype="multipart/form-data"' : '' !!}>
                        @csrf
                        @if($record) @method('PUT') @endif

                        <div class="row g-4">
                            <!-- LEFT MAIN COLUMNS: Text areas, inputs, scalars -->
                            <div class="col-lg-8">
                                <!-- Primary Attributes Card Block inside dynamic_form.blade.php -->
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="card-title border-bottom pb-2 mb-3 text-muted">Primary Attributes</h5>
                    <div class="row g-3">
                        @foreach($fields as $field)
                            {{-- FIX: Added 'email' and 'tel' explicitly to the allowed types array --}}
                            @if(in_array($field['type'], ['text', 'textarea', 'number', 'date', 'datetime-local', 'email', 'tel']))
                                @php 
                                    $currentValue = old($field['name'], $record->{$field['name']} ?? null); 
                                @endphp
                                <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}">
                                    <label for="{{ $field['name'] }}" class="form-label fw-bold text-secondary small">
                                        {{ $field['label'] }} @if($field['required'] && !$record) <span class="text-danger">*</span> @endif
                                        @if(!empty($field['unique'])) <span class="badge bg-light text-primary border border-primary px-1 py-0 style-mini-badge">Unique</span> @endif
                                    </label>

                                    @if($field['type'] === 'textarea')
                                        <textarea rows="5" class="form-control bg-light @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}">{{ $currentValue }}</textarea>
                                    
                                    {{-- RENDERING EMAIL INPUT FIELD --}}
                                    @elseif($field['type'] === 'email')
                                        <input type="email" class="form-control bg-light @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}" value="{{ $currentValue }}" placeholder="example@domain.com">

                                    {{-- RENDERING PHONE/TELEPHONE INPUT FIELD --}}
                                    @elseif($field['type'] === 'tel')
                                        <input type="tel" class="form-control bg-light @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}" value="{{ $currentValue }}" placeholder="+1 (555) 000-0000">

                                    {{-- FALLBACK STANDARD SCALAR INPUTS --}}
                                    @else
                                        <input type="{{ $field['type'] }}" class="form-control bg-light @error($field['name']) is-invalid @enderror" id="{{ $field['name'] }}" name="{{ $field['name'] }}" value="{{ $currentValue }}">
                                    @endif

                                    @error($field['name']) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- RIGHT SECONDARY COLUMNS: Selections, Checkboxes, Files, Previews -->
            <div class="col-lg-4">
                <!-- Status Configurations -->
                <!-- Categorization & Relations Section Inside dynamic_form.blade.php -->
<div class="card border-0 shadow-sm p-4 mb-4">
    <h5 class="card-title border-bottom pb-2 mb-3 text-muted">Categorization & Relations</h5>
    <div class="row g-3">
        @foreach($fields as $field)
            @if(in_array($field['type'], ['select', 'checkbox', 'relation', 'many_to_many']))
                
                @php 
                    if ($field['type'] === 'many_to_many') {
                        $currentValue = old($field['name'], $record ? $record->{$field['relation_name']}()->pluck('id')->toArray() : []);
                    } else {
                        // FIX: Safely retrieve foreign ID values (like category_id) during updating cycles
                        $currentValue = old($field['name'], $record ? $record->getAttribute($field['name']) : null); 
                    }
                @endphp

                <div class="col-12">
                    {{-- MANY TO MANY MULTI-SELECT --}}
                    @if($field['type'] === 'many_to_many')
                        <label class="form-label fw-bold text-secondary small">{{ $field['label'] }}</label>
                        <select name="{{ $field['name'] }}[]" class="form-select bg-light" multiple style="height: 120px;">
                            @foreach($field['options'] as $option)
                                <option value="{{ $option['id'] }}" {{ in_array($option['id'], (array)$currentValue) ? 'selected' : '' }}>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>

                    {{-- RELATION DROPDOWN --}}
                    @elseif($field['type'] === 'relation')
                        <label for="{{ $field['name'] }}" class="form-label fw-bold text-secondary small">{{ $field['label'] }}</label>
                        <select class="form-select bg-light dynamic-relation-select" 
                                id="{{ $field['name'] }}" 
                                name="{{ $field['name'] }}"
                                data-is-dependent="{{ $field['is_dependent'] ? 'true' : 'false' }}"
                                data-parent-name="{{ $field['parent_field_name'] }}"
                                data-current-value="{{ $currentValue }}">
                            <option value="">Choose item...</option>
                            @foreach($field['options'] as $option)
                                {{-- FIX: Lax type checking (==) ensures numeric IDs and strings match correctly --}}
                                <option value="{{ $option['id'] }}" {{ $currentValue == $option['id'] ? 'selected' : '' }}>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</div>


<!-- Dynamic Dependent Dropdown Cascading Logic -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dependentSelects = document.querySelectorAll('.dynamic-relation-select[data-is-dependent="true"]');

    dependentSelects.forEach(select => {
        const parentFieldName = select.getAttribute('data-parent-name');
        const parentSelect = document.getElementById(parentFieldName);
        const currentValue = select.getAttribute('data-current-value');
        const modelTarget = "{{ $model }}";

        if (parentSelect) {
            const updateChildOptions = function (parentIdValue) {
                if (!parentIdValue) {
                    select.innerHTML = '<option value="">Choose item...</option>';
                    return;
                }

                // Query the API route to fetch matching child data
                fetch(`/api/form-relation/${modelTarget}/children?parent_field=${parentFieldName}&parent_value=${parentIdValue}`)
                    .then(response => response.json())
                    .then(data => {
                        let html = '<option value="">Choose item...</option>';
                        data.forEach(opt => {
                            const selected = opt.id == currentValue ? 'selected' : '';
                            html += `<option value="${opt.id}" ${selected}>${opt.label}</option>`;
                        });
                        select.innerHTML = html;
                    });
            };

            // Trigger updates dynamically on form load and value changes
            parentSelect.addEventListener('change', function () {
                updateChildOptions(this.value);
            });

            if (parentSelect.value) {
                updateChildOptions(parentSelect.value);
            }
        }
    });
});
</script>


                <!-- Asset File Panel Layout -->
                @if($hasFiles)
                    <div class="card border-0 shadow-sm p-4">
                        <h5 class="card-title border-bottom pb-2 mb-3 text-muted">Media Attachments</h5>
                        <!-- Inside the Media Attachments loop in resources/views/dynamic_form.blade.php -->
                        <!-- Inside the Media Attachments foreach loops inside your dynamic_form.blade.php layout -->
@foreach($fields as $field)
    @if(in_array($field['type'], ['file', 'json_array']))
        @php 
            $currentValue = old($field['name'], $record->{$field['name']} ?? null); 
        @endphp
        <div class="mb-4">
            <label class="form-label fw-bold text-secondary small mb-1">
                {{ $field['label'] }} {!! $field['type'] === 'json_array' ? '<span class="text-primary font-monospace small">(Max 5 Files)</span>' : '' !!}
            </label>
            
            <input type="file" accept="image/*" 
                   class="form-control dynamic-multi-image-input @error($field['name']) is-invalid @enderror" 
                   id="{{ $field['name'] }}" 
                   name="{{ $field['name'] }}{{ $field['type'] === 'json_array' ? '[]' : '' }}" 
                   data-preview-container="container-gallery-{{ $field['name'] }}"
                   {!! $field['type'] === 'json_array' ? 'multiple' : '' !!}>
            
            <!-- Gallery Panel Grid Layout -->
            <div id="container-gallery-{{ $field['name'] }}" class="mt-3 row g-2 border p-2 bg-light rounded {{ $currentValue ? '' : 'd-none' }}">
                @if($currentValue)
                    @php $imageArray = json_decode($currentValue, true) ?: [$currentValue]; @endphp
                    @foreach($imageArray as $index => $imagePath)
                        <!-- Single Target Interactive Image Wrap Card -->
                        <div class="col-4 text-center position-relative class-media-card shadow-sm border p-1 bg-white rounded" id="media-card-{{ $field['name'] }}-{{ $index }}">
                            <img src="{{ asset('storage/' . $imagePath) }}" alt="Saved Item Card" class="img-fluid rounded" style="max-height: 80px; object-fit: contain;">
                            
                            @if($record && $field['type'] === 'json_array')
                                <!-- Async Action Control Trigger to Delete an Individual Image -->
                                <button type="button" 
                                        class="btn btn-danger btn-sm p-0 position-absolute top-0 end-0 rounded-circle style-delete-media-btn" 
                                        onclick="deleteSingleGalleryAsset('{{ $model }}', '{{ $record->id }}', '{{ $field['name'] }}', '{{ $index }}')">
                                    &times;
                                </button>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
            @error($field['name']) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    @endif
@endforeach

<style>
.style-delete-media-btn { width: 22px; height: 22px; line-height: 18px; font-weight: bold; margin-top: -5px; margin-right: -5px; }
.class-media-card { min-height: 90px; display: flex; align-items: center; justify-content: center; }
</style>

<!-- Async Image Deletion Script -->
<script>
function deleteSingleGalleryAsset(model, id, fieldName, indexId) {
    if (!confirm('Permanently delete this specific image file off system storage?')) return;

    // Send asynchronous deletion parameters via fetch API
    fetch(`{{ route('dynamic.media.remove', ['model' => ':model', 'id' => ':id']) }}`.replace(':model', model).replace(':id', id), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ field_name: fieldName, index_id: indexId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the image card from the DOM immediately upon successful deletion
            const element = document.getElementById(`media-card-${fieldName}-${indexId}`);
            if(element) element.remove();
            alert(data.message);
        } else {
            alert('Error updating framework records: ' + data.message);
        }
    });
}
</script>


                        

                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4 border-top pt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4 py-2">{{ $record ? 'Update Details' : 'Save Framework Details' }}</button>
            <a href="{{ route('dynamic.form.index', $model) }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
        </div>
    </form>
</div>



                </div>
            </div>
    </div>
</div>
</div>
<!-- JavaScript Loop Extension logic to process multiple selected images -->
                        <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            document.querySelectorAll('.dynamic-multi-image-input').forEach(input => {
                                input.addEventListener('change', function () {
                                    const containerId = this.getAttribute('data-preview-container');
                                    const galleryContainer = document.getElementById(containerId);
                                    
                                    galleryContainer.innerHTML = ''; // Clear out the old preview structure
                                    
                                    if (this.files && this.files.length > 0) {
                                        galleryContainer.classList.remove('d-none');
                                        
                                        Array.from(this.files).forEach(file => {
                                            const reader = new FileReader();
                                            reader.onload = function (e) {
                                                const colDiv = document.createElement('div');
                                                colDiv.className = 'col-4 text-center';
                                                colDiv.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded border bg-white" style="max-height: 80px; object-fit: contain;">`;
                                                galleryContainer.appendChild(colDiv);
                                            };
                                            reader.readAsDataURL(file);
                                        });
                                    }
                                });
                            });
                        });
                        </script>
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
