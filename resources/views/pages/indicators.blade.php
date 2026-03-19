@extends('layouts.base')

@section('title', 'Асосий кўрсаткичлар')

@section('content')
    @livewire('districts.indicators')
    @livewire('analysis.timeline')
@endsection

@section('modals')
    @livewire('info-modal')
    @livewire('cluster-modal')
    @livewire('reason-modal')
@endsection
