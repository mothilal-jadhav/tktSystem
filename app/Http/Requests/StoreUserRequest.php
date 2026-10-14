<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Only admins may create users (UserPolicy::create).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * Only the name is accepted. The email and password are generated
     * on the server, so anything else the client sends is ignored.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Letters, spaces and full stops only, with at least one letter
            // so the generated email is never empty.
            'name' => ['required', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z])[A-Za-z. ]+$/'],
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
