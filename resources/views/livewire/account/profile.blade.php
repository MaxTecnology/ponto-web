<div class="space-y-6">
    @if (session('status'))
        <div class="app-alert-success">
            {{ session('status') }}
        </div>
    @endif

    <section class="app-card p-6 space-y-4 max-w-2xl">
        <div>
            <h2 class="app-section-heading">Meu perfil</h2>
            <p class="app-section-subtitle">Atualize seu nome para refletir corretamente nos relatórios.</p>
        </div>

        <form wire:submit.prevent="updateProfile" class="space-y-4">
            <div class="space-y-1">
                <label for="profile-name" class="app-label">Nome</label>
                <input id="profile-name" type="text" wire:model.defer="name" class="app-input" required>
                @error('name')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="app-button">Salvar nome</button>
            </div>
        </form>
    </section>

    <section class="app-card p-6 space-y-4 max-w-2xl">
        <div>
            <h2 class="app-section-heading">Alterar senha</h2>
            <p class="app-section-subtitle">Por segurança, informe a senha atual antes de definir uma nova.</p>
        </div>

        <form wire:submit.prevent="updatePassword" class="space-y-4">
            <div class="space-y-1">
                <label for="current-password" class="app-label">Senha atual</label>
                <input id="current-password" type="password" wire:model.defer="currentPassword" class="app-input" required>
                @error('currentPassword')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-1">
                <label for="new-password" class="app-label">Nova senha</label>
                <input id="new-password" type="password" wire:model.defer="newPassword" class="app-input" required>
                @error('newPassword')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-1">
                <label for="new-password-confirm" class="app-label">Confirmar nova senha</label>
                <input id="new-password-confirm" type="password" wire:model.defer="newPasswordConfirmation" class="app-input" required>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="app-button">Alterar senha</button>
            </div>
        </form>
    </section>
</div>
