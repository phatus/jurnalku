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
        $section = $phpWord->addSection();

        $section->addText('JURNAL MENGAJAR BULAN ${monthName} ${year}', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        $section->addTextBreak(1);

        $tableStyle = [
            'borderSize' => 6, 
            'borderColor' => '000000', 
            'cellMargin' => 50
        ];
        $phpWord->addTableStyle('Jurnal Table', $tableStyle);
        $table = $section->addTable('Jurnal Table');

        // Header
        $table->addRow();
        $table->addCell(500)->addText('NO', ['bold' => true]);
        $table->addCell(2000)->addText('HARI/TANGGAL', ['bold' => true]);
        $table->addCell(1500)->addText('KELAS', ['bold' => true]);
        $table->addCell(1500)->addText('JAM', ['bold' => true]);
        $table->addCell(3000)->addText('MATERI', ['bold' => true]);
        $table->addCell(2000)->addText('KETUNTASAN', ['bold' => true]);

        // Row for Cloning
        $table->addRow();
        $table->addCell(500)->addText('${no}');
        $table->addCell(2000)->addText('${hari_tanggal}');
        $table->addCell(1500)->addText('${kelas}');
        $table->addCell(1500)->addText('${jam}');
        $table->addCell(3000)->addText('${materi}');
        $table->addCell(2000)->addText('${ketuntasan}');

        $section->addTextBreak(2);
        
        // Signatures
        $signatureTable = $section->addTable(['borderSize' => 0]);
        $signatureTable->addRow();
        $signatureTable->addCell(5000)->addText("Mengetahui,\nKepala Madrasah\n\n\n\n\${headmaster_name}\nNIP. \${headmaster_nip}");
        $signatureTable->addCell(5000)->addText("Pacitan, \${signatureDate}\nYang membuat,\n\n\n\n\${user_name}\nNIP. \${user_nip}");

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save(storage_path('app/templates/jurnal_template.docx'));
    }
}
