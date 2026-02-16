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
        $activities = Activity::with(['implementationBasis'])
            ->where('user_id', $user->id)
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->where(function ($query) {
                // Strict Filter: Exclude single dash, dot, or whitespace
                $query->whereRaw("TRIM(description) NOT IN ('-', '.', '')");
            })
            ->orderBy('activity_date')
            ->get();

        $template = new TemplateProcessor(app_path('templates/catkin_template.docx'));

        // Header and Global Variables
        $school = SchoolSetting::first();
        $template->setValue('bulan', Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'));
        $template->setValue('tgl_ttd', Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y')); 
        
        $template->setValue('nama_guru', $user->name);
        $template->setValue('nip_guru', $user->nip);
        $template->setValue('nama_kepala', $school?->headmaster_name ?? '-');
        $template->setValue('nip_kepala', $school?->headmaster_nip ?? '-');

        // Initializing data
        $processedActivities = collect();
        $groupedActivities = $activities->groupBy(function($item) {
            return $item->activity_date->format('Y-m-d');
        });

        foreach ($groupedActivities as $date => $dailyActivities) {
            $dateObj = Carbon::createFromFormat('Y-m-d', $date);
            if (!$dateObj->isSunday() && !$dateObj->isSaturday()) {
                $routine = new \stdClass();
                $routine->activity_date = $dateObj->copy();
                $routine->description = $dateObj->isMonday() ? 'Upacara bendera / Apel pagi' : 'Murottal dan Sholat Dhuha berjamaah';
                $routine->reference_source = '-';
                $routine->implementationBasis = null;
                $routine->output_result = 'Terlaksana';
                $processedActivities->push($routine);
            }
            foreach ($dailyActivities as $act) {
                $processedActivities->push($act);
            }
        }

        $template->cloneRow('no', max(1, $processedActivities->count()));

        if ($processedActivities->isEmpty()) {
            $template->setValue('no#1', '');
            $template->setValue('date#1', '');
            $template->setValue('dasar#1', '');
            $template->setValue('uraian#1', 'Tidak ada data');
            $template->setValue('hasil#1', '');
        } else {
            $no = 1;
            $lastDate = null;
            foreach ($processedActivities as $index => $activity) {
                $i = $index + 1;
                $currentDate = $activity->activity_date->format('Y-m-d');
                
                // Content
                $template->setValue("no#{$i}", $currentDate !== $lastDate ? $no++ : '');
                $template->setValue("date#{$i}", $currentDate !== $lastDate ? $activity->activity_date->translatedFormat('l, j F Y') : '');
                
                $dasar = (isset($activity->implementationBasis) && $activity->implementationBasis) 
                    ? $activity->implementationBasis->name 
                    : ($activity->reference_source ?? '-');
                
                $template->setValue("dasar#{$i}", $dasar);
                $template->setValue("uraian#{$i}", $activity->description);
                $template->setValue("hasil#{$i}", $activity->output_result ?? 'Terlaksana');
                
                $lastDate = $currentDate;
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
        $activities = Activity::with(['classRooms'])
            ->where('user_id', $user->id)
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            // Filter only Teaching Categories
            ->whereHas('category', function ($query) {
                $query->where('is_teaching', true);
            })
            // Strict Filter for Topic (Materi)
            ->whereNotNull('topic')
            ->where('topic', '!=', '')
            ->where(function ($query) {
                $query->whereRaw("TRIM(topic) NOT IN ('-', '.', '')");
            })
            ->orderBy('activity_date')
            ->orderBy('period_start')
            ->get();
            
        $template = new TemplateProcessor(app_path('templates/jurnal_template.docx'));
        
        // Header and Signatures
        $school = SchoolSetting::first();
        $template->setValue('bulan', Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'));
        $template->setValue('tgl_ttd', Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y')); 
        
        $template->setValue('nama_guru', $user->name);
        $template->setValue('nip_guru', $user->nip);
        $template->setValue('nama_kepala', $school?->headmaster_name ?? '-');
        $template->setValue('nip_kepala', $school?->headmaster_nip ?? '-');

        $template->setValue('mapel', $user->subject ?? '-');

        if ($month >= 7) {
            $semester = 'GANJIL';
            $tahunAjaran = "{$year}/" . ($year + 1);
        } else {
            $semester = 'GENAP';
            $tahunAjaran = ($year - 1) . "/{$year}";
        }
        $template->setValue('semester', $semester);
        $template->setValue('tahun_ajaran', $tahunAjaran);


        $template->cloneRow('no', max(1, $activities->count()));

        if ($activities->isEmpty()) {
            $template->setValue('no#1', '');
            $template->setValue('tgl#1', '');
            $template->setValue('kelas#1', '');
            $template->setValue('jam#1', '');
            $template->setValue('materi#1', 'Tidak ada data');
            $template->setValue('ket#1', '');
        } else {
            $no = 1;
            $lastDate = null;
            foreach ($activities as $index => $activity) {
                $i = $index + 1;
                $currentDate = $activity->activity_date->format('Y-m-d');
                
                $template->setValue("no#{$i}", $currentDate !== $lastDate ? $no++ : '');
                $template->setValue("date#{$i}", $currentDate !== $lastDate ? $activity->activity_date->translatedFormat('l, j F Y') : '');

                $classNames = $activity->classRooms->count() > 0 
                    ? $activity->classRooms->pluck('name')->join(', ') 
                    : ($activity->class_name ?? '-');

                $jam = ($activity->period_start && $activity->period_end) 
                    ? "{$activity->period_start} - {$activity->period_end}" 
                    : '-';

                $template->setValue("kelas#{$i}", $classNames);
                $template->setValue("jam#{$i}", $jam);
                $template->setValue("materi#{$i}", $activity->topic ?? '-');
                $template->setValue("ket#{$i}", $activity->student_outcome ?? '');
                
                $lastDate = $currentDate;
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

        // Group by category AND description to count volume
        $reportData = Activity::query()
            ->where('user_id', $user->id)
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->with('category')
            ->get()
            ->groupBy(function($activity) {
                // Grouping key: CategoryID + Description
                return $activity->category_id . '_' . md5($activity->description);
            });


        $allActivities = Activity::query()
            ->where('user_id', $user->id)
            ->whereYear('activity_date', $year)
            ->whereMonth('activity_date', $month)
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->get();

        $uniqueDates = $allActivities->pluck('activity_date')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->unique();
        
        $mondayCount = 0;
        $otherDayCount = 0; 

        foreach ($uniqueDates as $dateStr) {
            $d = Carbon::createFromFormat('Y-m-d', $dateStr);
            if ($d->isSunday() || $d->isSaturday()) continue;
            if ($d->isMonday()) {
                $mondayCount++;
            } else {
                $otherDayCount++;
            }
        }

        $addRoutineSummary = function($name, $count, $rhk_label) use ($reportData) {
            if ($count > 0) {
                $cat = new ReportCategory();
                $cat->name = $name;
                $cat->rhk_label = $rhk_label;
                
                $acts = collect();
                for ($i=0; $i < $count; $i++) {
                    $a = new Activity();
                    $a->setRelation('category', $cat);
                    $a->description = $name; // For routine, description is same as name
                    $a->evidence_link = '-';
                    $acts->push($a);
                }
                
                $reportData->put('routine_' . \Illuminate\Support\Str::slug($name), $acts);
            }
        };

        $addRoutineSummary('Upacara bendera / Apel pagi', $mondayCount, 'Terlaksananya kegiatan pembiasaan dan kedisiplinan siswa');
        $addRoutineSummary('Murottal dan Sholat Dhuha berjamaah', $otherDayCount, 'Terlaksananya kegiatan keagamaan dan pembiasaan siswa');

        $template = new TemplateProcessor(app_path('templates/labul_template.docx'));

        $template->setValue('bulan', Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'));
        $template->setValue('tgl_ttd', Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y')); 
        $template->setValue('nama', $user->name); 
        $template->setValue('nama_guru', $user->name); 
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
            $template->setValue("no#1", '');
            $template->setValue("rhk#1", '');
            $template->setValue("kegiatan#1", 'Belum ada kegiatan');
            $template->setValue("vol#1", '');
            $template->setValue("eviden#1", '');
        } else {
            $index = 0;
            foreach ($reportData as $key => $activities) {
                $index++;
                $firstActivity = $activities->first();
                $category = $firstActivity->category;
                $description = $firstActivity->description;
                $count = $activities->count();
                
                $evidenceLinks = $activities->pluck('evidence_link')->filter()->unique()->implode("\n");

                $template->setValue("no#{$index}", $index);
                $template->setValue("rhk#{$index}", $category->rhk_label);
                $template->setValue("kegiatan#{$index}", $description); // Key fix: show description instead of category name
                $template->setValue("vol#{$index}", "{$count} kgt"); 
                $template->setValue("eviden#{$index}", $evidenceLinks ?: '');
            }
        }

        $filename = "Labul_{$user->name}_{$month}-{$year}.docx";
        $path = storage_path("app/public/{$filename}");
        $template->saveAs($path);

        return $path;
    }
}
