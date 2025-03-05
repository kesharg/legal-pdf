@php
    $message = str_replace('�', '', $eData['body']);
@endphp

@if(in_array($isAttachmentSelected, ["0", "2"]))
<section class="pdf-message-collection">
    <div class="message-counter-head text-bold" dir="{{ $direction }}">
        <div class="numberDiv">
            <span class="number" dir="{{ $direction }}">
                <span>{{ localize('message_number', $language) }}:</span>
                <span dir="ltr">{{ sprintf('%02d', $count) }}</span>
            </span>
        </div>
        <div class="dateDiv">
            <span class="date" dir="{{ $direction }}">
                <span>{{ localize('date', $language) }}:</span>
                <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{$date}}</span>
                |
                <span>{{ localize('time', $language) }}:</span>
                <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{$time}}</span>
            </span>
        </div>
        <div class="from-to-container">
            <span dir="{{ $direction }}">
                <span dir="auto">{{ $eData['senderName'] }}</span>
                <span dir="{{ $direction }}">{{ localize('writes_to', $language) }} >></span>
                <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{ $eData['receiverName'] }}</span>
            </span>
        </div>
        <div class="subjectDiv">
            @php
                $subString = $eData['subject'];
            @endphp
            <span class="subject" dir="{{ $direction }}">
                <span>{{ localize('subject', $language) }}:</span>
                <span dir="auto">{!! $subString !!}</span>
            </span>
        </div>
    </div>   
    <div class="clear-both"></div>
    <div class="message-body">
        <div class="content" style="text-align:justify;font-size:1rem;margin-top: 8px;" dir="auto">
            <!-- Email content goes here -->
            {!! $message !!}
        </div>
    </div>
</section>
@endif
