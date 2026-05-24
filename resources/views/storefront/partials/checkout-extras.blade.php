@php
    /*
     | Order notes textarea + marketing opt-in checkbox. Themes include
     | this in their checkout form before the submit button. Both
     | fields are optional; the controller validates accordingly.
     */
@endphp

<div class="ce">
    <div class="ce-field">
        <label for="notes" class="ce-label">{{ __('site.checkout.notes_label') }}
            <small>({{ __('site.common.optional') }})</small>
        </label>
        <textarea name="notes" id="notes" rows="3"
                  placeholder="{{ __('site.checkout.notes_placeholder') }}"
                  maxlength="2000">{{ old('notes') }}</textarea>
    </div>
    <label class="ce-check">
        <input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in'))>
        <span>{{ __('site.checkout.marketing_opt_in') }}</span>
    </label>
</div>

<style>
    .ce { display: flex; flex-direction: column; gap: 1rem; }
    .ce-field { display: flex; flex-direction: column; gap: .375rem; }
    .ce-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: inherit;
    }
    .ce-label small {
        color: rgba(0, 0, 0, .5);
        font-weight: 400;
        margin-left: .25rem;
    }
    .ce-field textarea {
        padding: .625rem .75rem;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: 8px;
        font: inherit;
        font-size: .9375rem;
        background: white;
        resize: vertical;
        min-height: 4.5rem;
    }
    .ce-field textarea:focus { outline: none; border-color: rgba(0, 0, 0, .5); }
    .ce-check {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .875rem;
        cursor: pointer;
        color: rgba(0, 0, 0, .8);
    }
    .ce-check input { width: 16px; height: 16px; accent-color: #111; }
</style>
