<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\InstituteInfo;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    public function downloadReceipt($id)
    {
        try {

            // 1. Get payment with student relation
            $payment = Payment::with([
    'student.classInfo',
    'student.section'
])->findOrFail($id);

            // 2. Get institute information
            $institute = InstituteInfo::first();

            // 3. Generate PDF from blade view
            $pdf = Pdf::loadView('receipt', compact(
                'payment',
                'institute'
            ));

            // 4. Define folder inside public storage
            $dir = public_path('receipts');

            // 5. Create folder if not exists
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            // 6. File name
            $fileName = 'receipt_' . $payment->id . '.pdf';

            // 7. Full file path
            $filePath = $dir . '/' . $fileName;

            // 8. Save PDF file
            file_put_contents($filePath, $pdf->output());

            // 9. Public URL
            $url = asset('receipts/' . $fileName);

            return response()->json([
                'success' => true,
                'url' => $url
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Receipt generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
