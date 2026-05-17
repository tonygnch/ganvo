@if (session()->has('impersonator_id'))
    @php
        $impersonator = \App\Models\User::find(session('impersonator_id'));
        $current = auth()->user();
    @endphp
    @if ($impersonator && $current)
        <div style="
            background: linear-gradient(90deg, #f59e0b, #d97706);
            color: white;
            padding: .75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.875rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        ">
            <div>
                <strong>Impersonating</strong>
                {{ $current->name }} ({{ $current->email }})
                — originally signed in as {{ $impersonator->name }}
            </div>
            <form method="POST" action="{{ route('impersonate.stop') }}" style="margin: 0;">
                @csrf
                <button type="submit" style="
                    background: white;
                    color: #d97706;
                    border: 0;
                    padding: .375rem 1rem;
                    border-radius: .375rem;
                    font-weight: 600;
                    cursor: pointer;
                    font-size: 0.875rem;
                ">Return to super admin</button>
            </form>
        </div>
    @endif
@endif
