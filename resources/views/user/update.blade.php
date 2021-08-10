@extends('layout.main')
@section('content')
    <p class="fs-2">Kemas Kini Pengguna</p>
    @php
    //  Check if there's old value, if not insert data from database if available
        
        if(old('fullname') != NULL){ // fullname
            $valueFullname = old('fullname');
        }else{
            if($data != NULL && $data != ''){
                if($data->fullname != NULL && $data->fullname != ''){
                    $valueFullname = strtoupper($data->fullname);
                }else{
                    $valueFullname = "";
                }
            }
        }

        if(old('email') != NULL){ // email
            $valueEmail = old('email');
        }else{
            if($data != NULL && $data != ''){
                if($data->email != NULL && $data->email != ''){
                    $valueEmail = strtoupper($data->email);
                }else{
                    $valueEmail = "";
                }
            }
        }

        if(old('identification_number') != NULL){ // identification_number
            $valueIdentificationNumber = old('identification_number');
        }else{
            if($data != NULL && $data != ''){
                if($data->identification_number != NULL && $data->identification_number != ''){
                    $valueIdentificationNumber = $data->identification_number;
                }else{
                    $valueIdentificationNumber = "";
                }
            }
        }
    @endphp
    @if(session()->has('updateUserSuccess'))
        <span><div class="alert alert-success w-100 ml-1">{{ session('updateUserSuccess') }}</div></span>
    @endif
    @error('userNotExisted')
        <span><div class="alert alert-danger w-100 ml-1">{{ $message }}</div></span>
    @enderror
    <form action="" method="post" class="mb-3">
        @csrf
        <div class="mb-3">
            <label for="fullname" class="form-label">Nama Penuh Pengguna</label>
            <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Masukkan nama penuh pengguna." value="{{ $valueFullname }}">
        </div>
        @error('fullname')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
        <div class="mb-3">
            <label for="email" class="form-label">Alamat E-mel</label>
            <input type="text" class="form-control" id="email" name="email" placeholder="Masukkan alamat e-mel pengguna." value="{{ $valueEmail }}">
        </div>
        @error('email')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
        <div class="mb-3">
            <label for="identification_number " class="form-label">Nombor Kad Pengenalan</label>
            <input type="text" class="form-control" id="identification_number " name="identification_number" placeholder="Masukkan nombor kad pengenalan pengguna." value="{{ $valueIdentificationNumber }}">
            <div id="identification_number_help" class="form-text">
                Nombor kad pengenalan mestilah diisi tanpa "-". <br>
                Contoh: 012345678901
            </div>
        </div>
        @error('identification_number')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
            @can('authAdmin')
            <label for="role" class="form-label">Peranan Pengguna</label>
            <select class="form-select mb-3" name="role" id="role" aria-label="role">
                <option value="participant">Peserta</option>
                <option value="admin">Admin</option>
            </select>
            @error('role')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        @endcan
        <button class="btn btn-dark" type="submit" name="info">Kemas Kini Maklumat</button>
    </form>
    <p class="fs-2">Kemas Kini Kata Laluan</p>
    @if(session()->has('updateUserPasswordSuccess'))
        <span><div class="alert alert-success w-100 ml-1">{{ session('updateUserPasswordSuccess') }}</div></span>
    @endif
    @error('userNotExisted')
        <span><div class="alert alert-danger w-100 ml-1">{{ $message }}</div></span>
    @enderror
    <form action="" method="post" class="mb-3">
        @csrf
        <div class="mb-3">
            <label for="password" class="form-label">Kata Laluan</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan kata laluan pengguna.">
        </div>
        @error('password')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
        <div class="mb-3">
            <label for="password_confirmation " class="form-label">Sahkan Kata Laluan</label>
            <input type="password" class="form-control" id="password_confirmation " name="password_confirmation" placeholder="Masukkan kata laluan pengguna semula.">
        </div>
        <button class="btn btn-dark" type="submit" name="password-update">Kemas Kini Kata Laluan</button>
    </form>
@endsection