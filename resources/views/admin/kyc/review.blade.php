@extends('admin.layouts.app')
@section('title', 'Vérification KYC — ' . $driver->first_name . ' ' . $driver->last_name)

@section('content')
<div style="display:flex;flex-direction:column;gap:16px">

    <div class="page-header">
        <h1 class="page-title">Vérification KYC</h1>
        <p class="page-sub">{{ $driver->first_name }} {{ $driver->last_name }} — Dossier chauffeur</p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

        {{-- Infos identité --}}
        <div class="panel">
            <div class="panel-header">Identité</div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                @foreach([
                    ['Prénom',       $driver->first_name ?? 'N/A'],
                    ['Nom',          $driver->last_name  ?? 'N/A'],
                    ['Téléphone',    $driver->phone      ?? 'N/A'],
                    ['Né(e) le',     $driver->birth_date ?? 'N/A'],
                    ['Lieu naissance',$driver->birth_place ?? 'N/A'],
                    ['Pays naissance',$driver->country_birth ?? 'N/A'],
                ] as [$lbl,$val])
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding-bottom:8px;font-size:13px">
                    <span style="color:var(--text-3);font-weight:600">{{ $lbl }}</span>
                    <span style="color:var(--text-1);font-weight:500">{{ $val }}</span>
                </div>
                @endforeach

                <div style="display:flex;justify-content:space-between;font-size:13px">
                    <span style="color:var(--text-3);font-weight:600">Statut</span>
                    <span class="badge {{ $driver->status === 'pending' ? 'badge-warning' : ($driver->status === 'approved' ? 'badge-success' : 'badge-danger') }}">
                        {{ ucfirst($driver->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Infos véhicule --}}
        <div class="panel">
            <div class="panel-header">Véhicule</div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                @foreach([
                    ['Marque',   $driver->vehicle_brand ?? 'N/A'],
                    ['Modèle',   $driver->vehicle_model ?? 'N/A'],
                    ['Plaque',   $driver->vehicle_plate ?? 'N/A'],
                    ['Couleur',  $driver->vehicle_color ?? 'N/A'],
                    ['Type',     $driver->vehicle_type  ?? 'N/A'],
                    ['Pays',     $driver->vehicle_country ?? 'N/A'],
                ] as [$lbl,$val])
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding-bottom:8px;font-size:13px">
                    <span style="color:var(--text-3);font-weight:600">{{ $lbl }}</span>
                    <span style="color:var(--text-1);font-weight:500">{{ $val }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Documents --}}
        <div class="panel" style="grid-column:1/-1">
            <div class="panel-header">Documents fournis</div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px">
                    @foreach([
                        ['CNI (recto)',    $driver->id_card_front],
                        ['CNI (verso)',    $driver->id_card_back],
                        ['Permis (recto)', $driver->license_front],
                        ['Permis (verso)', $driver->license_back],
                        ['Carte grise',    $driver->vehicle_registration],
                        ['Assurance',      $driver->insurance],
                    ] as [$lbl,$doc])
                    <div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
                        <div style="padding:8px 12px;background:#FAFAFA;font-size:11px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.05em">{{ $lbl }}</div>
                        @if($doc)
                            <a href="{{ Storage::url($doc) }}" target="_blank">
                                <img src="{{ Storage::url($doc) }}" alt="{{ $lbl }}"
                                     style="width:100%;height:140px;object-fit:cover;display:block"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div style="display:none;height:140px;align-items:center;justify-content:center;background:#F2F3F3;font-size:12px;color:var(--text-3)">Voir le document</div>
                            </a>
                        @else
                            <div style="height:140px;display:flex;align-items:center;justify-content:center;background:#F2F3F3;font-size:12px;color:var(--text-3)">Non fourni</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- Actions --}}
    @if($driver->status === 'pending')
    <div class="panel">
        <div class="panel-header">Decision</div>
        <div class="card-body" style="display:flex;gap:16px;flex-wrap:wrap">

            <form method="POST" action="{{ route('admin.kyc.approve', $driver) }}" style="margin:0"
                  onsubmit="return confirm('Approuver ce chauffeur ?')">
                @csrf
                <button class="btn btn-success">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Approuver
                </button>
            </form>

            <form method="POST" action="{{ route('admin.kyc.reject', $driver) }}" style="margin:0;flex:1;min-width:280px"
                  onsubmit="return confirm('Rejeter ce chauffeur ?')">
                @csrf
                <div style="display:flex;gap:10px;align-items:center">
                    <input type="text" name="rejection_reason" required placeholder="Raison du rejet..."
                           class="ttg-input" style="flex:1">
                    <button class="btn btn-danger">Rejeter</button>
                </div>
            </form>

        </div>
    </div>
    @endif

    <div>
        <a href="{{ route('admin.kyc.index', ['status' => $driver->status]) }}" class="btn-back">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            Retour à la liste
        </a>
    </div>

</div>
@endsection
