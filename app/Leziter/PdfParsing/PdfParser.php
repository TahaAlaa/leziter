<?php

namespace App\Leziter\PdfParsing;

use Exception;

class PdfParser
{
    /**
     * @throws Exception
     */
    public function getText($pdfPath): false|string
    {
        $outputPath = tempnam(sys_get_temp_dir(), 'pdf_text_');
        $command = "pdftotext -layout -nopgbrk '$pdfPath' '$outputPath'";
        exec($command, $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Failed to extract text from PDF. Error code: $returnVar");
        }

        $text = file_get_contents($outputPath);
        unlink($outputPath);

        return $text;
    }
}
