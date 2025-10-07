<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

class UserRoles extends Component
{
    #[Validate('required|exists:users,id')]
    public ?int $editingUserId = null;

    #[Validate('required|string|in:colaborador,rh_manager,admin')]
    public string $role = User::ROLE_COLABORADOR;

    public string $editingName = '';

    public string $editingEmail = '';

    public string $newName = '';

    public string $newEmail = '';

    public string $newPassword = '';

    public string $newRole = User::ROLE_COLABORADOR;

    public function mount(): void
    {
        $this->newRole = User::ROLE_COLABORADOR;
    }

    public function render()
    {
        $users = User::query()->orderBy('name')->get();

        return view('livewire.admin.user-roles', [
            'users' => $users,
        ]);
    }

    public function setRole(int $userId, string $role): void
    {
        $this->editingUserId = $userId;
        $this->role = $role;
        $user = User::findOrFail($userId);
        $this->editingName = $user->name;
        $this->editingEmail = $user->email;
    }

    public function update(): void
    {
        if (! $this->editingUserId) {
            return;
        }

        $validated = $this->validate([
            'editingUserId' => ['required', 'exists:users,id'],
            'editingName' => ['required', 'string', 'min:3', 'max:255'],
            'editingEmail' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'role' => ['required', Rule::in([User::ROLE_COLABORADOR, User::ROLE_RH_MANAGER, User::ROLE_ADMIN])],
        ]);

        $user = User::findOrFail($this->editingUserId);
        $user->update([
            'name' => $validated['editingName'],
            'email' => $validated['editingEmail'],
            'role' => $validated['role'],
        ]);

        session()->flash('status', 'Perfil atualizado com sucesso.');
        $this->reset(['editingUserId', 'editingName', 'editingEmail']);
    }

    public function createUser(): void
    {
        $data = $this->validate([
            'newName' => ['required', 'string', 'min:3', 'max:255'],
            'newEmail' => ['required', 'email', Rule::unique('users', 'email')],
            'newPassword' => ['required', 'string', 'min:8'],
            'newRole' => ['required', Rule::in([User::ROLE_COLABORADOR, User::ROLE_RH_MANAGER, User::ROLE_ADMIN])],
        ]);

        User::create([
            'name' => $data['newName'],
            'email' => $data['newEmail'],
            'password' => Hash::make($data['newPassword']),
            'role' => $data['newRole'],
        ]);

        session()->flash('status', 'Usuário criado com sucesso.');

        $this->reset(['newName', 'newEmail', 'newPassword']);
        $this->newRole = User::ROLE_COLABORADOR;
    }

    public function deactivateUser(int $userId): void
    {
        if ($userId === Auth::id()) {
            session()->flash('status', 'Você não pode desativar a própria conta.');
            return;
        }

        $user = User::findOrFail($userId);

        if (! $user->isActive()) {
            return;
        }

        $user->update(['deactivated_at' => now('UTC')]);

        session()->flash('status', 'Usuário desativado.');
    }

    public function activateUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->isActive()) {
            return;
        }

        $user->update(['deactivated_at' => null]);

        session()->flash('status', 'Usuário reativado.');
    }
}
