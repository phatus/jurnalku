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

        $template = new TemplateProcessor(storage_path('app/templates/catkin_template.docx'));

        // Header Variables
        $school = SchoolSetting::first();
        $template->setValue('school_name', $school->school_name ?? 'NAMA MADRASAH BELUM DISET');
        $template->setValue('school_address', $school->school_address ?? 'Alamat belum diset');
        $template->setValue('monthName', Carbon::createFromDate($year, $month, 1)->translatedFormat('F'));
        $template->setValue('year', $year);
        
        // Signatures
        $template->setValue('signatureDate', Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y'));
        $template->setValue('user_name', $user->name);
        $template->setValue('user_nip', $user->nip);
        $template->setValue('headmaster_name', $school->headmaster_name ?? '.........................');
        $template->setValue('headmaster_nip', $school->headmaster_nip ?? '................');

        // Initialize processed activities collection
        $processedActivities = collect();
        
        // Group existing activities by date
        $groupedActivities = $activities->groupBy(function($item) {
            return $item->activity_date->format('Y-m-d');
        });

        // Iterate ONLY over days that have activities
        foreach ($groupedActivities as $date => $dailyActivities) {
            $dateObj = Carbon::createFromFormat('Y-m-d', $date);
            
            // Skip Sunday and Saturday if any activities somehow exist there (optional safety)
            // But per request "Check user input", if user input on Sunday, maybe we shouldn't add routine? 
            // Request said "Only add when user added activity", implied "on that day".
            // Let's stick to: If it's Mon-Fri AND has activity -> Add routine.
            
            if (!$dateObj->isSunday() && !$dateObj->isSaturday()) {
                // Determine Routine
                $routineDescription = $dateObj->isMonday() 
                    ? 'Upacara bendera / Apel pagi' 
                    : 'Murottal dan Sholat Dhuha berjamaah';
                
                // Create Virtual Routine Activity
                $routine = new \stdClass();
                $routine->activity_date = $dateObj->copy();
                $routine->description = $routineDescription;
                $routine->reference_source = '-';
                $routine->implementationBasis = null;
                $routine->output_result = 'Terlaksana';

                // Add Routine PREPENDED to the day
                $processedActivities->push($routine);
            }

            // Add Real Activities
            foreach ($dailyActivities as $act) {
                $processedActivities->push($act);
            }
        }

        // Process Rows for Table
        $table = new \PhpOffice\PhpWord\Element\Table([
            'borderSize' => 6, 
            'borderColor' => '000000', 
            'cellMargin' => 50,
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT, 
            'width' => 100 * 50 // 5000 % ? No, width usually in pct is 50*100 = 5000 (meaning 100%). Doc says 5000 = 100%.
        ]);
        
        // Header
        $table->addRow();
        $table->addCell(700)->addText('NO', ['bold' => true], ['alignment' => 'center']);
        $table->addCell(2500)->addText('HARI/TANGGAL', ['bold' => true], ['alignment' => 'center']);
        $table->addCell(3500)->addText('DASAR PELAKSANAAN', ['bold' => true], ['alignment' => 'center']);
        $table->addCell(5000)->addText('URAIAN PEKERJAAN', ['bold' => true], ['alignment' => 'center']);
        $table->addCell(2500)->addText('HASIL PEKERJAAN/OUTPUT', ['bold' => true], ['alignment' => 'center']);
        $table->addCell(1500)->addText('PARAF ATASAN', ['bold' => true], ['alignment' => 'center']);

        $no = 1;
        $lastDate = null;
        $lastBasis = null;

        foreach ($processedActivities as $index => $activity) {
            $currentDate = $activity->activity_date->format('Y-m-d');
            
            // Get Basis Name
            if (isset($activity->implementationBasis) && $activity->implementationBasis) {
                $basisName = $activity->implementationBasis->name;
            } else {
                $basisName = $activity->reference_source ?? '-';
            }

            $table->addRow();

            // 1. NO Column (Restart if new day)
            if ($currentDate !== $lastDate) {
                $table->addCell(700, ['vMerge' => 'restart'])->addText($no++);
            } else {
                $table->addCell(700, ['vMerge' => 'continue']);
            }

            // 2. DATE Column (Restart if new day)
            if ($currentDate !== $lastDate) {
                $table->addCell(2500, ['vMerge' => 'restart'])->addText($activity->activity_date->translatedFormat('l, j F Y'));
            } else {
                $table->addCell(2500, ['vMerge' => 'continue']);
            }

            // 3. DASAR Column (Restart if new basis OR new day)
            // Logic: Reset merge if New Day OR (Same Day AND Diff Basis)
            if ($currentDate !== $lastDate) {
                 // New Day -> Always Restart, write basis
                 $table->addCell(3500, ['vMerge' => 'restart'])->addText($basisName);
            } else {
                 // Same Day
                 if ($basisName !== $lastBasis) {
                     // Different Basis -> Restart and write
                     $table->addCell(3500, ['vMerge' => 'restart'])->addText($basisName);
                 } else {
                     // Same Basis -> Continue merge
                     $table->addCell(3500, ['vMerge' => 'continue']);
                 }
            }

            // 4. URAIAN (Always unique)
            $table->addCell(5000)->addText($activity->description);

            // 5. HASIL (Always unique)
            $table->addCell(2500)->addText($activity->output_result ?? 'Terlaksana');

            // 6. PARAF (Empty)
            $table->addCell(1500)->addText('');

            $lastDate = $currentDate;
            $lastBasis = $basisName;
        }

        $template->setComplexBlock('table_block', $table);

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
            
        $template = new TemplateProcessor(storage_path('app/templates/jurnal_template.docx'));
        
        // Header
        $school = SchoolSetting::first();
        $template->setValue('school_name', $school->school_name ?? 'NAMA MADRASAH BELUM DISET');
        $template->setValue('school_address', $school->school_address ?? 'Alamat belum diset');
        $template->setValue('monthName', Carbon::createFromDate($year, $month, 1)->translatedFormat('F'));
        $template->setValue('year', $year);

        // Header Info
        $template->setValue('nama_guru', $user->name);
        $template->setValue('mapel', $user->subject ?? '-');
        $template->setValue('bulan', Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'));

        // Determine Semester & Year Info
        // July(7) to Dec(12) = Ganjil, Year = Current - Next
        // Jan(1) to June(6) = Genap, Year = Prev - Current
        if ($month >= 7) {
            $semester = 'GANJIL';
            $tahunAjaran = "{$year}/" . ($year + 1);
        } else {
            $semester = 'GENAP';
            $tahunAjaran = ($year - 1) . "/{$year}";
        }
        $template->setValue('semester', $semester);
        $template->setValue('tahun_ajaran', $tahunAjaran);

        // Signatures
        $template->setValue('signatureDate', Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y'));
        $template->setValue('user_name', $user->name);
        $template->setValue('user_nip', $user->nip);
        $template->setValue('headmaster_name', $school->headmaster_name ?? '.........................');
        $template->setValue('headmaster_nip', $school->headmaster_nip ?? '................');

        // Table Generation
        $table = new \PhpOffice\PhpWord\Element\Table([
            'borderSize' => 6, 
            'borderColor' => '000000', 
            'cellMargin' => 50,
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT, 
            'width' => 100 * 50 
        ]);

        // Table Header
        $table->addRow();
        $table->addCell(500, ['valign' => 'center'])->addText('NO.', ['bold' => true], ['alignment' => 'center']);
        
        $cellDate = $table->addCell(1400, ['valign' => 'center']);
        $cellDate->addText('HARI/', ['bold' => true], ['alignment' => 'center']);
        $cellDate->addText('TANGGAL', ['bold' => true], ['alignment' => 'center']);

        $table->addCell(900, ['valign' => 'center'])->addText('KELAS', ['bold' => true], ['alignment' => 'center']);
        
        $cellJam = $table->addCell(900, ['valign' => 'center']);
        $cellJam->addText('JAM', ['bold' => true], ['alignment' => 'center']);
        $cellJam->addText('KE-', ['bold' => true], ['alignment' => 'center']);

        $table->addCell(3800, ['valign' => 'center'])->addText('URAIAN PEKERJAAN', ['bold' => true], ['alignment' => 'center']);
        $table->addCell(2400, ['valign' => 'center'])->addText('KET.', ['bold' => true], ['alignment' => 'center']);

        $no = 1;
        $lastDate = null;

        foreach ($activities as $activity) {
            $currentDate = $activity->activity_date->format('Y-m-d');
            
            // Format Class Rooms
            $classNames = $activity->classRooms->count() > 0 
                ? $activity->classRooms->pluck('name')->join(', ') 
                : ($activity->class_name ?? '-');

            $jam = ($activity->period_start && $activity->period_end) 
                ? "{$activity->period_start} - {$activity->period_end}" 
                : '-';

            $materi = $activity->topic ?? '-';
            $ket = $activity->student_outcome ?? '';

            $table->addRow();

            // 1. NO (Merge logic)
            if ($currentDate !== $lastDate) {
                $table->addCell(500, ['vMerge' => 'restart', 'valign' => 'top'])->addText($no++);
            } else {
                $table->addCell(500, ['vMerge' => 'continue', 'valign' => 'top']);
            }

            // 2. DATE (Merge logic)
            if ($currentDate !== $lastDate) {
                // Use addText with break for "Hari, d Month Y" if needed or keep standard
                $table->addCell(1400, ['vMerge' => 'restart', 'valign' => 'top'])->addText($activity->activity_date->translatedFormat('l, j F Y'));
            } else {
                $table->addCell(1400, ['vMerge' => 'continue', 'valign' => 'top']);
            }

            // 3. KELAS
            $table->addCell(900, ['valign' => 'top'])->addText($classNames);

            // 4. JAM
            $table->addCell(900, ['valign' => 'top'])->addText($jam);

            // 5. URAIAN
            $table->addCell(3800, ['valign' => 'top'])->addText($materi);

            // 6. KET
            $table->addCell(2400, ['valign' => 'top'])->addText($ket);

            $lastDate = $currentDate;
        }

        $template->setComplexBlock('table_block', $table);

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
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->with('category')
            ->get()
            ->groupBy('category_id');

        // Automatic Routines for Labul (Summary) - Based on Days Present
        // Calculate based on distinct dates in the reportData
        // We need the raw list of activities first to get dates
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
        $otherDayCount = 0; // Tue-Fri

        foreach ($uniqueDates as $dateStr) {
            $d = Carbon::createFromFormat('Y-m-d', $dateStr);
            
            if ($d->isSunday() || $d->isSaturday()) continue;

            if ($d->isMonday()) {
                $mondayCount++;
            } else {
                $otherDayCount++;
            }
        }

        // Helper to create category and activity collection
        $addRoutineSummary = function($name, $count, $rhk_label) use ($reportData) {
            if ($count > 0) {
                // Create Virtual Category
                $cat = new ReportCategory();
                $cat->name = $name;
                $cat->rhk_label = $rhk_label;
                
                // Create Collection of Virtual Activities (only need count logic usually, but code uses .count())
                $acts = collect();
                for ($i=0; $i < $count; $i++) {
                    $a = new Activity();
                    $a->setRelation('category', $cat);
                    $a->evidence_link = '-';
                    $acts->push($a);
                }
                
                // Use a unique negative key to avoid collision with real IDs
                $reportData->put('routine_' . \Illuminate\Support\Str::slug($name), $acts);
            }
        };

        // Add Upacara (Mondays)
        $addRoutineSummary(
            'Melaksanakan Upacara Bendera / Apel Pagi', 
            $mondayCount, 
            'Terlaksananya kegiatan pembiasaan dan kedisiplinan siswa'
        );

        // Add Murottal (Tue-Fri)
        $addRoutineSummary(
            'Membimbing Murottal dan Sholat Dhuha Berjamaah', 
            $otherDayCount, 
            'Terlaksananya kegiatan keagamaan dan pembiasaan siswa'
        );

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
            $template->setValue("no#1", '');
            $template->setValue("rhk#1", '');
            $template->setValue("kegiatan#1", 'Belum ada kegiatan');
            $template->setValue("vol#1", '');
            $template->setValue("eviden#1", '');
        } else {
            $index = 0;
            foreach ($reportData as $categoryId => $activities) {
                $index++;
                $category = $activities->first()->category;
                $count = $activities->count();
                
                $evidenceLinks = $activities->pluck('evidence_link')->filter()->unique()->implode("\n");

                $template->setValue("no#{$index}", $index);
                $template->setValue("rhk#{$index}", $category->rhk_label);
                $template->setValue("kegiatan#{$index}", $category->name);
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
