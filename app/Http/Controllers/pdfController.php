<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\SendPdfMail;
use App\Models\NetPension;
use App\Models\NetSalary;
use Illuminate\Support\Facades\Mail;

class PdfController extends Controller
{
    public function testPdf(Request $request)
    {
        $userEmail = $request->input('email'); // user email from request



        // 1. Generate PDF
        // $data = ['title' => 'Laravel PDF Example', 'date' => now()->format('d-m-Y')];
        // $salary = NetSalary::with('deduction.deductionRecoveries', 'paySlip.salaryArrears', 'employeeRelation')->find(176);
        $pension = NetPension::with(
            'pensionerDeduction',
            'monthlyPension',
            'pensioner.employee',
        )->find(51);
        // dd($pension);
        $pdf = Pdf::loadView('pdf.pensionPdf', compact('pension'));
        // 2. Send Email with PDF Attachment
        // Mail::to($userEmail)->send(new SendPdfMail($pdf));
        return $pdf->stream('Test.pdf');
        // return response()->json(['message' => 'PDF sent successfully to ' . $userEmail]);
    }
}
