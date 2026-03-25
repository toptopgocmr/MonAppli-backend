<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('user', 'driver', 'trip');

        if ($request->status)  $query->where('status', $request->status);
        if ($request->method)  $query->where('method', $request->method);
        if ($request->country) $query->where('country', $request->country);
        if ($request->city)    $query->where('city', $request->city);
        if ($request->from)    $query->whereDate('created_at', '>=', $request->from);
        if ($request->to)      $query->whereDate('created_at', '<=', $request->to);

        return response()->json($query->latest()->paginate(20));
    }

    public function show($id)
    {
        return response()->json(Payment::with('user', 'driver', 'trip')->findOrFail($id));
    }

    public function exportCsv(Request $request)
    {
        $payments = Payment::with('user', 'driver')->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payments.csv"',
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Client', 'Chauffeur', 'Montant', 'Commission', 'Net', 'Méthode', 'Statut', 'Date']);
            foreach ($payments as $p) {
                fputcsv($file, [
                    $p->id,
                    $p->user?->first_name . ' ' . $p->user?->last_name,
                    $p->driver?->first_name . ' ' . $p->driver?->last_name,
                    $p->amount,
                    $p->commission,
                    $p->driver_net,
                    $p->method,
                    $p->status,
                    $p->created_at,
                ]);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
