@extends('admin.layouts.app')
@section('title', 'Vérification KYC')

@section('content')
<div style="display:flex;flex-direction:column;gap:16px">

    <div class="page-header">
        <h1 class="page-title">Vérification KYC</h1>
        <p class="page-sub">Vérifier les documents d'identité des chauffeurs</p>
    </div>

    {{-- Onglets de statut --}}
    <div class="panel" style="padding:0">
        <nav style="display:flex;border-bottom:1px solid #e2e8f0">
            @php
                $tabs = [
                    'pending'  => ['label' => 'En attente',  'color' => '#F59E0B'],
                    'approved' => ['label' => 'Approuvés',   'color' => '#10B981'],
                    'rejected' => ['label' => 'Rejetés',     'color' => '#EF4444'],
                ];
            @endphp
            @foreach($tabs as $key => $tab)
                <a href="{{ route('admin.kyc.index', ['status' => $key]) }}"
                   style="padding:14px 20px;font-size:13px;font-weight:600;text-decoration:none;
                          border-bottom:3px solid {{ $status === $key ? $tab['color'] : 'transparent' }};
                          color:{{ $status === $key ? $tab['color'] : '#64748b' }};
                          transition:color .2s">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Grille de cartes --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @forelse($drivers as $driver)
        <div class="panel" style="padding:0;overflow:hidden">
            {{-- En-tête coloré selon statut --}}
            <div style="padding:16px 20px;background:{{ $status === 'pending' ? '#fffbeb' : ($status === 'approved' ? '#f0fdf4' : '#fef2f2') }};
                        border-bottom:1px solid {{ $status === 'pending' ? '#fde68a' : ($status === 'approved' ? '#bbf7d0' : '#fecaca') }}">
                <div style="display:flex;align-items:center;gap:12px">
                    <div class="avatar" style="background:{{ $status === 'pending' ? '#fef3c7' : ($status === 'approved' ? '#dcfce7' : '#fee2e2') }};
                                               color:{{ $status === 'pending' ? '#d97706' : ($status === 'approved' ? '#16a34a' : '#dc2626') }}">
                        {{ strtoupper(substr($driver->user->first_name ?? 'D', 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:700;color:#1e293b;font-size:14px">
                            {{ $driver->user->first_name ?? 'N/A' }} {{ $driver->user->last_name ?? '' }}
                        </div>
                        <div style="font-size:12px;color:#64748b">{{ $driver->user->phone ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- Infos véhicule --}}
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:8px">
                @foreach([
                    ['Véhicule', ($driver->vehicle_brand ?? 'N/A').' '.($driver->vehicle_model ?? '')],
                    ['Plaque',   $driver->vehicle_plate ?? 'N/A'],
                    ['Permis',   $driver->license_number ?? 'N/A'],
                    ['Inscrit',  $driver->created_at->format('d/m/Y')],
                ] as [$lbl,$val])
                <div style="display:flex;justify-content:space-between;font-size:13px">
                    <span style="color:#64748b">{{ $lbl }}</span>
                    <span style="font-weight:600;color:#1e293b">{{ $val }}</span>
                </div>
                @endforeach
            </div>

            {{-- Action --}}
            <div style="padding:12px 20px;border-top:1px solid #f1f5f9">
                @if($status === 'pending')
                    <a href="{{ route('admin.kyc.review', $driver) }}" class="btn btn-primary" style="display:block;text-align:center;text-decoration:none">
                        Vérifier les documents
                    </a>
                @elseif($status === 'rejected')
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px;margin-bottom:10px;font-size:12px;color:#dc2626">
                        <strong>Raison:</strong> {{ $driver->kyc_rejection_reason ?? 'Non spécifiée' }}
                    </div>
                    <a href="{{ route('admin.kyc.review', $driver) }}" class="btn btn-secondary" style="display:block;text-align:center;text-decoration:none">
                        Revoir
                    </a>
                @else
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px;font-size:12px;color:#16a34a;text-align:center">
                        Approuvé le {{ $driver->kyc_reviewed_at ? $driver->kyc_reviewed_at->format('d/m/Y') : 'N/A' }}
                    </div>
                @endif
            </div>
        </div>
        @empty
        <div style="grid-column:1/-1">
            <div class="panel" style="text-align:center;padding:64px">
                <div style="font-size:48px;margin-bottom:12px"></div>
                                <p style="color:#64748b;font-weight:600">Aucun chauffeur</p>
                <p style="font-size:13px;color:#94a3b8;margin-top:4px">
                    Il n'y a aucun dossier dans cette catégorie.
                </p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($drivers->hasPages())
        <div style="margin-top:8px">
            {{ $drivers->links() }}
        </div>
    @endif

</div>
@endsection
