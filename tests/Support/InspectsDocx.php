<?php

namespace Tests\Support;

use App\Models\Agenda;
use App\Services\WordExportService;
use RuntimeException;
use ZipArchive;

/**
 * Helpers for asserting on the Word export.
 *
 * The Word export is a real .docx: a ZIP archive whose text lives in
 * word/document.xml and whose images live under word/media. Asserting on the
 * raw response body no longer works, because that body is binary.
 */
trait InspectsDocx
{
    /** @var list<string> */
    private array $docxTempFiles = [];

    /**
     * The document part of a .docx, as a string.
     */
    protected function docxXml(string $binary): string
    {
        $xml = $this->docxPart($binary, 'word/document.xml');

        if ($xml === null) {
            throw new RuntimeException('Paket .docx tidak memuat word/document.xml.');
        }

        return $xml;
    }

    /**
     * How many images the package carries.
     */
    protected function docxMediaCount(string $binary): int
    {
        $zip = $this->openDocx($binary);
        $count = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_starts_with((string) $zip->getNameIndex($i), 'word/media/')) {
                $count++;
            }
        }

        $zip->close();

        return $count;
    }

    /**
     * Every <w:tbl> in the document part, in order, as raw XML strings.
     *
     * Layout assertions need the table, not the whole document: "the signature
     * block is three rows wide" is a claim about one table and says nothing
     * about the other six.
     *
     * @return list<string>
     */
    protected function docxTables(string $binary): array
    {
        preg_match_all('#<w:tbl>.*?</w:tbl>#s', $this->docxXml($binary), $matches);

        return $matches[0];
    }

    /**
     * The column widths declared by a table's grid, in twips.
     *
     * @return list<int>
     */
    protected function docxGridColumns(string $tableXml): array
    {
        preg_match_all('#<w:gridCol w:w="(\d+)"#', $tableXml, $matches);

        return array_map('intval', $matches[1]);
    }

    /**
     * The cells of every row of a table, row by row.
     *
     * @return list<list<string>>
     */
    protected function docxRows(string $tableXml): array
    {
        preg_match_all('#<w:tr[ >].*?</w:tr>#s', $tableXml, $rows);

        return array_map(
            static function (string $row): array {
                preg_match_all('#<w:tc>.*?</w:tc>#s', $row, $cells);

                return $cells[0];
            },
            $rows[0]
        );
    }

    /**
     * The table that holds the signature block: the one carrying its opening
     * label, "Mengetahui,".
     */
    protected function docxTableContaining(string $binary, string $needle): string
    {
        foreach ($this->docxTables($binary) as $table) {
            if (str_contains($table, $needle)) {
                return $table;
            }
        }

        throw new RuntimeException('Tidak ada tabel yang memuat "'.$needle.'" pada paket .docx.');
    }

    private function docxPart(string $binary, string $name): ?string
    {
        $zip = $this->openDocx($binary);
        $contents = $zip->getFromName($name);
        $zip->close();

        return $contents === false ? null : $contents;
    }

    private function openDocx(string $binary): ZipArchive
    {
        $path = tempnam(sys_get_temp_dir(), 'docx_assert_');
        file_put_contents($path, $binary);

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            @unlink($path);

            throw new RuntimeException('Berkas .docx tidak dapat dibuka sebagai arsip ZIP.');
        }

        $this->docxTempFiles[] = $path;

        return $zip;
    }

    /**
     * The shared document body that both renderers are built from.
     *
     * Assertions about HTML markup (attributes such as border="1", colspan,
     * inline styles) belong here rather than on the .docx package, which stores
     * rendered formatting rather than the source markup.
     *
     * @param  array<string,mixed>  $config
     */
    protected function documentBodyHtml(Agenda $agenda, array $config = []): string
    {
        return app(WordExportService::class)
            ->generateDocumentContent($agenda, $config, 'plain');
    }

    /**
     * Remove the archives this test opened.
     */
    protected function docxCleanup(): void
    {
        foreach ($this->docxTempFiles as $path) {
            @unlink($path);
        }

        $this->docxTempFiles = [];
    }
}
