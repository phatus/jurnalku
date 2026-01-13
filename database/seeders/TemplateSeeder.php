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

        $tableStyle = [
            'borderSize' => 6, 
            'borderColor' => '000000', 
            'cellMargin' => 50
        ];
        $phpWord->addTableStyle('Catkin Table', $tableStyle);
        $table = $section->addTable('Catkin Table');

        // Header - Adjusted widths for Landscape (approx 15000 twips total)
        $table->addRow();
        $table->addCell(700)->addText('NO', ['bold' => true]);
        $table->addCell(2500)->addText('HARI/TANGGAL', ['bold' => true]);
        $table->addCell(4000)->addText('DASAR PELAKSANAAN', ['bold' => true]);
        $table->addCell(5500)->addText('URAIAN PEKERJAAN', ['bold' => true]);
        $table->addCell(2500)->addText('HASIL', ['bold' => true]);

        // Row for Cloning
        $table->addRow();
        $table->addCell(700)->addText('${no}');
        $table->addCell(2500)->addText('${hari_tanggal}');
        $table->addCell(4000)->addText('${dasar}');
        $table->addCell(5500)->addText('${uraian}');
        $table->addCell(2500)->addText('${output}');

        $section->addTextBreak(2);
        
        // Signatures (Wider spacing)
        $signatureTable = $section->addTable(['borderSize' => 0]);
        $signatureTable->addRow();
        $signatureTable->addCell(7000)->addText("Mengetahui,\nKepala Madrasah\n\n\n\n\${headmaster_name}\nNIP. \${headmaster_nip}");
        $signatureTable->addCell(7000)->addText("Pacitan, \${signatureDate}\nYang membuat,\n\n\n\n\${user_name}\nNIP. \${user_nip}");

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
