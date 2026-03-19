<div>
    <div class="timeline-card">
        <div class="timeline-header">
            <span class="timeline-label">
                <i class="bx bx-calendar-alt"></i>
                Давр танлаш
            </span>
            <span class="timeline-current-date" id="timeline-current-date"></span>
        </div>
        <div class="timeline-track-wrapper">
            <div id="slider"></div>
        </div>
    </div>

    @script
    <script>
        (function() {
            var dates = @json($months);

            function formatDate(d) {
                if (!d) return '';
                if (typeof d === 'number') return String(d);
                var parts = d.split('-');
                var months = ['Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'];
                return months[parseInt(parts[1]) - 1] + ' ' + parts[0];
            }

            function buildSlider(dates) {
                $("#slider").slider({
                    max: dates.length - 1,
                    value: dates.length - 1,
                    slide: function(event, ui) {
                        $('#timeline-current-date').text(formatDate(dates[ui.value]));
                    },
                    stop: function(event, ui) {
                        Livewire.dispatch('dateChanged', { date: dates[ui.value] });
                    }
                })
                .each(function() {
                    var opt = $(this).data().uiSlider.options;
                    var vals = opt.max - opt.min;
                    var arrayLength = dates.length;

                    var step = Math.ceil(arrayLength / 16);

                    for (var i = 0; i < arrayLength; i++) {
                        if (i % step === 0 || i === arrayLength - 1) {
                            if (typeof dates[i] === 'number') {
                                var el = $('<label>' + dates[i] + '</label>').css('left', (i / vals * 100) + '%');
                            } else {
                                var el = $('<label>' + (dates[i].substring(2, 7)) + '</label>').css('left', (i / vals * 100) + '%');
                            }
                            $("#slider").append(el);
                        }
                    }

                    $('#timeline-current-date').text(formatDate(dates[dates.length - 1]));
                });
            }

            buildSlider(dates);

            Livewire.on('changeSentimentTimeline', ({ dates }) => {
                $("#slider").slider("destroy");
                $("#slider").find('label').remove();
                buildSlider(dates);
            });
        })();
    </script>
    @endscript
</div>
