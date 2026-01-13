<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Table;

class TemplateSeeder extends Seeder
{
    public function run()
    {
        $this->createCatkinTemplate();
        $this->createJurnalTemplate();
    }

    protected function createCatkinTemplate()
    {
        $phpWord = new PhpWord();
        // Landscape Orientation
        $section = $phpWord->addSection(['orientation' => 'landscape']);

        $section->addText('CATATAN KINERJA BULAN ${monthName} ${year}', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        $section->addTextBreak(1);

        // Placeholder for Programmatic Table
        $section->addText('${table_block}');

        $section->addTextBreak(2);
        
        // Signatures (Wider spacing, No Borders, Center Alignment)
        $signatureStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0];
        $phpWord->addTableStyle('SigTable', $signatureStyle);
        $signatureTable = $section->addTable('SigTable');
        
        $signatureTable->addRow();
        
        // Left Signature
        $cell1 = $signatureTable->addCell(7500);
        $cell1->addText("Mengetahui,", [], ['alignment' => 'center']);
        $cell1->addText("Kepala Madrasah", [], ['alignment' => 'center']);
        $cell1->addTextBreak(3);
        $cell1->addText('${headmaster_name}', ['bold' => true, 'underline' => 'single'], ['alignment' => 'center']);
        $cell1->addText("NIP. \${headmaster_nip}", [], ['alignment' => 'center']);

        // Right Signature
        $cell2 = $signatureTable->addCell(7500);
        $cell2->addText("Pacitan, \${signatureDate}", [], ['alignment' => 'center']);
        $cell2->addText("Yang membuat,", [], ['alignment' => 'center']);
        $cell2->addTextBreak(3);
        $cell2->addText('${user_name}', ['bold' => true, 'underline' => 'single'], ['alignment' => 'center']);
        $cell2->addText("NIP. \${user_nip}", [], ['alignment' => 'center']);

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save(storage_path('app/templates/catkin_template.docx'));
    }

    protected function createJurnalTemplate()
    {
        $phpWord = new PhpWord();
        // Landscape Orientation
        $section = $phpWord->addSection(['orientation' => 'landscape']);

        // Header
        $section->addText('JURNAL MENGAJAR GURU', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        $section->addText('SEMESTER ${semester} TAHUN PELAJARAN ${tahun_ajaran}', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        $section->addTextBreak(1);

        // Info Block (Using a borderless table for alignment)
        $infoStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0];
        $phpWord->addTableStyle('InfoTable', $infoStyle);
        $infoTable = $section->addTable('InfoTable');

        // Row 1: Nama
        $row1 = $infoTable->addRow();
        $row1->addCell(3000)->addText('NAMA GURU');
        $row1->addCell(500)->addText(':');
        $row1->addCell(8000)->addText('${nama_guru}');

        // Row 2: Mapel
        $row2 = $infoTable->addRow();
        $row2->addCell(3000)->addText('MATA PELAJARAN');
        $row2->addCell(500)->addText(':');
        $row2->addCell(8000)->addText('${mapel}');

        // Row 3: Bulan
        $row3 = $infoTable->addRow();
        $row3->addCell(3000)->addText('BULAN');
        $row3->addCell(500)->addText(':');
        $row3->addCell(8000)->addText('${bulan}');

        $section->addTextBreak(1);

        // Placeholder for Programmatic Table
        $section->addText('${table_block}');

        $section->addTextBreak(2);
        
        // Signatures (Borderless, Center Alignment)
        $signatureStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0];
        $phpWord->addTableStyle('SigTableJurnal', $signatureStyle);
        $signatureTable = $section->addTable('SigTableJurnal');
        
        $signatureTable->addRow();
        
        // Left Signature
        $cell1 = $signatureTable->addCell(7500);
        $cell1->addText("Mengetahui,", [], ['alignment' => 'center']);
        $cell1->addText("Kepala Madrasah", [], ['alignment' => 'center']);
        $cell1->addTextBreak(3);
        $cell1->addText('${headmaster_name}', ['bold' => true, 'underline' => 'single'], ['alignment' => 'center']);
        $cell1->addText("NIP. \${headmaster_nip}", [], ['alignment' => 'center']);

        // Right Signature
        $cell2 = $signatureTable->addCell(7500);
        $cell2->addText("Pacitan, \${signatureDate}", [], ['alignment' => 'center']);
        $cell2->addText("Yang membuat,", [], ['alignment' => 'center']);
        $cell2->addTextBreak(3);
        $cell2->addText('${user_name}', ['bold' => true, 'underline' => 'single'], ['alignment' => 'center']);
        $cell2->addText("NIP. \${user_nip}", [], ['alignment' => 'center']);

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save(storage_path('app/templates/jurnal_template.docx'));
    }
}
