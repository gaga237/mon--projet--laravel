<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_online',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen_at'      => 'datetime',
        'is_online'         => 'boolean',
    ];

    protected $appends = ['avatar_url'];

    // ─── Relations ───────────────────────────────────────────

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)
                    ->withPivot(['role', 'last_read_at', 'is_muted'])
                    ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'created_by');
    }

    // ─── Accessors ───────────────────────────────────────────

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        // Génère un avatar avec les initiales
        $initials = collect(explode(' ', $this->name))
            ->map(fn ($word) => strtoupper($word[0]))
            ->take(2)
            ->join('');
        return 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=6366f1&color=fff&size=128';
    }
}

