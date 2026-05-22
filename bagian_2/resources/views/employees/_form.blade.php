<div class="d-flex flex-column">
    <div class="mb-3">
        <label class="form-label">Pilih Company</label>

        {{-- Select2 dengan AJAX. name="company_id" agar value langsung ter-submit. --}}
        <select id="company-select" name="company_id"
                class="form-select select2-ajax @error('company_id') is-invalid @enderror"
                style="width: 100%;">
            {{-- Pre-selected option untuk old input atau edit mode --}}
            @php
                $selectedId = old('company_id', $employee->company_id ?? null);
                $selectedName = $employee->company->name ?? null;

                // Saat validation gagal di create, kita tidak punya $employee->company.
                // old() hanya kembalikan ID. Label akan di-handle oleh JS via initial selection.
            @endphp

            @if ($selectedId && $selectedName)
                <option value="{{ $selectedId }}" selected>{{ $selectedName }}</option>
            @elseif ($selectedId)
                {{-- Validation failed di create — JS akan fetch label berdasarkan ID --}}
                <option value="{{ $selectedId }}" selected data-need-fetch="1">Loading...</option>
            @endif
        </select>

        @error('company_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Nama</label>
        <input class="form-control @error('name') is-invalid @enderror" type="text" name="name"
               value="{{ old('name', $employee->name ?? '') }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control @error('email') is-invalid @enderror" type="email" name="email"
               value="{{ old('email', $employee->email ?? '') }}" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button class="btn btn-primary mt-3" type="submit">
        {{ isset($employee) ? 'Update' : 'Tambah' }}
    </button>
    <a href="{{ route('employees.index') }}" class="btn btn-secondary mt-3">Cancel</a>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        const $companySelect = $('#company-select');

        $companySelect.select2({
            placeholder: 'Cari & pilih company...',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('companies.select2') }}",
                dataType: 'json',
                delay: 250, // debounce 250ms agar tidak request setiap keystroke
                data: function (params) {
                    return {
                        q: params.term,         // search keyword
                        page: params.page || 1, // pagination page
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results, // array of {id, name}
                        pagination: {
                            more: data.pagination.more,
                        },
                    };
                },
                cache: true,
            },
            // Map field 'name' dari response sebagai label option
            templateResult: function (item) {
                if (item.loading) return item.text;
                return item.name || item.text;
            },
            templateSelection: function (item) {
                return item.name || item.text;
            },
            minimumInputLength: 0, // boleh kosong untuk tampilkan semua
        });

        // Edge case: kalau old input ada tapi label belum ke-load (validation gagal di create),
        // fetch label berdasarkan ID dan update pre-selected option.
        const $needFetch = $companySelect.find('option[data-need-fetch="1"]');
        if ($needFetch.length > 0) {
            const id = $needFetch.val();
            $.ajax({
                url: "{{ route('companies.select2') }}",
                data: { q: '', id: id },
            }).then(function (data) {
                const found = (data.results || []).find(c => String(c.id) === String(id));
                if (found) {
                    $needFetch.text(found.name).removeAttr('data-need-fetch');
                    $companySelect.trigger('change');
                }
            });
        }
    });
</script>
@endpush
