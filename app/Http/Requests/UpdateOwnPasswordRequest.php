<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateOwnPasswordRequest extends FormRequest
{
    /**
     * The route is behind the auth middleware; users only change their own password.
     * The current password is checked in the earlier confirm step (SettingsController).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Reject the current password being reused as the new one.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var User $user */
                $user = $this->user();

                if (is_string($this->input('password')) && Hash::check($this->input('password'), $user->password)) {
                    $validator->errors()->add('password', 'Choose a password different from your current one.');
                }
            },
        ];
    }
}
