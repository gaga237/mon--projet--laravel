<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $conversations = $request->user()
            ->conversations()
            ->with(['users', 'lastMessage'])
            ->orderByDesc('last_message_at')
            ->get();
            
        // Append unread count for each conversation
        $conversations->each(function($conv) use ($request) {
            $conv->setAttribute('unread_count', $conv->unreadCount($request->user()->id));
        });

        return response()->json($conversations);
    }

    public function show(Conversation $conversation, Request $request)
    {
        // Check if user belongs to conversation
        if (!$conversation->users->contains($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        
        // Mark as read
        $conversation->users()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now()
        ]);

        return response()->json($conversation->load(['users']));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required_without:is_group|exists:users,id',
            'is_group' => 'boolean',
            'name' => 'required_if:is_group,true|string|max:255',
            'users' => 'required_if:is_group,true|array',
            'users.*' => 'exists:users,id'
        ]);

        $currentUser = $request->user();

        if ($request->is_group) {
            $conversation = Conversation::create([
                'type' => 'group',
                'name' => $request->name,
                'created_by' => $currentUser->id,
                'last_message_at' => now(),
            ]);

            $userIds = collect($request->users)->push($currentUser->id)->unique();
            $conversation->users()->attach($userIds, ['role' => 'member']);
            
            // Set creator as admin
            $conversation->users()->updateExistingPivot($currentUser->id, ['role' => 'admin']);
        } else {
            // Check if private conversation already exists
            $existing = $currentUser->conversations()
                ->where('type', 'private')
                ->whereHas('users', function ($query) use ($request) {
                    $query->where('users.id', $request->user_id);
                })->first();

            if ($existing) {
                return response()->json($existing->load('users'));
            }

            $conversation = Conversation::create([
                'type' => 'private',
                'created_by' => $currentUser->id,
                'last_message_at' => now(),
            ]);

            $conversation->users()->attach([$currentUser->id, $request->user_id]);
        }

        return response()->json($conversation->load('users'), 201);
    }
}
