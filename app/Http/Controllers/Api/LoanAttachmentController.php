<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiResponseTrait;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanAttachmentController extends Controller
{
    use ApiResponseTrait;

    public function store(Request $request, Loan $loan)
    {
        if ($loan->user_id !== $request->user()->id) {
            return $this->error('No tienes permisos para este recurso.', 403);
        }

        $data = $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // 5MB cada archivo
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'string', 'max:50'],
        ], [
            'files.required' => 'Debes adjuntar al menos un archivo.',
            'files.*.mimes' => 'Tipo de archivo no permitido. Usa JPG, PNG o PDF.',
            'files.*.max' => 'Cada archivo no debe superar 5MB.',
        ]);

        $attachments = [];

        foreach ($data['files'] as $i => $file) {
            $path = $file->store("loans/{$loan->id}", 'local');

            $att = $loan->attachments()->create([
                'path' => $path,
                'mime' => $file->getMimeType(),
                'size' => (int) round($file->getSize() / 1024),
                'category' => $data['categories'][$i] ?? null,
                'meta' => ['original' => $file->getClientOriginalName()],
            ]);

            $attachments[] = [
                'id' => $att->id,
                'path' => $att->path,
                'category' => $att->category,
                'mime' => $att->mime,
            ];
        }

        return $this->success($attachments, 'Adjuntos subidos correctamente', 201);
    }

}
