<div class="d-flex flex-column">
    <div class="form-input">
        <label class="form-label">Nama</label>
        <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" value="{{old('name', $company->name ??' ')}}" required>
        @error('name')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
    </div>

    <div class="form-input">
        <label class="form-label">Email</label>
        <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{old('email', $company->email ?? '')}}" required>
        @error('email')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
    </div>

    <div class="form-input">
        <label class="form-label">Logo</label>

        @if (isset($company) && $company->logo)
            <div class="mb-2">
                <img src="{{ route('companies.logo', $company) }}"
                     style="max-height: 100px;" class="d-block mb-1">
                <small class="text-muted">Current logo. Upload to replace.</small>
            </div>
        @endif

        <input class="form-control @error('logo') is-invalid @enderror" type="file" name="logo" accept="image/png, .png" value="{{old('logo', $company->logo ?? '')}}" {{ isset($company) ? '' : 'required' }}>
        @error('logo')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
    </div>

    <div class="form-input">
        <label class="form-label">website</label>
        <input class="form-control @error('website') is-invalid @enderror" type="text" name="website" value="{{old('website', $company->website ?? '')}}">
        @error('website')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
    </div>

    <button class="btn btn-primary mt-3" type="submit">
        {{ isset($company) ? 'Update' : 'Tambah' }}
    </button>
</div>
