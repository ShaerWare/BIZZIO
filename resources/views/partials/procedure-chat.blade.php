{{-- #218 Чат процедуры (этап 1): вопросы участников и ответы организатора.
     Виден организатору и участникам. Для участников переписка обезличена —
     коды вида «У-01» намеренно отличаются от кодов торгов этапа 2.
     #295 У открытой процедуры вопрос можно задать до подачи заявки.
     Параметр: $procedure (Rfq|Auction). --}}
@php
    $viewer = auth()->user();
    $canRead = $procedure->canReadChat($viewer);
    $bannedParticipant = $procedure->bannedParticipantFor($viewer);
@endphp

@if($canRead || $bannedParticipant)
    @php
        $isOrganizer = $viewer && $procedure->canManage($viewer);
        $isAuction = $procedure instanceof \App\Models\Auction;
        $routePrefix = $isAuction ? 'auctions' : 'rfqs';
        $chatOpen = $procedure->isChatOpen();

        $participantRows = collect();
        if ($isOrganizer) {
            $records = $procedure->chatParticipants()->get()->keyBy('company_id');
            // #295 В списке — и те, кто писал в чат, не подав заявку: их тоже можно отстранить
            $participantRows = \App\Models\Company::whereIn('id', $procedure->chatVisibleCompanyIds())
                ->where('id', '!=', $procedure->company_id)
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'code' => optional($records->get($c->id))->chat_code ?? '—',
                    'banned' => optional($records->get($c->id))->banned_at !== null,
                ])
                ->values();
        }
    @endphp

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6"
         x-data="procedureChat({
            feedUrl: '{{ route($routePrefix.'.chat.index', $procedure) }}',
            postUrl: '{{ route($routePrefix.'.chat.store', $procedure) }}',
            banUrl: '{{ route($routePrefix.'.chat.ban', $procedure) }}',
            isOrganizer: {{ $isOrganizer ? 'true' : 'false' }},
            chatOpen: {{ $chatOpen ? 'true' : 'false' }},
         })"
         x-init="init()">
        <div class="p-6">
            <div class="flex items-start justify-between mb-1">
                <h3 class="text-lg font-semibold text-gray-900">Чат процедуры</h3>
                <span x-show="myCode" x-cloak class="font-mono text-sm px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">
                    Ваш код: <span x-text="myCode"></span>
                </span>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                Вопросы по процедуре и ответы организатора. Переписка видна только организатору и участникам;
                участники не видят названий компаний друг друга.
                @if($procedure->chatOpenToProspects())
                    {{-- #295 Вопрос можно задать до подачи заявки --}}
                    <span class="block mt-1">Задать вопрос можно до подачи заявки на участие.</span>
                @endif
                @isset($chatNote)
                    <span class="block mt-1">{{ $chatNote }}</span>
                @endisset
            </p>

            @if($bannedParticipant)
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">Ваша компания отстранена от участия в процедуре.</p>
                    <p class="mt-1">Причина: {{ $bannedParticipant->ban_reason }}</p>
                </div>
            @endif

            @if($canRead)
                {{-- Лента сообщений --}}
                <div class="border border-gray-200 rounded-lg h-80 overflow-y-auto p-3 space-y-3 bg-gray-50" x-ref="feed">
                    <template x-for="m in messages" :key="m.id">
                        <div>
                            <template x-if="m.is_system">
                                <div class="text-xs text-center text-gray-500 bg-gray-100 border border-gray-200 rounded px-3 py-2"
                                     x-text="m.body"></div>
                            </template>
                            <template x-if="!m.is_system">
                                <div class="flex" :class="m.is_mine ? 'justify-end' : 'justify-start'">
                                    <div class="max-w-[85%] rounded-lg px-3 py-2 text-sm"
                                         :class="m.is_organizer
                                            ? 'bg-blue-50 border border-blue-200 text-blue-900'
                                            : (m.is_mine ? 'bg-emerald-50 border border-emerald-200 text-emerald-900'
                                                         : 'bg-white border border-gray-200 text-gray-800')">
                                        <p class="text-xs mb-0.5 opacity-70">
                                            <span class="font-mono font-semibold" x-text="m.author"></span>
                                            <span x-show="m.company" x-cloak x-text="' · ' + m.company"></span>
                                            <span x-text="' · ' + m.time"></span>
                                        </p>
                                        <p class="whitespace-pre-line break-words" x-text="m.body"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="messages.length === 0">
                        <p class="text-sm text-center text-gray-400 py-6">Сообщений пока нет</p>
                    </template>
                </div>

                {{-- Форма отправки --}}
                @if($chatOpen)
                    <form class="mt-3 flex items-start gap-2" @submit.prevent="send()">
                        <textarea x-model="draft" rows="2" maxlength="2000"
                                  placeholder="Задайте вопрос по процедуре…"
                                  class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"></textarea>
                        <button type="submit" :disabled="sending || !draft.trim()"
                                class="px-4 py-2 rounded-md bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 disabled:opacity-50">
                            Отправить
                        </button>
                    </form>
                    <p x-show="error" x-cloak x-text="error" class="mt-1 text-sm text-red-600"></p>
                @else
                    <p class="mt-3 text-sm text-gray-500">Приём сообщений завершён — чат доступен только для чтения.</p>
                @endif

                {{-- Управление участниками (только организатор) --}}
                @if($isOrganizer && $participantRows->isNotEmpty())
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Участники процедуры</h4>
                        <p class="text-xs text-gray-500 mb-3">
                            Отстранение блокирует участнику чат и подачу заявок, а уже поданные заявки аннулирует —
                            в определении победителя они не участвуют.
                        </p>
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-500 text-xs">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Код</th>
                                        <th class="px-3 py-2 text-left">Компания</th>
                                        <th class="px-3 py-2 text-right">Действие</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($participantRows as $row)
                                        <tr :class="banned.includes({{ $row['id'] }}) ? 'bg-red-50 text-red-800' : ''">
                                            <td class="px-3 py-2 font-mono">{{ $row['code'] }}</td>
                                            <td class="px-3 py-2">{{ $row['name'] }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <template x-if="banned.includes({{ $row['id'] }})">
                                                    <span class="text-xs font-semibold">Отстранён</span>
                                                </template>
                                                <template x-if="!banned.includes({{ $row['id'] }})">
                                                    <button type="button" @click="ban({{ $row['id'] }}, @js($row['name']))"
                                                            class="text-xs font-semibold text-red-600 hover:text-red-800">
                                                        Отстранить
                                                    </button>
                                                </template>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    @if($canRead)
        @push('scripts')
        <script>
        function procedureChat(cfg) {
            return {
                ...cfg,
                messages: [],
                banned: @json($participantRows->where('banned', true)->pluck('id')->values()),
                myCode: null,
                draft: '',
                sending: false,
                error: null,
                lastId: 0,
                polling: null,

                init() {
                    this.load();
                    // Тот же приём, что и в панели торгов: короткий поллинг + докачка по after_id.
                    this.polling = setInterval(() => this.load(), 7000);
                    window.addEventListener('beforeunload', () => clearInterval(this.polling));
                },

                async load() {
                    try {
                        const url = this.lastId ? `${this.feedUrl}?after_id=${this.lastId}` : this.feedUrl;
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.myCode = data.my_code;
                        if (data.messages.length) {
                            this.messages.push(...data.messages);
                            this.lastId = this.messages[this.messages.length - 1].id;
                            this.$nextTick(() => this.scrollToEnd());
                        }
                    } catch (e) { /* следующий тик повторит */ }
                },

                scrollToEnd() {
                    const el = this.$refs.feed;
                    if (el) el.scrollTop = el.scrollHeight;
                },

                async send() {
                    const body = this.draft.trim();
                    if (!body || this.sending) return;
                    this.sending = true;
                    this.error = null;
                    try {
                        const res = await fetch(this.postUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ body }),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            this.error = data.message || 'Не удалось отправить сообщение';
                            return;
                        }
                        this.draft = '';
                        this.messages.push(data.message);
                        this.lastId = data.message.id;
                        this.myCode = this.myCode || data.message.chat_code;
                        this.$nextTick(() => this.scrollToEnd());
                    } catch (e) {
                        this.error = 'Ошибка сети';
                    } finally {
                        this.sending = false;
                    }
                },

                async ban(companyId, companyName) {
                    const reason = window.prompt(
                        `Причина отстранения компании «${companyName}» от участия:`,
                        'Компания отстранена от участия в торгах за неподобающие комментарии'
                    );
                    if (!reason || !reason.trim()) return;
                    try {
                        const res = await fetch(this.banUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ company_id: companyId, reason: reason.trim() }),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            this.error = data.message || 'Не удалось отстранить участника';
                            return;
                        }
                        this.banned.push(companyId);
                        this.load();
                    } catch (e) {
                        this.error = 'Ошибка сети';
                    }
                },
            };
        }
        </script>
        @endpush
    @endif
@endif
