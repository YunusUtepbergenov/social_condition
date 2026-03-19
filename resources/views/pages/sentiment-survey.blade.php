@extends('layouts.base')

@section('title', 'Сўровнома натижалари')

@section('content')
    @livewire('sentiment.survey')
    @livewire('sentiment.timeline')
@endsection

@section('modals')
    @livewire('info-modal')
    @livewire('cluster-modal')
@endsection

@push('page-scripts')
    <script src="{{asset('assets/js/sentiment.js')}}"></script>
@endpush
