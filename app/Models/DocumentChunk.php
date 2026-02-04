<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'embedding',
        'metadata',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the embedding as an array of floats.
     */
    public function getEmbeddingVector(): ?array
    {
        if (!$this->embedding) {
            return null;
        }
        return unpack('f*', $this->embedding);
    }

    /**
     * Set embedding from array of floats.
     */
    public function setEmbeddingVector(array $vector): void
    {
        $packed = '';
        foreach ($vector as $val) {
            $packed .= pack('f', $val);
        }
        $this->embedding = $packed;
    }
}
