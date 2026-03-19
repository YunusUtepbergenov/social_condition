@extends('layouts.base')

@section('title', 'Ҳудудлар тоифалари')

@section('content')
    @livewire('districts.clusters')
    @livewire('analysis.timeline')
@endsection

@section('modals')
    @livewire('info-modal')
    @livewire('cluster-modal')
    @livewire('reason-modal')
@endsection
