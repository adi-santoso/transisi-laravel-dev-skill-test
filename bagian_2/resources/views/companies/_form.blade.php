<div class="d-flex flex-column">
    <div class="form-input mb-3">
        <label class="form-label">Nama</label>
        <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name', $company->name ?? '') }}" required>
        @error('name')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
    </div>

    <div class="form-input mb-3">
        <label class="form-label">Email</label>
        <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $company->email ?? '') }}" required>
        @error('email')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
    </div>

    <div class="form-input mb-3">
        <label class="form-label d-block">Logo</label>

        {{-- Preview logo lama (mode edit) --}}
        @if (isset($company) && $company->logo)
            <div class="mb-2">
                <img src="{{ route('companies.logo', $company) }}"
                     style="max-height: 100px;" class="d-block mb-1">
                <small class="text-muted">Logo saat ini. Pilih file baru untuk mengganti.</small>
            </div>
        @endif

        {{-- Komponen upload Varian B: tombol + status text --}}
        @php
            $hasCurrentLogo = isset($company) && $company->logo;
            $hasTmpLogo = old('tmp_logo');
        @endphp

        <div class="d-flex align-items-center gap-2 flex-wrap" id="logoUploadWrapper"
             data-has-current-logo="{{ $hasCurrentLogo ? '1' : '0' }}"
             data-has-tmp-logo="{{ $hasTmpLogo ? '1' : '0' }}"
             data-tmp-logo="{{ $hasTmpLogo ?? '' }}">
            {{-- Hidden file input — di-trigger via JS oleh tombol --}}
            <input id="logoFileInput"
                   class="d-none @error('logo') is-invalid @enderror"
                   type="file"
                   name="logo"
                   accept="image/png"
                   {{ (isset($company) || old('tmp_logo')) ? '' : 'required' }}>

            <button type="button" class="btn btn-outline-primary btn-sm" id="logoPickBtn">
                <i class="mdi mdi-upload"></i>
                @if ($hasTmpLogo)
                    Pilih File Baru
                @elseif ($hasCurrentLogo)
                    Ganti Logo
                @else
                    Pilih File
                @endif
            </button>

            <span id="logoStatus" class="small">
                @if ($hasTmpLogo)
                    <span class="text-success">
                        <i class="mdi mdi-check-circle"></i>
                        Logo siap dipakai
                    </span>
                @elseif ($hasCurrentLogo)
                    <span class="text-muted">Logo saat ini tetap dipakai</span>
                @else
                    <span class="text-muted">Belum ada file dipilih</span>
                @endif
            </span>

            <button type="button" class="btn btn-link btn-sm text-danger {{ $hasTmpLogo ? '' : 'd-none' }} p-0" id="logoClearBtn">
                <i class="mdi mdi-close"></i> Hapus
            </button>
        </div>

        {{-- Hidden field untuk preserve tmp logo dari submit sebelumnya --}}
        @if ($hasTmpLogo)
            <input type="hidden" name="tmp_logo" value="{{ $hasTmpLogo }}" id="tmpLogoField">
        @endif

        {{-- Helper text --}}
        <div class="form-text">PNG, maksimal 2MB, minimal 100x100 px.</div>

        @error('logo')
        <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-input mb-3">
        <label class="form-label">Website</label>
        <input class="form-control @error('website') is-invalid @enderror" type="text" name="website" value="{{ old('website', $company->website ?? '') }}">
        @error('website')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
    </div>

    <button class="btn btn-primary mt-3" type="submit">
        {{ isset($company) ? 'Update' : 'Tambah' }}
    </button>
</div>

@push('scripts')
<script>
    (function () {
        const wrapper = document.getElementById('logoUploadWrapper');
        const pickBtn = document.getElementById('logoPickBtn');
        const fileInput = document.getElementById('logoFileInput');
        const statusEl = document.getElementById('logoStatus');
        const clearBtn = document.getElementById('logoClearBtn');
        let tmpField = document.getElementById('tmpLogoField');

        if (!wrapper || !pickBtn || !fileInput || !statusEl || !clearBtn) return;

        const hasCurrentLogo = wrapper.dataset.hasCurrentLogo === '1';
        const originalTmpLogo = wrapper.dataset.tmpLogo || '';

        // Click tombol → trigger native file picker.
        pickBtn.addEventListener('click', function () {
            fileInput.click();
        });

        // Saat user pilih file, update status text + show clear button.
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const sizeKb = (file.size / 1024).toFixed(0);
                statusEl.innerHTML =
                    '<span class="text-success"><i class="mdi mdi-file-image"></i> ' +
                    escapeHtml(file.name) + ' (' + sizeKb + ' KB)</span>';
                clearBtn.classList.remove('d-none');

                // Kalau user pilih file baru, tmp_logo lama di-overwrite di server.
                // Hapus hidden input agar tidak ambigu di backend.
                if (tmpField) {
                    tmpField.remove();
                    tmpField = null;
                }
            }
        });

        // Tombol hapus → clear file input + revert status.
        clearBtn.addEventListener('click', function () {
            fileInput.value = '';

            if (originalTmpLogo) {
                restoreTmpField(originalTmpLogo);
                statusEl.innerHTML = '<span class="text-success"><i class="mdi mdi-check-circle"></i> Logo siap dipakai</span>';
            } else if (hasCurrentLogo) {
                statusEl.innerHTML = '<span class="text-muted">Logo saat ini tetap dipakai</span>';
                clearBtn.classList.add('d-none');
            } else {
                statusEl.innerHTML = '<span class="text-muted">Belum ada file dipilih</span>';
                clearBtn.classList.add('d-none');
            }
        });

        function restoreTmpField(value) {
            if (tmpField) {
                tmpField.value = value;
                return;
            }

            tmpField = document.createElement('input');
            tmpField.type = 'hidden';
            tmpField.name = 'tmp_logo';
            tmpField.id = 'tmpLogoField';
            tmpField.value = value;
            wrapper.insertAdjacentElement('afterend', tmpField);
        }

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    })();
</script>
@endpush
