<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportController extends Controller
{
    public function export()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="financial-report-' . now()->format('Y-m-d') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Month', 'Revenue', 'Confirmed Bookings']);

            $start = now()->startOfYear();
            $end = now()->endOfYear();

            $payments = Payment::whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as revenue')
                ->groupBy('month')
                ->get()
                ->keyBy('month');

            $bookings = Booking::whereBetween('created_at', [$start, $end])
                ->where('status', 'confirmed')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->get()
                ->keyBy('month');

            for ($m = 1; $m <= 12; $m++) {
                $date = now()->month($m);
                $key = $date->format('Y-m');
                
                fputcsv($handle, [
                    $date->format('F Y'),
                    $payments[$key]->revenue ?? 0,
                    $bookings[$key]->count ?? 0,
                ]);
            }
            
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
