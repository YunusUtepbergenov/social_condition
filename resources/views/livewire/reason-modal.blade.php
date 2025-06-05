<div>
    <div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chartModalLabel">Оммавий норозиликлар бўйича маълумот</h5>
                <button type="button" class="btn-close" wire:click="$emit('closeReasonModal')" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @forelse ($reasons as $key => $reason)
                    <div class="mb-3 p-3 border-start border-3 border-primary bg-white shadow-sm reason_text">
                        <h6 class="fw-bold mb-2">#{{ $loop->iteration }} {{findDistrict($reason->district_code)}}</h6>
                        {!! $reason->context !!}
                    </div>                
                @empty
                    <div>
                        <p class="text-muted">Маълумот мавжуд эмас</p>
                    </div>
                @endforelse
                    
            </div>
            </div>
        </div>
    </div>
</div>

@prepend('scripts')
    <script>
        window.addEventListener('openReasonModal', event => {
            $("#reasonModal").modal('show');
        });

        Livewire.on('closeReasonModal', () => {
            $("#reasonModal").modal('hide');
        });
    </script>
@endprepend