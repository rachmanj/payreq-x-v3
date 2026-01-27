<?php

namespace App\Services;

use App\Http\Controllers\DocumentNumberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PcbcService
{
    public function calculateFisikAmount(Request $request): float
    {
        return ($request->kertas_100rb ?? 0) * 100000 +
            ($request->kertas_50rb ?? 0) * 50000 +
            ($request->kertas_20rb ?? 0) * 20000 +
            ($request->kertas_10rb ?? 0) * 10000 +
            ($request->kertas_5rb ?? 0) * 5000 +
            ($request->kertas_2rb ?? 0) * 2000 +
            ($request->kertas_1rb ?? 0) * 1000 +
            ($request->kertas_500 ?? 0) * 500 +
            ($request->kertas_100 ?? 0) * 100 +
            ($request->logam_1rb ?? 0) * 1000 +
            ($request->logam_500 ?? 0) * 500 +
            ($request->logam_200 ?? 0) * 200 +
            ($request->logam_100 ?? 0) * 100 +
            ($request->logam_50 ?? 0) * 50 +
            ($request->logam_25 ?? 0) * 25;
    }

    public function generateDocumentNumber(string $project): string
    {
        return app(DocumentNumberController::class)->generate_document_number('pcbc', $project);
    }

    public function uploadFile($file): string
    {
        // Validate file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('File size exceeds 5MB limit.');
        }

        // Validate MIME type (PDF only)
        $allowedMimes = ['application/pdf'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Only PDF files are allowed.');
        }

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = 'pcbc_' . uniqid() . '_' . time() . '.' . $extension;
        
        // Move file to storage
        $file->move(public_path('dokumens'), $filename);
        
        Log::info('PCBC file uploaded', [
            'filename' => $filename,
            'size' => $file->getSize(),
            'user_id' => auth()->id()
        ]);
        
        return $filename;
    }

    public function deleteFile(string $filename): bool
    {
        $filePath = public_path('dokumens/' . $filename);
        
        if (file_exists($filePath)) {
            try {
                unlink($filePath);
                Log::info('PCBC file deleted', [
                    'file' => $filename,
                    'user_id' => auth()->id()
                ]);
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to delete PCBC file', [
                    'file' => $filename,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id()
                ]);
                return false;
            }
        }
        
        return false;
    }

    public function validateAmounts(float $systemAmount, float $fisikAmount, float $sapAmount): array
    {
        $systemVariance = $systemAmount - $fisikAmount;
        $sapVariance = $sapAmount - $fisikAmount;
        
        return [
            'system_variance' => $systemVariance,
            'sap_variance' => $sapVariance,
            'has_variance' => abs($systemVariance) > 0.01 || abs($sapVariance) > 0.01,
        ];
    }

    public function validatePhysicalAmount(float $calculatedAmount, float $submittedAmount): bool
    {
        return abs($calculatedAmount - $submittedAmount) <= 0.01;
    }
}
