@extends('layouts.base')

@section('title', 'Оммавий норозиликлар')

@section('content')
    @livewire('districts.protests')
    @livewire('analysis.timeline')
@endsection

@section('modals')
    @livewire('info-modal')
    @livewire('cluster-modal')
    @livewire('reason-modal')
@endsection
