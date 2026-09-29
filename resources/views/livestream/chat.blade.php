<section class="live-chat {{ ($staff ?? false) ? 'live-chat--studio' : '' }}" data-live-chat
         data-list-url="{{ route('livestream.messages', $livestream) }}"
         data-send-url="{{ route('livestream.messages.send', $livestream) }}"
         data-presence-url="{{ route('livestream.presence', $livestream) }}"
         data-auto-presence="{{ !($staff ?? false) && $livestream->source === 'youtube' ? '1' : '0' }}"
         @if($staff ?? false) data-hide-base="{{ url('/admin/livestreams/'.$livestream->id.'/messages') }}" @endif
         aria-labelledby="live-chat-title">
    <div class="live-chat-header">
        <div><span class="live-chat-kicker">Cùng trò chuyện</span><h3 id="live-chat-title">Góc trò chuyện</h3></div>
        <span class="live-chat-viewers" title="Số khách đang xem trong khoảng 45 giây gần đây"><span class="live-chat-viewer-dot"></span><span data-live-watching>0</span> đang xem</span>
    </div>
    <div class="live-chat-messages" data-live-messages role="log" aria-live="polite" aria-relevant="additions text">
        <p class="live-chat-empty">Hãy gửi lời chào hoặc hỏi về mùi hương bạn thích @include('partials.icon', ['name' => 'flower', 'size' => '1em'])</p>
    </div>
    <form class="live-chat-form" data-live-form>
        <label class="visually-hidden" for="live-chat-input-{{ ($staff ?? false) ? 'studio' : 'viewer' }}">Tin nhắn livestream</label>
        <input id="live-chat-input-{{ ($staff ?? false) ? 'studio' : 'viewer' }}" name="body" maxlength="300" autocomplete="off" placeholder="Viết lời nhắn của bạn..." required>
        <button type="submit" aria-label="Gửi tin nhắn">Gửi ↗</button>
    </form>
    <p class="live-chat-hint" data-live-feedback role="status">Bình luận hiển thị công khai trong buổi live.</p>
</section>
