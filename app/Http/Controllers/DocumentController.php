<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Display document management page.
     */
    public function index()
    {
        $documents = Document::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
        ]);
    }

    /**
     * Upload a new document.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200|mimes:pdf,xlsx,xls,csv,txt',
        ]);

        $document = $this->documentService->upload(
            $request->file('file'),
            Auth::id()
        );

        return back()->with('flash', [
            'message' => "Document '{$document->name}' uploaded and indexed!",
        ]);
    }

    /**
     * Delete a document.
     */
    public function destroy(Document $document)
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        $name = $document->name;
        $this->documentService->delete($document);

        return back()->with('flash', [
            'message' => "Document '{$name}' deleted.",
        ]);
    }
}
