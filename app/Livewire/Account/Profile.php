<?php

namespace App\Livewire\Account;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Profile extends Component
{
    public string $name;

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user?->name ?? '';
    }

    public function render()
    {
        return view('livewire.account.profile');
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $user->update(['name' => $validated['name']]);

        session()->flash('status', 'Perfil atualizado com sucesso.');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->validate([
            'currentPassword' => ['required'],
            'newPassword' => ['required', 'string', Password::defaults()],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ], [
            'newPasswordConfirmation.same' => 'A confirmação da nova senha não confere.',
        ]);

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Senha atual incorreta.');
            return;
        }

        $user->forceFill([
            'password' => Hash::make($this->newPassword),
        ])->save();

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);

        session()->flash('status', 'Senha alterada com sucesso.');
    }
}
