<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Note\IndexNoteRequest;
use App\Http\Requests\Note\StoreNoteRequest;
use App\Http\Requests\Note\UpdateNoteRequest;
use App\Http\Resources\NoteResource;
use App\Services\NoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NoteController extends Controller
{
    public function __construct(
        private readonly NoteService $notes,
    ) {}

    public function index(IndexNoteRequest $request): AnonymousResourceCollection
    {
        return NoteResource::collection($this->notes->list($request->user(), $request->validated()));
    }

    public function store(StoreNoteRequest $request): JsonResponse
    {
        $note = $this->notes->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Note created successfully.',
            'data' => [
                'note' => new NoteResource($note),
            ],
        ], 201);
    }

    public function show(Request $request, string $note): NoteResource
    {
        return new NoteResource($this->notes->get($request->user(), (int) $note));
    }

    public function update(UpdateNoteRequest $request, string $note): JsonResponse
    {
        $note = $this->notes->update($request->user(), (int) $note, $request->validated());

        return response()->json([
            'message' => 'Note updated successfully.',
            'data' => [
                'note' => new NoteResource($note),
            ],
        ]);
    }

    public function destroy(Request $request, string $note): JsonResponse
    {
        $this->notes->delete($request->user(), (int) $note);

        return response()->json([
            'message' => 'Note deleted successfully.',
        ]);
    }
}
