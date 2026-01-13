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

        // Group by Date for Routine Injection
        $groupedActivities = $activities->groupBy(function($item) {
            return $item->activity_date->format('Y-m-d');
        });

        // Flatten back to list with routines
        $processedActivities = collect();

        foreach ($groupedActivities as $date => $dailyActivities) {
            $dateObj = Carbon::createFromFormat('Y-m-d', $date);
            
            // Determine Routine
            $routineDescription = $dateObj->isMonday() 
                ? 'Upacara bendera / Apel pagi' 
                : 'Murottal Pagi dan Sholat Dhuha';
            
            // Create Virtual Routine Activity
            $routine = new \stdClass();
            $routine->activity_date = $dateObj;
            $routine->description = $routineDescription;
            $routine->reference_source = '-'; // As per request/image
            $routine->implementationBasis = null; // No relation
            $routine->output_result = 'Terlaksana';

            // Add Routine PREPENDED to the day
            $processedActivities->push($routine);

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
            ->whereNotNull('description')
            ->where('description', '!=', '')
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

        // Signatures
        $template->setValue('signatureDate', Carbon::createFromDate($year, $month, 1)->endOfMonth()->translatedFormat('j F Y'));
        $template->setValue('user_name', $user->name);
        $template->setValue('user_nip', $user->nip);
        $template->setValue('headmaster_name', $school->headmaster_name ?? '.........................');
        $template->setValue('headmaster_nip', $school->headmaster_nip ?? '................');

        // Rows
        $values = [];
        $no = 1;
        $lastDate = null;

        foreach ($activities as $activity) {
            $currentDate = $activity->activity_date->format('Y-m-d');
            
            // Format Class Rooms: join names
            $classNames = $activity->classRooms->count() > 0 
                ? $activity->classRooms->pluck('name')->join(', ') 
                : ($activity->class_name ?? '-');

            $jam = ($activity->period_start && $activity->period_end) 
                ? "{$activity->period_start} - {$activity->period_end}" 
                : '-';

            $row = [
                'kelas' => $classNames,
                'jam' => $jam,
                'materi' => $activity->topic ?? '-',
                'ketuntasan' => $activity->student_outcome ?? '',
            ];

            if ($currentDate !== $lastDate) {
                $row['no'] = $no++;
                $row['hari_tanggal'] = $activity->activity_date->translatedFormat('l, j F Y');
                $lastDate = $currentDate;
            } else {
                $row['no'] = '';
                $row['hari_tanggal'] = '';
            }

            $values[] = $row;
        }

        $template->cloneRowAndSetValues('no', $values);

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
                
                $evidenceLinks = $activities->pluck('evidence_link')->filter()->implode("\n");

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
