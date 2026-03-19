@extends('layouts.base')

@section('title', 'Аҳоли кайфияти')

@section('content')
    @livewire('sentiment.mood')
    @livewire('sentiment.timeline')
@endsection

@section('modals')
    @livewire('info-modal')
    @livewire('cluster-modal')
@endsection

@push('page-scripts')
    <script src="{{asset('assets/js/sentiment.js')}}"></script>
@endpush
