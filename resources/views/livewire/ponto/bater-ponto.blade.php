<div class="space-y-6" data-component-id="{{ $this->getId() }}">
    <section class="app-card p-6">
        <h2 class="text-lg font-semibold text-[rgb(var(--color-text))]">Status do Dia</h2>
        <p class="mt-2 text-sm text-[rgb(var(--color-muted))]">
            @if ($lastPunch)
                @php
                    $tipoLabel = $tipoLabels[$lastPunch->type] ?? $lastPunch->type;
                    $horaLocal = \App\Support\Timezone::toLocal($lastPunch->ts_server);
                @endphp
                Última batida: <span class="font-medium text-[rgb(var(--color-text))]">{{ $tipoLabel }}</span>
                às <span class="font-medium text-[rgb(var(--color-text))]">{{ $horaLocal?->format('H:i') }}</span>
            @else
                Nenhuma batida registrada hoje.
            @endif
        </p>
    </section>

    <section class="app-card p-6">
        <h2 class="text-lg font-semibold text-[rgb(var(--color-text))]">Registrar Batida</h2>
        <form id="punch-form" class="mt-4 space-y-5">
            <div>
                <label for="type" class="app-label">Tipo</label>
                <select id="type" wire:model.live="type" class="app-input">
                    @foreach ($tipoLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="observacao" class="app-label">Observação (opcional)</label>
                <textarea id="observacao" wire:model.lazy="observacao" maxlength="255" class="app-input h-28" placeholder="Ex.: reunião externa"></textarea>
                @error('observacao')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-center justify-end">
                <button type="submit" id="punch-submit" class="app-button">Bater Ponto</button>
            </div>
        </form>
    </section>

    <dialog id="geo-consent-dialog" class="max-w-lg rounded-lg p-0 shadow-xl">
        <form method="dialog" class="space-y-4 p-6">
            <h3 class="text-lg font-semibold text-slate-800">Consentimento de Geolocalização</h3>
            <p class="text-sm text-slate-600">
                Ao continuar, você autoriza a coleta de localização aproximada e informações do dispositivo para comprovar local de trabalho remoto e prevenir fraudes. Esses dados são acessíveis apenas ao RH/Admin e armazenados por até 12 meses (geolocalização), enquanto registros de ponto ficam por até 5 anos conforme política interna. Você pode negar; o registro será feito sem localização e marcado para revisão.
            </p>
            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                <button type="button" data-action="deny" class="app-button-secondary w-full sm:w-auto">Negar</button>
                <button type="button" data-action="accept" class="app-button w-full sm:w-auto">Concordo</button>
            </div>
        </form>
    </dialog>
</div>

@push('scripts')
    <script>
        console.log('BaterPonto script carregado');

        const initPunchForm = (container) => {
            if (!container) {
                console.warn('BaterPonto: container não encontrado');
                return;
            }

            const componentId = container.dataset.componentId;
            const form = container.querySelector('#punch-form');
            const submitButton = container.querySelector('#punch-submit');
            const consentDialog = container.querySelector('#geo-consent-dialog');
            const consentKey = 'ponto_geo_consent';

            if (!form || !submitButton || !consentDialog) {
                console.warn('BaterPonto: elementos obrigatórios não encontrados', { form: !!form, submitButton: !!submitButton, consentDialog: !!consentDialog });
                return;
            }

            if (form.dataset.bound === 'true') {
                return;
            }
            form.dataset.bound = 'true';

            const collectGeo = async (enabled) => {
                if (!enabled || !navigator.geolocation) {
                    return null;
                }

                return new Promise((resolve) => {
                    let resolved = false;
                    const timer = setTimeout(() => {
                        if (!resolved) {
                            resolved = true;
                            resolve(null);
                        }
                    }, 7000);

                    navigator.geolocation.getCurrentPosition((position) => {
                        if (resolved) return;
                        resolved = true;
                        clearTimeout(timer);
                        resolve({
                            lat: position.coords.latitude,
                            lon: position.coords.longitude,
                            accuracy_m: position.coords.accuracy,
                        });
                    }, () => {
                        if (resolved) return;
                        resolved = true;
                        clearTimeout(timer);
                        resolve(null);
                    }, { enableHighAccuracy: true, timeout: 7000 });
                });
            };

            const hashFingerprint = async (fingerprintSource) => {
                if (!window.crypto?.subtle) {
                    return null;
                }

                const encoder = new TextEncoder();
                const data = encoder.encode(fingerprintSource);
                const hashBuffer = await crypto.subtle.digest('SHA-256', data);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');
            };

            const parseUserAgent = () => {
                const raw = navigator.userAgent;
                const platform = navigator.userAgentData?.platform || navigator.platform || null;
                const brands = navigator.userAgentData?.brands?.map((brand) => brand.brand + '/' + brand.version) || [];

                let browser = null;
                let os = null;
                let device = null;

                if (/chrome|chromium|crios/i.test(raw)) {
                    browser = 'Chrome';
                } else if (/firefox|fxios/i.test(raw)) {
                    browser = 'Firefox';
                } else if (/safari/i.test(raw) && !/chrome|crios/i.test(raw)) {
                    browser = 'Safari';
                } else if (/edg/i.test(raw)) {
                    browser = 'Edge';
                } else if (/opera|opr/i.test(raw)) {
                    browser = 'Opera';
                }

                if (/windows nt 10/i.test(raw)) {
                    os = 'Windows 10';
                } else if (/windows nt 11/i.test(raw)) {
                    os = 'Windows 11';
                } else if (/windows nt/i.test(raw)) {
                    os = 'Windows';
                } else if (/android/i.test(raw)) {
                    os = 'Android';
                } else if (/iphone|ipad|ipod/i.test(raw)) {
                    os = 'iOS';
                } else if (/mac os x/i.test(raw)) {
                    os = 'macOS';
                } else if (/linux/i.test(raw)) {
                    os = 'Linux';
                }

                if (/mobile/i.test(raw)) {
                    device = 'Mobile';
                } else if (/tablet/i.test(raw)) {
                    device = 'Tablet';
                } else {
                    device = 'Desktop';
                }

                return {
                    raw,
                    platform,
                    brands,
                    browser,
                    os,
                    device,
                };
            };

            const collectPayload = async (geoConsent) => {
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                const screenInfo = window.screen || { width: null, height: null, colorDepth: null, pixelDepth: null };
                const hardwareConcurrency = navigator.hardwareConcurrency || null;
                const deviceMemory = navigator.deviceMemory || null;
                const languages = navigator.languages || [navigator.language];
                const userAgentParsed = parseUserAgent();
                const fingerprintSource = [
                    userAgentParsed.raw,
                    userAgentParsed.platform,
                    userAgentParsed.browser,
                    userAgentParsed.os,
                    userAgentParsed.device,
                    navigator.language,
                    languages.join(','),
                    screenInfo.width,
                    screenInfo.height,
                    screenInfo.colorDepth,
                    screenInfo.pixelDepth,
                    timezone,
                    hardwareConcurrency,
                    deviceMemory,
                ].join('|');

                return {
                    ts_client: new Date().toISOString(),
                    device_info: {
                        platform: userAgentParsed.platform,
                        device_category: userAgentParsed.device,
                        os: userAgentParsed.os,
                        browser: userAgentParsed.browser,
                        languages,
                        screen: {
                            width: screenInfo.width,
                            height: screenInfo.height,
                            color_depth: screenInfo.colorDepth,
                            pixel_depth: screenInfo.pixelDepth,
                        },
                        hardware_concurrency: hardwareConcurrency,
                        device_memory: deviceMemory,
                        timezone,
                        user_agent_raw: userAgentParsed.raw,
                        brands: userAgentParsed.brands,
                    },
                    fingerprint_hash: await hashFingerprint(fingerprintSource),
                    geo: await collectGeo(geoConsent),
                    geo_consent: geoConsent,
                };
            };

            const askConsent = () => {
                return new Promise((resolve) => {
                    if ('showModal' in consentDialog) {
                        consentDialog.showModal();
                    }

                    const acceptHandler = () => {
                        consentDialog.close();
                        resolve(true);
                    };

                    const denyHandler = () => {
                        consentDialog.close();
                        resolve(false);
                    };

                    consentDialog.querySelector('[data-action="accept"]').addEventListener('click', acceptHandler, { once: true });
                    consentDialog.querySelector('[data-action="deny"]').addEventListener('click', denyHandler, { once: true });
                });
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (!componentId) {
                    console.error('BaterPonto: componentId ausente');
                    return;
                }

                submitButton.disabled = true;
                submitButton.classList.add('opacity-70');

                try {
                    let consentValue = sessionStorage.getItem(consentKey);
                    let geoConsent = consentValue === 'accepted';

                    if (consentValue === null) {
                        geoConsent = await askConsent();
                        sessionStorage.setItem(consentKey, geoConsent ? 'accepted' : 'denied');
                    }

                    if (!geoConsent) {
                        alert('Para registrar o ponto é necessário permitir a coleta de localização. Tente novamente concedendo o acesso.');
                        return;
                    }

                    const payload = await collectPayload(geoConsent);
                    const component = window.Livewire?.find(componentId);
                    if (!component) {
                        throw new Error('Livewire component não encontrado.');
                    }

                    await component.set('clientPayload', payload);
                    await component.call('salvar');
                    console.info('BaterPonto: batida enviada', payload);
                } catch (error) {
                    console.error('Erro ao bater ponto', error);
                    alert('Não foi possível registrar o ponto. Tente novamente.');
                } finally {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-70');
                }
            });

            console.debug('BaterPonto: handler inicializado', componentId);
        };

        const scheduleInit = () => {
            const container = document.querySelector('[data-component-id="{{ $this->getId() }}"]');
            initPunchForm(container);
        };

        if (document.readyState !== 'loading') {
            scheduleInit();
        } else {
            document.addEventListener('DOMContentLoaded', scheduleInit, { once: true });
        }

        document.addEventListener('livewire:init', scheduleInit);
        document.addEventListener('livewire:navigated', scheduleInit);
    </script>
@endpush
