<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use App\Notifications\ClinicPasswordResetNotification;
use Illuminate\Support\Facades\Storage;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'calendar_color',
        'profile_picture',
        'notification_preferences',
        'working_hours',
        'requires_password_change',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'notification_preferences' => 'array',
            'working_hours' => 'array',
            'requires_password_change' => 'boolean',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['profile_picture_url'];

    /**
     * Get the profile picture URL.
     *
     * @return string
     */
    public function getProfilePictureUrlAttribute(): string
    {
        if (!$this->profile_picture) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
        }

        if (str_starts_with($this->profile_picture, 'data:image')) {
            return $this->profile_picture;
        }

        if (tenant()) {
            return route('tenant.storage', ['path' => ltrim($this->profile_picture, '/')]);
        }

        $path = ltrim($this->profile_picture, '/');

        // Central context: if a historical image was written into tenant storage,
        // migrate it back to central public storage so /storage URLs work again.
        if (!Storage::disk('public')->exists($path)) {
            $candidate = $this->findImageInTenantStorage($path);

            if ($candidate) {
                $target = storage_path('app/public/' . $path);
                $targetDir = dirname($target);

                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }

                @copy($candidate, $target);
            }
        }

        if (!Storage::disk('public')->exists($path)) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
        }

        return asset('storage/' . $path);
    }

    private function findImageInTenantStorage(string $relativePath): ?string
    {
        $tenantPattern = storage_path('tenant*/app/public/' . $relativePath);
        $matches = glob($tenantPattern) ?: [];

        foreach ($matches as $match) {
            if (is_file($match) && is_readable($match)) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ClinicPasswordResetNotification($token));
    }
}
