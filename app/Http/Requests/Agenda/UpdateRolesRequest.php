<?php

namespace App\Http\Requests\Agenda;

use App\Models\Agenda;
use Illuminate\Foundation\Http\FormRequest;
use RuntimeException;

/**
 * Validates the dynamic assignment of a meeting leader and minute taker.
 *
 * This action used to validate inline via $request->validate(), which made it the
 * only mutating action in the controller not covered by a Form Request — against
 * AGENTS.md §3.1.
 *
 * The two column names are NEVER written out literally in this file. They are
 * derived from Agenda::$fillable, which is the single source of truth. Spelling
 * the column names in two places meant a single mistyped character in one copy
 * silently invalidated the other: validation would pass (no rule matched the
 * submitted field) and the write would be dropped as non-fillable, leaving the
 * role permanently null with no error anywhere.
 */
class UpdateRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agenda = $this->route('agenda');

        return $this->user()?->can('update', $agenda) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (self::roleFields() as $field) {
            $rules[$field] = ['nullable', 'integer', 'exists:users,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (self::roleFields() as $field) {
            // The leader column -> "Pimpinan Rapat". Derived, so the label can
            // never drift from the column it belongs to.
            $role = str($field)->beforeLast('_id')->headline();

            $attributes[$field] = $role.' Rapat';
        }

        return $attributes;
    }

    /**
     * The role assignment, ready to hand straight to Model::update().
     *
     * Both columns are nullable, so an omitted or blank value means "clear this
     * role" and is normalised to null. Keys come from roleFields(), keeping this
     * in lockstep with validation.
     *
     * @return array<string, int|null>
     */
    public function roles(): array
    {
        $roles = [];

        foreach (self::roleFields() as $field) {
            $value = $this->input($field);

            $roles[$field] = ($value === '' || $value === null) ? null : (int) $value;
        }

        return $roles;
    }

    /**
     * The agenda's assignable role columns, derived from the model.
     *
     * On the agendas table the only foreign keys are created_by plus the two role
     * assignments, so "ends in _id and is not created_by" identifies them without
     * ever spelling them out. Failing loudly beats silently validating nothing.
     *
     * @return list<string>
     */
    private static function roleFields(): array
    {
        $fields = array_values(array_filter(
            (new Agenda)->getFillable(),
            static fn (string $field): bool => str_ends_with($field, '_id') && $field !== 'created_by',
        ));

        if (count($fields) !== 2) {
            throw new RuntimeException(
                'UpdateRolesRequest expected exactly two assignable role columns on Agenda, found: '
                .implode(', ', $fields)
            );
        }

        return $fields;
    }
}
