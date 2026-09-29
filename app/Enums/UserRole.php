<?php

namespace App\Enums;

/**
 * Peran pengguna sistem.
 *
 * Typed rather than loose strings because `role` drives every authorization
 * decision in the application. With raw strings a typo such as 'adminstrator' in
 * a comparison fails silently and simply denies access; with an enum the
 * comparison is a type error the moment it is written.
 *
 * The backing values are the ones already stored in the `users.role` column
 * (an ENUM of the same three values), so no data migration is required.
 */
enum UserRole: string
{
    case Administrator = 'administrator';
    case Admin = 'admin';
    case Staff = 'staff';

    /**
     * Human-readable label for the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Admin => 'Admin Unit',
            self::Staff => 'Staff Pegawai',
        };
    }

    /**
     * Roles allowed to reach the admin-facing sections.
     */
    public function isBackOffice(): bool
    {
        return $this !== self::Staff;
    }

    /**
     * @return array<string, string> value => label, for building <select> options.
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
