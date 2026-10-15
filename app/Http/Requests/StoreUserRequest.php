<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    /**
     * The email's local part is the name's letters, plus a number for duplicates.
     * Local parts may be at most 64 characters (RFC 5321); 60 leaves room for
     * a suffix of up to 4 digits.
     */
    public const MAX_NAME_LETTERS = 60;

    /**
     * Only admins may create users (UserPolicy::create).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * Only the name, role name and department name are accepted. The email and password are
     * generated on the server, so anything else the client sends is ignored.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Letters, spaces and full stops only, with at least one letter
            // so the generated email is never empty.
            'name' => ['required', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z])[A-Za-z. ]+$/'],
            'role' => ['required', 'string', Rule::in([Role::EMPLOYEE, Role::MANAGER, Role::ADMIN])],
            'department' => ['required', 'string', Rule::in(Department::ALL)],
        ];
    }

    /**
     * Keep the generated email short enough to be valid and to fit the column.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $name = $this->input('name');

                if (is_string($name) && strlen((string) preg_replace('/[^A-Za-z]/', '', $name)) > self::MAX_NAME_LETTERS) {
                    $validator->errors()->add('name', 'The name may contain at most '.self::MAX_NAME_LETTERS.' letters.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'The name may only contain letters, spaces and full stops.',
        ];
    }
}
