<div>
    <div class="card" style="padding: 0 10px 25px 10px;">
        <div class="card-body timeline">
            <div id="slider"></div>
        </div>
    </div>
    @prepend('scripts')
        <script>
            $(function() {
                var dates = <?php echo json_encode($months) ?>;
                $("#slider").slider({
                    max: dates.length - 1,
                    value: 100,
                    slide: function(event, ui){
                    },
                    stop: function(event, ui){
                        window.livewire.emit('dateChanged', dates[ui.value]);
                    }
                })
                .each(function() {
                    var opt = $(this).data().uiSlider.options;
                    var vals = opt.max - opt.min;
                    var arrayLength = dates.length;
                    
                    for (var i = 0; i < arrayLength; i++) {
                        var el = $('<label>' + (dates[i].substring(2, 7)) + '</label>').css('left', (i / vals * 100) + '%');
                        $("#slider").append(el);
                    }
                });
            });


            Livewire.on('changeTimeline', (dates) => {
                $("#slider").slider("destroy");
                $("#slider").slider({
                    max: dates.length - 1,
                    value: 100,
                    stop: function(event, ui){
                        window.livewire.emit('dateChanged', dates[ui.value]);
                    }
                })
                .each(function() {
                    var opt = $(this).data().uiSlider.options;
                    var vals = opt.max - opt.min;
                    var arrayLength = dates.length;
                    
                    for (var i = 0; i < arrayLength; i++) {
                        var el = $('<label>' + (dates[i].substring(2, 7)) + '</label>').css('left', (i / vals * 100) + '%');
                        $("#slider").append(el);
                    }
                });
            });
        </script>    
    @endprepend
</div>