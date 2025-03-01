<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use Illuminate\Support\Facades\DB;

class BagisIstatistikleri extends Tags
{
    public function index()
    {
        try {
            $count = DB::table('form_submissions')
                ->where('form', 'iyiligi_ulastir')
                ->count();
                
            $submissions = DB::table('form_submissions')
                ->where('form', 'iyiligi_ulastir')
                ->get();
            
            $total = 0;
            foreach ($submissions as $submission) {
                $data = json_decode($submission->data, true);
                $total += floatval($data['toplam_tutar'] ?? 0);
            }
            
            return [
                'count' => $count,
                'total' => number_format($total, 0, ',', '.')
            ];
        } catch (\Exception $e) {
            return [
                'count' => 0,
                'total' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
}