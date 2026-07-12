@extends('company.layouts.app')
@section('title', 'Support TopTopGo')

@section('content')

<div class="aws-page-header">
    <div class="aws-crumb"><a href="{{ route('company.dashboard') }}">Dashboard</a> › Support TopTopGo</div>
    <h1 class="aws-page-title">💬 Support TopTopGo</h1>
</div>

@if(session('success'))
<div class="aws-alert aws-alert-success">✓ {{ session('success') }}</div>
@endif

<div class="aws-panel" style="margin-bottom:0">
    <div class="aws-panel-header">
        <span class="aws-panel-title">Discussion avec le support</span>
        <span style="font-size:12px;color:var(--aws-sub)">{{ $messages->count() }} message(s)</span>
    </div>

    <div style="max-height:60vh;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:12px;background:var(--aws-bg)"
         id="chat-body">
        @forelse($messages as $msg)
        @php $isCompany = $msg->sender_type === \App\Models\Company::class; @endphp
        <div style="display:flex;flex-direction:column;align-items:{{ $isCompany ? 'flex-end' : 'flex-start' }}">
            <div style="font-size:10px;color:var(--aws-sub);margin-bottom:3px;padding:0 6px">
                {{ $isCompany ? '🏢 '.$company->name : '🛠 Support TopTopGo' }}
                · {{ $msg->created_at?->format('d/m H:i') }}
            </div>
            <div style="
                max-width:72%;
                padding:10px 14px;
                border-radius:{{ $isCompany ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }};
                background:{{ $isCompany ? '#ec7211' : '#fff' }};
                border:{{ $isCompany ? 'none' : '1px solid var(--aws-border)' }};
                color:{{ $isCompany ? '#fff' : '#16191f' }};
                font-size:13px;
                line-height:1.5;
            ">{{ $msg->content }}</div>
        </div>
        @empty
        <div style="text-align:center;padding:60px 20px;color:var(--aws-sub)">
            <div style="font-size:15px;font-weight:600;color:var(--aws-header);margin-bottom:8px">Démarrez la conversation</div>
            <div style="font-size:13px">Écrivez votre premier message au support TopTopGo ci-dessous.</div>
        </div>
        @endforelse
    </div>

    <div style="padding:16px 20px;border-top:1px solid var(--aws-border)">
        <form method="POST" action="{{ route('company.support.send') }}" style="display:flex;gap:10px;align-items:flex-end">
            @csrf
            <textarea name="content" rows="2" required placeholder="Écrire un message au support..."
                      class="aws-input" style="flex:1;resize:none"></textarea>
            <button type="submit" class="aws-btn aws-btn-primary">Envoyer</button>
        </form>
    </div>
</div>

<script>
    const chatBody = document.getElementById('chat-body');
    if (chatBody) chatBody.scrollTop = chatBody.scrollHeight;
    setInterval(() => location.reload(), 10000);
</script>

@endsection
