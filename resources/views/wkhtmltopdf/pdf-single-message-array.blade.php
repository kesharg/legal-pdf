@php
    $message = $eData['body'];
@endphp

@if (in_array($isAttachmentSelected, ['0', '2']))
    @foreach ($message as $thread)
        @if (is_array($thread))
            <section class="pdf-message-collection">
                <div class="message-counter-head text-bold" dir="{{ $direction }}">
                    <div class="numberDiv">
                        <span class="number" dir="{{ $direction }}">
                            <span>{{ localize('message_number', $language) }}:</span>
                            <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{ sprintf('%02d', $count) }}</span>
                            <span style="color: #c3b019;" dir="{{ $direction }}">{{ localize('thread', $language) }}</span>
                            <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}" style="color: #c3b019;">
                                {{ $loop->count }} / {{ $loop->iteration }}
                            </span>
                        </span>
                    </div>
                    <div class="dateDiv">
                        <span class="date" dir="{{ $direction }}">
                            <span>{{ localize('date', $language) }}:</span>
                            <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{ $thread['date'] }}</span>
                            |
                            <span>{{ localize('time', $language) }}:</span>
                            <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{ $thread['time'] }}</span>
                        </span>
                    </div>
                    <div class="from-to-container">
                        <span dir="{{ $direction }}">
                            <span>{{ $thread['senderName'] }}</span>
                            <span dir="{{ $direction }}">{{ localize('writes_to', $language) }} >></span>
                            <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{ $thread['receiverName'] }}</span>
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
                        {!! $thread['content'] !!}
                    </div>
                </div>

                <!-- Mail Attachments -->
                @if (in_array($isAttachmentSelected, ['1', '2']))
                    <div class="clear-both"></div>
                    <div class="message-body">
                        <div class="content" style="font-size:1rem;margin-top: 8px;color: #c3b019;" dir="auto">
                            @if (isset($thread['attachments']) && count($thread['attachments']) == 0)
                                <p>{{ localize('pdf_attachment_label', $language) }}:
                                    <strong>{{ localize('pdf_attachment_label_none', $language) }}</strong></p>
                            @else
                                <p>{{ localize('pdf_attachment_label', $language) }}: </p>
                                <div>
                                    @foreach ($thread['attachments'] as $attachment)
                                        <div id="{{ $attachment['attachment_id'] }}">{{ $attachment['file_name'] }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif



            </section>
        @else
            <section class="pdf-message-collection">
                <div class="message-counter-head text-bold" dir="{{ $direction }}">
                    <div class="numberDiv">
                        <span class="number" dir="{{ $direction }}">
                            <span>{{ localize('message_number', $language) }}:</span>
                            <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{ sprintf('%02d', $count) }}</span>
                            <span style="color: #c3b019;" dir="{{ $direction }}">{{ localize('thread', $language) }}</span>
                            <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}" style="color: #c3b019;">
                                {{ $loop->count }} / {{ $loop->iteration }}
                            </span>
                        </span>
                    </div>
                    <div class="dateDiv">
                        <span class="date" dir="{{ $direction }}">
                            <span>{{ localize('date', $language) }}:</span>
                            <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{ $thread['date'] }}</span>
                            |
                            <span>{{ localize('time', $language) }}:</span>
                            <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{ $thread['time'] }}</span>
                        </span>
                    </div>
                    <div class="from-to-container">
                        <span dir="{{ $direction }}">
                            <span>{{ $thread['senderName'] }}</span>
                            <span dir="{{ $direction }}">{{ localize('writes_to', $language) }} >></span>
                            <span dir="{{($direction == 'ltr')? 'rtl': 'ltr' }}">{{ $thread['receiverName'] }}</span>
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
                        {!! $thread['content'] !!}
                    </div>
                </div>

            </section>
        @endif
    @endforeach
@endif
