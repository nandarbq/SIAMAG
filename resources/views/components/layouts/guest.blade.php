<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth bg-[#050a12]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Masuk ke SIAMAG, sistem informasi absensi dan logbook magang BMKG Banyuwangi.">
    <meta name="theme-color" content="#050a12">
    <title>Masuk — SIAMAG BMKG Banyuwangi</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#050a12] text-slate-100 antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>
