<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Conversation $conversation, Request $request)
    {
        if (!$conversation->users->contains($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = $conversation->messages()
            ->with(['sender', 'replyTo'])
            ->latest() // Getting latest messages first
            ->paginate(50);

        // Mark as read
        $conversation->users()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now()
        ]);

        return response()->json($messages);
    }

    public function store(Request $request, Conversation $conversation)
    {
        if (!$conversation->users->contains($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'body' => 'required_without:file|string|nullable',
            'file' => 'nullable|file|max:10240', // 10MB max
            'reply_to_id' => 'nullable|exists:messages,id'
        ]);

        $filePath = null;
        $fileName = null;
        $type = 'text';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('attachments', 'public');
            
            $mime = $file->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                $type = 'image';
            } elseif (str_starts_with($mime, 'audio/')) {
                $type = 'audio';
            } else {
                $type = 'file';
            }
        }

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
            'type' => $type,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'reply_to_id' => $request->reply_to_id,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json($message->load(['sender', 'replyTo']), 201);
    }
}
