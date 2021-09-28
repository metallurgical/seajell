<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="shortcut icon" href="{{ asset('/storage/logo/SeaJell-Logo.png') }}" type="image/png">
    @if(!empty($systemSetting->logo))
    <link rel="shortcut icon" href="{{ asset('storage/') . $systemSetting->logo }}" type="image/png">
    @else
    <link rel="shortcut icon" href="{{ asset('/storage/logo/SeaJell-Logo.png') }}" type="image/png">
    @endif
    <title>
        @if(!empty($systemSetting->name))
        {{ strtoupper($systemSetting->name) }}
        @else
        {{ 'SeaJell' }}
        @endif
        {{ '- SeaJell' }}
    </title>
</head>

<body class="" style="background-color: #495057">

    @yield('content')

    {{-- Footer --}}
    <div class="text-center text-light mt-5">
        <p><a class="text-decoration-underline text-light" href="https://projects.hanisirfan.xyz/seajell"
                target="_blank">SeaJell</a> {{ $appVersion }}</p>
        <p>Hak Cipta &copy; <a href="http://hanisirfan.xyz">Muhammad Hanis Irfan bin Mohd Zaid</a> 2021</p>
    </div>

    @include('layout.footer')

</body>

</html>