<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'application',
        'description',
        'is_active',
        'last_used_at',
        'created_by',
        'permissions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'permissions' => 'array',
    ];

    protected $hidden = [
        'key', // Never expose the hashed key in API responses
    ];

    /**
     * Generate a new API key
     * 
     * @param string $name
     * @param string $application
     * @param string|null $description
     * @param int $createdBy
     * @return array ['raw_key' => string, 'api_key' => ApiKey]
     */
    public static function generate(string $name, string $application, ?string $description = null, int $createdBy): array
    {
        // Generate a random API key with prefix
        $rawKey = 'ak_' . Str::random(40);

        // Hash the key for storage
        $hashedKey = hash('sha256', $rawKey);

        // Create the API key record
        $apiKey = self::create([
            'name' => $name,
            'key' => $hashedKey,
            'application' => $application,
            'description' => $description,
            'is_active' => true,
            'created_by' => $createdBy,
        ]);

        return [
            'raw_key' => $rawKey,
            'api_key' => $apiKey,
        ];
    }

    /**
     * Validate an API key
     * 
     * @param string $rawKey
     * @return ApiKey|null
     */
    public static function validate(string $rawKey): ?ApiKey
    {
        $hashedKey = hash('sha256', $rawKey);

        return self::where('key', $hashedKey)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Mark the API key as used
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Activate the API key
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the API key
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Check if the API key has a specific permission
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->permissions) {
            return true; // No restrictions if permissions is null
        }

        return $this->permissions[$permission] ?? false;
    }

    /**
     * Relationship: User who created this API key
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Only active API keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Recently used API keys
     */
    public function scopeRecentlyUsed($query, int $days = 30)
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }
}
