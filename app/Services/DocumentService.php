<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentService
{
    protected int $chunkSize = 1000; // Characters per chunk
    protected int $chunkOverlap = 200;

    /**
     * Upload and process a document.
     */
    public function upload(UploadedFile $file, int $userId): Document
    {
        $path = $file->store('documents', 'local');

        $document = Document::create([
            'user_id' => $userId,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'path' => $path,
            'size' => $file->getSize(),
            'status' => 'pending',
        ]);

        // Process asynchronously in production, synchronously here for simplicity
        $this->processDocument($document);

        return $document;
    }

    /**
     * Parse and chunk a document.
     */
    public function processDocument(Document $document): void
    {
        $document->update(['status' => 'processing']);

        try {
            $text = $this->extractText($document);
            $chunks = $this->chunkText($text);

            foreach ($chunks as $index => $content) {
                $chunk = DocumentChunk::create([
                    'document_id' => $document->id,
                    'chunk_index' => $index,
                    'content' => $content,
                    'metadata' => ['page' => null],
                ]);

                // Generate embedding
                $embedding = $this->generateEmbedding($content);
                if ($embedding) {
                    $chunk->setEmbeddingVector($embedding);
                    $chunk->save();
                }
            }

            $document->update([
                'status' => 'indexed',
                'chunk_count' => count($chunks),
            ]);
        } catch (\Exception $e) {
            $document->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Extract text based on file type.
     */
    protected function extractText(Document $document): string
    {
        $fullPath = Storage::disk('local')->path($document->path);
        $mimeType = $document->mime_type;

        if (str_contains($mimeType, 'pdf')) {
            return $this->extractFromPdf($fullPath);
        } elseif (str_contains($mimeType, 'spreadsheet') || str_contains($mimeType, 'excel') || str_contains($mimeType, 'csv')) {
            return $this->extractFromSpreadsheet($fullPath);
        } elseif ($mimeType === 'text/csv') {
            return $this->extractFromCsv($fullPath);
        } else {
            // Fallback: read as plain text
            return file_get_contents($fullPath);
        }
    }

    protected function extractFromPdf(string $path): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        return $pdf->getText();
    }

    protected function extractFromSpreadsheet(string $path): string
    {
        $spreadsheet = IOFactory::load($path);
        $text = '';

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $text .= "Sheet: " . $sheet->getTitle() . "\n";
            foreach ($sheet->toArray() as $row) {
                $text .= implode("\t", array_map(fn($c) => $c ?? '', $row)) . "\n";
            }
            $text .= "\n";
        }

        return $text;
    }

    protected function extractFromCsv(string $path): string
    {
        $content = file_get_contents($path);
        return $content;
    }

    /**
     * Split text into overlapping chunks.
     */
    protected function chunkText(string $text): array
    {
        $chunks = [];
        $length = strlen($text);
        $position = 0;

        while ($position < $length) {
            $chunk = substr($text, $position, $this->chunkSize);
            $chunks[] = trim($chunk);
            $position += $this->chunkSize - $this->chunkOverlap;
        }

        return array_filter($chunks);
    }

    /**
     * Generate embedding using OpenAI.
     */
    protected function generateEmbedding(string $text): ?array
    {
        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => substr($text, 0, 8000),
            ]);

            return $response->embeddings[0]->embedding;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Delete a document and its chunks.
     */
    public function delete(Document $document): void
    {
        Storage::disk('local')->delete($document->path);
        $document->delete();
    }
}
