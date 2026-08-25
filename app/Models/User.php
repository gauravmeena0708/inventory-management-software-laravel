<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'role' => UserRole::class,
        ];
    }

    /**
     * Get the options for activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    /**
     * Check if the user has one of the specified roles.
     */
    public function hasRole(UserRole|string ...$roles): bool
    {
        $current = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;

        foreach ($roles as $role) {
            $value = $role instanceof UserRole ? $role->value : (string) $role;
            if ($current === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user is an Administrator.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::ADMIN);
    }

    /**
     * Check if the user is an Inventory Manager.
     */
    public function isInventoryManager(): bool
    {
        return $this->hasRole(UserRole::INVENTORY_MANAGER);
    }

    /**
     * Check if the user is a Stock Operator.
     */
    public function isStockOperator(): bool
    {
        return $this->hasRole(UserRole::STOCK_OPERATOR);
    }

    /**
     * Check if the user is a Finance Operator.
     */
    public function isFinanceOperator(): bool
    {
        return $this->hasRole(UserRole::FINANCE_OPERATOR);
    }

    /**
     * Check if the user is a Viewer.
     */
    public function isViewer(): bool
    {
        return $this->hasRole(UserRole::VIEWER);
    }

    /**
     * Check if the user is an Auditor.
     */
    public function isAuditor(): bool
    {
        return $this->hasRole(UserRole::AUDITOR);
    }

    /**
     * Check if user can manage users and user roles.
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can create, update, or decommission assets.
     */
    public function canManageInventory(): bool
    {
        return $this->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Check if user can assign and return assets.
     */
    public function canAssignAssets(): bool
    {
        return $this->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Check if user can post stock entries (purchase/issue).
     */
    public function canPostStockEntries(): bool
    {
        return $this->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR);
    }

    /**
     * Check if user can manage vendor agreements.
     */
    public function canManageAgreements(): bool
    {
        return $this->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR);
    }

    /**
     * Check if user can complete or cancel scheduled payments.
     */
    public function canManagePayments(): bool
    {
        return $this->hasRole(UserRole::ADMIN, UserRole::FINANCE_OPERATOR);
    }

    /**
     * Check if user can view ordinary inventory records.
     */
    public function canViewInventory(): bool
    {
        return $this->hasRole(
            UserRole::ADMIN,
            UserRole::INVENTORY_MANAGER,
            UserRole::STOCK_OPERATOR,
            UserRole::FINANCE_OPERATOR,
            UserRole::VIEWER,
            UserRole::AUDITOR
        );
    }

    /**
     * Check if user can export inventory or financial data.
     */
    public function canExportData(): bool
    {
        return $this->hasRole(
            UserRole::ADMIN,
            UserRole::INVENTORY_MANAGER,
            UserRole::STOCK_OPERATOR,
            UserRole::FINANCE_OPERATOR,
            UserRole::AUDITOR
        );
    }

    /**
     * Check if user can view sensitive personnel data.
     */
    public function canViewSensitivePersonnel(): bool
    {
        return $this->hasRole(UserRole::ADMIN, UserRole::AUDITOR);
    }

    /**
     * Check if user can view audit log history.
     */
    public function canViewAuditHistory(): bool
    {
        return $this->hasRole(
            UserRole::ADMIN,
            UserRole::INVENTORY_MANAGER,
            UserRole::STOCK_OPERATOR,
            UserRole::FINANCE_OPERATOR,
            UserRole::AUDITOR
        );
    }

    /**
     * The organizational units this user belongs to.
     */
    public function organizationalUnits(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(OrganizationalUnit::class)
            ->withPivot(['read_scope', 'write_scope', 'valid_from', 'valid_until'])
            ->withTimestamps();
    }

    /**
     * The active organizational units this user belongs to.
     */
    public function activeOrganizationalUnits(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        $now = now();
        return $this->organizationalUnits()
            ->where(function ($query) use ($now) {
                $query->whereNull('organizational_unit_user.valid_from')
                      ->orWhere('organizational_unit_user.valid_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('organizational_unit_user.valid_until')
                      ->orWhere('organizational_unit_user.valid_until', '>=', $now);
            });
    }

    /**
     * The default organizational unit for the user context.
     */
    public function defaultOrganizationalUnit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'default_organizational_unit_id');
    }
}
