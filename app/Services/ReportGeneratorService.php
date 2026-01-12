<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ReportCategory;
use App\Models\SchoolSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\TemplateProcessor;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportGeneratorService
{
    public function __construct()
    {
        Carbon::setLocale('id');
    }

    /**
     * Generate CATKIN (Catatan Kinerja)
     * Chronological log of ALL activities.
     */
    public function generateCatkin($month, $year)
    {
        $user = Auth::user();
        $activities = Activity::where('user_id', $user->id)
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            ->orderBy('activity_date')
            ->get();

        $school = SchoolSetting::first();

        $data = [
            'school' => $school,
            'user' => $user,
            'monthName' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F'),
            'year' => $year,
            'activities' => $activities,
            'signatureDate' => Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y'),
        ];

        $pdf = Pdf::loadView('reports.catkin_pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = "Catkin_{$user->name}_{$month}-{$year}.pdf";
        $path = storage_path("app/public/{$filename}");
        $pdf->save($path);

        return $path;
    }

    /**
     * Generate JURNAL (Teaching Journal)
     * Filtered only for Teaching (KBM) activities.
     */
    public function generateJurnal($month, $year)
    {
        $user = Auth::user();
        $activities = Activity::where('user_id', $user->id)
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            ->orderBy('activity_date')
            ->orderBy('period_start')
            ->get();
            
        $school = SchoolSetting::first();
        
        $data = [
            'school' => $school,
            'user' => $user,
            'monthName' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F'),
            'year' => $year,
            'activities' => $activities,
            'signatureDate' => Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y'),
        ];

        $pdf = Pdf::loadView('reports.jurnal_pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = "Jurnal_{$user->name}_{$month}-{$year}.pdf";
        $path = storage_path("app/public/{$filename}");
        $pdf->save($path);

        return $path;
    }

    /**
     * Generate LABUL (Laporan Bulanan)
     * Grouped by Category with counts.
     */
    public function generateLabul($month, $year)
    {
        $user = Auth::user();

        // Group by category to count volume
        $reportData = Activity::query()
            ->where('user_id', $user->id)
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            ->with('category')
            ->get()
            ->groupBy('category_id');

        $template = new TemplateProcessor(storage_path('app/templates/labul_template.docx'));

        $template->setValue('bulan', Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'));
        $template->setValue('tgl_ttd', Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y')); // Added Signature Date
        $template->setValue('nama', $user->name); // Short variable
        $template->setValue('nama_guru', $user->name); // Long variable (user might use either)
        $template->setValue('nip', $user->nip);
        $template->setValue('nip_guru', $user->nip);
        $template->setValue('jabatan', $user->jabatan);
        $template->setValue('unit_kerja', $user->unit_kerja);
        $template->setValue('pangkat', $user->pangkat_gol);
        
        $school = SchoolSetting::first();

        $template->setValue('nama_kepala', $school?->headmaster_name ?? '-');
        $template->setValue('nip_kepala', $school?->headmaster_nip ?? '-');
        
        $template->cloneRow('rhk', max(1, $reportData->count()));

        if ($reportData->isEmpty()) {
            $template->setValue("no#1", '-');
            $template->setValue("rhk#1", '-');
            $template->setValue("kegiatan#1", 'Belum ada kegiatan');
            $template->setValue("vol#1", '-');
            $template->setValue("eviden#1", '-');
        } else {
            $index = 0;
            foreach ($reportData as $categoryId => $activities) {
                $index++;
                $category = $activities->first()->category;
                $count = $activities->count();
                
                $evidenceLinks = $activities->pluck('evidence_link')->filter()->implode("\n");

                $template->setValue("no#{$index}", $index);
                $template->setValue("rhk#{$index}", $category->rhk_label);
                $template->setValue("kegiatan#{$index}", $category->name);
                $template->setValue("vol#{$index}", "{$count} kgt"); 
                $template->setValue("eviden#{$index}", $evidenceLinks ?: '-');
            }
        }

        $filename = "Labul_{$user->name}_{$month}-{$year}.docx";
        $path = storage_path("app/public/{$filename}");
        $template->saveAs($path);

        return $path;
    }
}
