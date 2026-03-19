@extends('layouts.base')

@section('title', 'Истеъмолчилар кайфияти')

@section('content')
    @livewire('districts.mood')
    @livewire('analysis.timeline')
@endsection

@section('modals')
    @livewire('info-modal')
    @livewire('cluster-modal')
    @livewire('reason-modal')
@endsection
