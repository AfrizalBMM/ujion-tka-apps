<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetTemplateExporter
{
    public static function download(string $filename, array $headers, array $exampleRows = []): StreamedResponse
    {
        $callback = function () use ($headers, $exampleRows): void {
            echo self::buildSpreadsheetMl($headers, $exampleRows);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private static function buildSpreadsheetMl(array $headers, array $exampleRows): string
    {
        $rows = [$headers, ...$exampleRows];
        $xmlRows = '';

        foreach ($rows as $rowIndex => $row) {
            $xmlRows .= '<Row>';

            foreach (array_values($row) as $cell) {
                $styleId = $rowIndex === 0 ? 'Header' : 'Default';
                $xmlRows .= '<Cell ss:StyleID="' . $styleId . '"><Data ss:Type="String">' . self::escape((string) $cell) . '</Data></Cell>';
            }

            $xmlRows .= '</Row>';
        }

        return <<<XML
<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1E293B"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="Header">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#0F172A"/>
   <Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Template">
  <Table>
   {$xmlRows}
  </Table>
 </Worksheet>
</Workbook>
XML;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
