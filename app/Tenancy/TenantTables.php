<?php

namespace App\Tenancy;

/**
 * The tables that carry a `centre_id`.
 *
 * One list, used by the migrations, the audit command and the isolation tests,
 * so "is this table scoped?" has exactly one answer and adding a table to the
 * schema without deciding is not possible to do quietly.
 */
final class TenantTables
{
    /** No owner column of any kind before tenancy. */
    public const UNOWNED = [
        'groups', 'rooms', 'finances', 'sms_templates',
    ];

    /**
     * Reachable only through a missing foreign key or a name string, so they
     * cannot be scoped by derivation.
     */
    public const WEAKLY_LINKED = [
        'lesson_and_histories', 'history_payments', 'student_information',
        'assessments', 'dept_students', 'active_students', 'certificates',
    ];

    /**
     * Reachable through a real foreign key, but denormalised anyway: a global
     * scope only filters the model's own table, and the raw query builder is
     * not scoped at all.
     */
    public const DENORMALISED = [
        'group_user', 'group_teachers', 'attendances',
        'homeworks', 'homework_submissions', 'lesson_skill_grades',
    ];

    /**
     * Deliberately NOT scoped:
     *  - `users`     — a person belongs to centres many-to-many;
     *  - `centres`, `centre_user` — the platform layer itself;
     *  - `parent_student` — "this person is the guardian of that one" is a fact
     *    about people; guardian lists are scoped through the student query,
     *    which is already role-scoped.
     */
    public const PLATFORM = [
        'users', 'centres', 'centre_user', 'parent_student',
    ];

    /** @return array<int, string> */
    public static function all(): array
    {
        return array_merge(self::UNOWNED, self::WEAKLY_LINKED, self::DENORMALISED);
    }

    /**
     * Child table => the parent whose centre it must agree with.
     *
     * Used by `centres:audit` to prove nothing has drifted.
     *
     * @return array<string, array{0: string, 1: string}>  [parent table, local key]
     */
    public static function parents(): array
    {
        return [
            'group_user'           => ['groups', 'group_id'],
            'group_teachers'       => ['groups', 'group_id'],
            'attendances'          => ['groups', 'group_id'],
            'homeworks'            => ['groups', 'group_id'],
            'lesson_skill_grades'  => ['groups', 'group_id'],
            'homework_submissions' => ['homeworks', 'homework_id'],
        ];
    }
}
