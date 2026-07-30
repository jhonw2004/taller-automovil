@extends('marketplace.layouts.app')

@section('title', 'Buscar talleres')
@section('layout_variant', 'app-shell')

@section('content')
    @include('marketplace.search.map-experience', ['categorias' => $categorias])
@endsection
