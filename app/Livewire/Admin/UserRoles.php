<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
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

    #[Validate('required|integer|min:0|max:60')]
    public int $pontoMinInterval = 2;

    public function mount(): void
    {
        $this->newRole = User::ROLE_COLABORADOR;

        $config = Setting::value('ponto', []);
        $this->pontoMinInterval = (int) ($config['min_interval_minutes'] ?? 2);
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, message: $message);
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

        $this->notify('success', 'Perfil atualizado com sucesso.');
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

        $this->notify('success', 'Usuário criado com sucesso.');

        $this->reset(['newName', 'newEmail', 'newPassword']);
        $this->newRole = User::ROLE_COLABORADOR;
    }

    public function deactivateUser(int $userId): void
    {
        if ($userId === Auth::id()) {
            $this->notify('warning', 'Você não pode desativar a própria conta.');
            return;
        }

        $user = User::findOrFail($userId);

        if (! $user->isActive()) {
            return;
        }

        $user->update(['deactivated_at' => now('UTC')]);

        $this->notify('success', 'Usuário desativado.');
    }

    public function activateUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->isActive()) {
            return;
        }

        $user->update(['deactivated_at' => null]);

        $this->notify('success', 'Usuário reativado.');
    }

    public function updatePontoSettings(): void
    {
        $data = $this->validate([
            'pontoMinInterval' => ['required', 'integer', 'between:0,60'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'ponto'],
            ['value' => ['min_interval_minutes' => (int) $data['pontoMinInterval']]]
        );

        $this->notify('success', 'Intervalo mínimo atualizado com sucesso.');
    }
}
