<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ReportCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\TemplateProcessor;

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

        $template = new TemplateProcessor(storage_path('app/templates/catkin_template.docx'));

        // Header Data
        $template->setValue('bulan', Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'));
        $template->setValue('tgl_ttd', Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y')); // Added Signature Date
        $template->setValue('nama_guru', $user->name);
        $template->setValue('nip_guru', $user->nip);
        $template->setValue('nama_kepala', $user->headmaster_name);
        $template->setValue('nip_kepala', $user->headmaster_nip);
        
        // Clone Rows
        $template->cloneRow('date', max(1, $activities->count()));

        if ($activities->isEmpty()) {
            $template->setValue("no#1", '-');
            $template->setValue("dasar#1", '-');
            $template->setValue("date#1", '-');
            $template->setValue("uraian#1", 'Tidak ada kegiatan');
            $template->setValue("hasil#1", '-');
        } else {
            foreach ($activities as $index => $activity) {
                $i = $index + 1;
                $date = Carbon::parse($activity->activity_date)->translatedFormat('l, j F Y');
                
                $template->setValue("no#{$i}", $i);
                $template->setValue("dasar#{$i}", $activity->reference_source ?? '-');
                $template->setValue("date#{$i}", $date);
                $template->setValue("uraian#{$i}", $activity->description);
                $template->setValue("hasil#{$i}", $activity->output_result ?? 'Terlaksana');
            }
        }

        $filename = "Catkin_{$user->name}_{$month}-{$year}.docx";
        $path = storage_path("app/public/{$filename}");
        $template->saveAs($path);

        return $path;
    }

    /**
     * Generate JURNAL (Teaching Journal)
     * Filtered only for Teaching (KBM) activities.
     */
    public function generateJurnal($month, $year)
    {
        $user = Auth::user();
        
        // Get only teaching activities
        $activities = Activity::query()
            ->where('user_id', $user->id)
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            ->whereHas('category', function ($q) {
                $q->where('is_teaching', true);
            })
            ->orderBy('activity_date')
            ->get();
            
        $template = new TemplateProcessor(storage_path('app/templates/jurnal_template.docx'));
        
        $template->setValue('semester', ($month >= 7 && $month <= 12) ? 'Ganjil' : 'Genap');
        $template->setValue('tahun_ajaran', "{$year}/" . ($year+1)); 
        $template->setValue('nama_guru', $user->name);
        $template->setValue('nip_guru', $user->nip); // Added
        $template->setValue('nama_kepala', $user->headmaster_name); // Added
        $template->setValue('nip_kepala', $user->headmaster_nip); // Added
        $template->setValue('mapel', $user->subject ?? '-'); 
        $template->setValue('bulan', Carbon::createFromDate($year, $month, 1)->translatedFormat('F')); 
        $template->setValue('tgl_ttd', Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y')); 
        
        $template->cloneRow('date', max(1, $activities->count()));

        if ($activities->isEmpty()) {
             // Handle empty case
             $template->setValue("no#1", '-'); // Added
             $template->setValue("date#1", '-');
             $template->setValue("kelas#1", '-');
             $template->setValue("jam#1", '-');
             $template->setValue("jam_ke#1", '-'); // Added
             $template->setValue("materi#1", 'Tidak ada KBM');
             $template->setValue("ket#1", '-');
        } else {
            foreach ($activities as $index => $act) {
                $i = $index + 1;
                $date = Carbon::parse($act->activity_date)->translatedFormat('l, j F Y');
                $jam = "{$act->period_start} - {$act->period_end}";

                $template->setValue("no#{$i}", $i); // Added
                $template->setValue("date#{$i}", $date);
                $template->setValue("kelas#{$i}", $act->class_name);
                $template->setValue("jam#{$i}", $jam);
                $template->setValue("jam_ke#{$i}", $jam); // Added to handle 'jam ke' variable
                $template->setValue("materi#{$i}", $act->topic);
                $template->setValue("ket#{$i}", $act->student_outcome);
            }
        }

        $filename = "Jurnal_{$user->name}_{$month}-{$year}.docx";
        $path = storage_path("app/public/{$filename}");
        $template->saveAs($path);

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
        
        $template->setValue('nama_kepala', $user->headmaster_name);
        $template->setValue('nip_kepala', $user->headmaster_nip);
        
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
