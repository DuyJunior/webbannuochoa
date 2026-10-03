<form class="language-switcher {{ $languageClass ?? '' }}" method="POST" action="{{ route('locale.update') }}" data-language-switcher>
    @csrf
    <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
    <label><span class="language-label">{{ app()->getLocale() === 'en' ? 'Language' : 'Ngôn ngữ' }}</span>
        <select name="locale" aria-label="{{ app()->getLocale() === 'en' ? 'Display language' : 'Ngôn ngữ hiển thị' }}">
            <option value="vi" @selected(app()->getLocale() === 'vi')>VI · Tiếng Việt</option>
            <option value="en" @selected(app()->getLocale() === 'en')>EN · English</option>
        </select>
    </label>
    <button type="submit" class="language-apply">{{ app()->getLocale() === 'en' ? 'Apply' : 'Áp dụng' }}</button>
</form>
