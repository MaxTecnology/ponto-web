<div class="space-y-6">
    <section class="app-card p-6">
        <h2 class="app-section-heading">Novo Usuário</h2>
        <p class="app-section-subtitle">Crie usuários adicionais definindo o perfil de acesso.</p>

        <form wire:submit.prevent="createUser" class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="space-y-1">
                <label for="new-name" class="app-label">Nome</label>
                <input id="new-name" type="text" wire:model.defer="newName" class="app-input" required>
                @error('newName')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-1">
                <label for="new-email" class="app-label">E-mail</label>
                <input id="new-email" type="email" wire:model.defer="newEmail" class="app-input" required>
                @error('newEmail')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-1">
                <label for="new-password" class="app-label">Senha temporária</label>
                <input id="new-password" type="password" wire:model.defer="newPassword" class="app-input" required>
                @error('newPassword')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-1">
                <label for="new-role" class="app-label">Perfil</label>
                <select id="new-role" wire:model.defer="newRole" class="app-input">
                    <option value="colaborador">colaborador</option>
                    <option value="rh_manager">rh_manager</option>
                    <option value="admin">admin</option>
                </select>
                @error('newRole')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="app-button">Criar usuário</button>
            </div>
        </form>
    </section>

    <section class="app-card p-6 space-y-4">
        <div>
            <h2 class="app-section-heading text-base">Usuários existentes</h2>
            <p class="app-section-subtitle">Apenas administradores podem alterar perfis ou desativar contas.</p>
        </div>

        <div class="app-table-wrapper">
            <table class="app-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">E-mail</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Perfil</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Desativado em</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[rgb(var(--color-muted))]">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgb(var(--color-border))]/50">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-4 py-3 text-sm text-[rgb(var(--color-text))]">
                                <div class="font-semibold">{{ $user->name }}</div>
                                <div class="text-xs text-[rgb(var(--color-muted))]">#{{ $user->id }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-[rgb(var(--color-muted))]">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <span class="app-badge app-badge-neutral uppercase">{{ $user->role }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($user->isActive())
                                    <span class="app-badge app-badge-success uppercase">Ativo</span>
                                @else
                                    <span class="app-badge app-badge-warning uppercase">Desativado</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-[rgb(var(--color-muted))]">{{ $user->deactivated_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="setRole({{ $user->id }}, '{{ $user->role }}')" class="app-button-ghost text-xs">Editar</button>
                                    @if ($user->isActive())
                                        <button wire:click="deactivateUser({{ $user->id }})" class="app-button-danger text-xs">Desativar</button>
                                    @else
                                        <button wire:click="activateUser({{ $user->id }})" class="app-button-success text-xs">Reativar</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($editingUserId)
        <section class="app-card p-6">
            <h3 class="app-section-heading text-base">Editar Usuário</h3>
            <form wire:submit.prevent="update" class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="space-y-1">
                    <label for="edit-name" class="app-label">Nome</label>
                    <input id="edit-name" type="text" wire:model.defer="editingName" class="app-input" required>
                    @error('editingName')
                        <p class="text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-1">
                    <label for="edit-email" class="app-label">E-mail</label>
                    <input id="edit-email" type="email" wire:model.defer="editingEmail" class="app-input" required>
                    @error('editingEmail')
                        <p class="text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-1">
                    <label for="edit-role" class="app-label">Perfil</label>
                    <select id="edit-role" wire:model.defer="role" class="app-input">
                        <option value="colaborador">colaborador</option>
                        <option value="rh_manager">rh_manager</option>
                        <option value="admin">admin</option>
                    </select>
                    @error('role')
                        <p class="text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2 flex justify-end gap-2">
                    <button type="button" wire:click="$set('editingUserId', null)" class="app-button-ghost">Cancelar</button>
                    <button type="submit" class="app-button">Salvar alterações</button>
                </div>
            </form>
        </section>
    @endif
</div>
