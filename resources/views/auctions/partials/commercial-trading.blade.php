{{-- #179 Коммерческий аукцион (этап 2): «Настройка предложения» + история лучших --}}
@php
    $myCompanies = auth()->check()
        ? auth()->user()->moderatedCompanies
            ->where('is_verified', true)
            ->whereIn('id', $auction->invitations->pluck('company_id'))
            ->values()
        : collect();
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6"
     x-data="commercialAuction({
        stateUrl: '{{ route('auctions.state', $auction) }}',
        currency: '{{ $auction->currency_symbol }}',
        nmc: {{ (float) $auction->starting_price }},
        weights: { p: {{ (float) $auction->weight_price }}, d: {{ (float) $auction->weight_deadline }}, a: {{ (float) $auction->weight_advance }} },
        refs: { d: {{ (int) $auction->max_deadline }}, a: {{ (float) $auction->max_advance }} },
        steps: { p: {{ (float) $auction->step_price }}, d: {{ (int) $auction->step_deadline }}, a: {{ (float) $auction->step_advance }} },
        canOffer: {{ $myCompanies->isNotEmpty() ? 'true' : 'false' }},
        resultsHidden: {{ $auction->is_results_hidden && ! (auth()->check() && $auction->canManage(auth()->user())) ? 'true' : 'false' }},
     })"
     x-init="init()">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Коммерческий аукцион — торги</h3>
        <p class="text-sm text-gray-500 mb-2">
            НМЦ: {{ number_format((float) $auction->starting_price, 2, ',', ' ') }} {{ $auction->currency_symbol }} ·
            Веса: цена {{ (float) $auction->weight_price }}% / срок {{ (float) $auction->weight_deadline }}% / аванс {{ (float) $auction->weight_advance }}%
        </p>
        {{-- #198 Количество компаний-участников, сделавших ставку --}}
        <p class="text-sm mb-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 font-medium">
                Участников (сделали ставку): <span class="font-semibold" x-text="participants"></span>
            </span>
        </p>

        <div class="flex flex-col lg:flex-row gap-6">
            {{-- Левая колонка: настройка предложения --}}
            <div class="lg:w-1/2">
                {{-- Текущее лучшее предложение --}}
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-4">
                    <p class="text-sm font-medium text-emerald-800 mb-1">Текущее лучшее предложение</p>
                    {{-- #232 Скрытые результаты: детали лидера не показываем, только целевой балл для лидерства. --}}
                    <template x-if="resultsHidden">
                        <div class="text-sm text-gray-700 space-y-0.5">
                            <p class="text-gray-500">Результаты скрыты организатором — детали конкурентов не отображаются.</p>
                            <template x-if="bestScore !== null">
                                <p>Чтобы выйти в лидеры, ваш рейтинг должен превысить
                                   <span class="font-semibold" x-text="round2(bestScore)"></span>.</p>
                            </template>
                            <template x-if="bestScore === null">
                                <p class="text-gray-500">Предложений ещё нет — первое корректное станет лучшим.</p>
                            </template>
                        </div>
                    </template>
                    <template x-if="!resultsHidden && best">
                        <div class="text-sm text-gray-700 space-y-0.5">
                            <p>Участник: <span class="font-mono font-semibold" x-text="best.anonymous_code"></span>
                                <span x-show="best.is_mine" class="text-emerald-600">(ваше)</span></p>
                            <p>Цена: <span class="font-semibold" x-text="fmt(best.price)"></span> <span x-text="currency"></span></p>
                            <p>Срок: <span class="font-semibold" x-text="best.deadline"></span> дн. ·
                               Аванс: <span class="font-semibold" x-text="best.advance"></span>%</p>
                            <p>Рейтинг: <span class="font-semibold" x-text="round2(best.total_score)"></span> ·
                               <span x-text="best.time"></span></p>
                        </div>
                    </template>
                    <template x-if="!resultsHidden && !best">
                        <p class="text-sm text-gray-500">Предложений ещё нет — первое корректное станет лучшим.</p>
                    </template>
                </div>

                @auth
                @if($myCompanies->isNotEmpty())
                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Настройка предложения</h4>
                    <form method="POST" action="{{ route('auctions.offers.store', $auction) }}" class="space-y-4"
                          @submit="if (!wouldBeat) $event.preventDefault()">
                        @csrf
                        @if($myCompanies->count() > 1)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Компания-участник</label>
                                <select name="company_id" required class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach($myCompanies as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="company_id" value="{{ $myCompanies->first()->id }}">
                        @endif

                        {{-- Цена (#207: крупные кнопки −/+ со стабильной шириной + разделитель разрядов в поле) --}}
                        <div>
                            <div class="flex justify-between items-baseline mb-1">
                                <label class="text-sm font-medium text-gray-700">Ваша цена ({{ $auction->currency_symbol }})</label>
                                <span class="text-xs" :class="criteria.price.is_best ? 'text-emerald-600' : 'text-gray-400'"
                                      x-text="hintText('price')"></span>
                            </div>
                            {{-- Реальное числовое значение цены уходит на сервер скрытым полем; видимое поле — форматированное. --}}
                            <input type="hidden" name="price" :value="p">
                            <div class="flex items-stretch gap-2">
                                <button type="button" @click="stepPrice(-1)" aria-label="Уменьшить цену"
                                        class="flex-none w-12 h-12 rounded-md border border-gray-300 bg-gray-50 text-3xl leading-none text-gray-700 hover:bg-gray-100 select-none">−</button>
                                <input type="text" inputmode="decimal" x-model="priceStr" @input="onPriceInput()" @blur="formatPriceStr()"
                                       class="flex-1 w-full text-center text-lg rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <button type="button" @click="stepPrice(1)" aria-label="Увеличить цену"
                                        class="flex-none w-12 h-12 rounded-md border border-gray-300 bg-gray-50 text-3xl leading-none text-gray-700 hover:bg-gray-100 select-none">+</button>
                            </div>
                            <input type="range" x-model.number="p" @input="syncPriceStr()" min="1" :max="nmc" :step="priceStep" class="w-full mt-2">
                            <p class="text-xs text-gray-500 mt-1">допустимо до <span x-text="fmt(nmc)"></span> {{ $auction->currency_symbol }}</p>
                        </div>

                        {{-- Срок (#207: крупные кнопки −/+ как у цены) --}}
                        <div>
                            <div class="flex justify-between items-baseline mb-1">
                                <label class="text-sm font-medium text-gray-700">Ваш срок (дни)</label>
                                <span class="text-xs" :class="criteria.deadline.is_best ? 'text-emerald-600' : 'text-gray-400'"
                                      x-text="hintText('deadline')"></span>
                            </div>
                            <input type="hidden" name="deadline" :value="d">
                            <div class="flex items-stretch gap-2">
                                <button type="button" @click="stepDeadline(-1)" aria-label="Уменьшить срок"
                                        class="flex-none w-12 h-11 rounded-md border border-gray-300 bg-gray-50 text-3xl leading-none text-gray-700 hover:bg-gray-100 select-none">−</button>
                                {{-- #232 step="1" (любое целое), а НЕ организаторский шаг: иначе дефолт срока (=max_deadline)
                                     мог не совпасть с шагом при базе min=1 и HTML5-валидация молча блокировала сабмит формы.
                                     Кнопки −/+ шагают организаторским шагом через stepDeadline(). --}}
                                <input type="number" x-model.number="d" @input="recalc()" min="1" :max="maxDeadline" step="1"
                                       class="ca-no-spin flex-1 w-full text-center rounded-md border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <button type="button" @click="stepDeadline(1)" aria-label="Увеличить срок"
                                        class="flex-none w-12 h-11 rounded-md border border-gray-300 bg-gray-50 text-3xl leading-none text-gray-700 hover:bg-gray-100 select-none">+</button>
                            </div>
                            <input type="range" x-model.number="d" @input="recalc()" min="1" :max="maxDeadline" step="1" class="w-full mt-2">
                            <p class="text-xs text-gray-400">Допустимо: 1…<span x-text="maxDeadline"></span> дн.</p>
                        </div>

                        {{-- Аванс (#207: крупные кнопки −/+ как у цены) --}}
                        <div>
                            <div class="flex justify-between items-baseline mb-1">
                                <label class="text-sm font-medium text-gray-700">Ваш аванс (%)</label>
                                <span class="text-xs" :class="criteria.advance.is_best ? 'text-emerald-600' : 'text-gray-400'"
                                      x-text="hintText('advance')"></span>
                            </div>
                            <input type="hidden" name="advance_percent" :value="a">
                            <div class="flex items-stretch gap-2">
                                <button type="button" @click="stepAdvance(-1)" aria-label="Уменьшить аванс"
                                        class="flex-none w-12 h-11 rounded-md border border-gray-300 bg-gray-50 text-3xl leading-none text-gray-700 hover:bg-gray-100 select-none">−</button>
                                {{-- #232 step="any": организаторский шаг аванса мог не совпасть с дефолтом/max при базе
                                     min=0 → HTML5-валидация блокировала сабмит. Кнопки −/+ шагают через stepAdvance(). --}}
                                <input type="number" x-model.number="a" @input="recalc()" min="0" :max="maxAdvance" step="any"
                                       class="ca-no-spin flex-1 w-full text-center rounded-md border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <button type="button" @click="stepAdvance(1)" aria-label="Увеличить аванс"
                                        class="flex-none w-12 h-11 rounded-md border border-gray-300 bg-gray-50 text-3xl leading-none text-gray-700 hover:bg-gray-100 select-none">+</button>
                            </div>
                            <input type="range" x-model.number="a" @input="recalc()" min="0" :max="maxAdvance" step="any" class="w-full mt-2">
                            <p class="text-xs text-gray-400">Допустимо: 0…<span x-text="maxAdvance"></span>%</p>
                        </div>

                        {{-- Общий вердикт --}}
                        <div class="rounded-lg p-3 text-sm font-medium"
                             :class="wouldBeat ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'">
                            <span x-show="wouldBeat">✔ Лучшее предложение — можно подавать (рейтинг <span x-text="round2(total)"></span>)</span>
                            <span x-show="!wouldBeat">До лучшего предложения не хватает <span x-text="round2(deficit)"></span> баллов</span>
                        </div>

                        <button type="submit" :disabled="!wouldBeat"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition"
                                :class="wouldBeat ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300 cursor-not-allowed'">
                            Подать предложение
                        </button>
                    </form>
                @elseif($auction->canManage(auth()->user()))
                    {{-- #232 Организатор не может участвовать в собственном аукционе — говорим об этом явно. --}}
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
                        Вы организатор этой процедуры. Подавать предложения могут только приглашённые компании-участники — участие организатора в собственном аукционе недоступно.
                    </div>
                @else
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-600">
                        Подавать предложения могут только компании — участники этой процедуры (подавшие заявку на этапе 1).
                    </div>
                @endif
                @else
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800">
                        <a href="{{ route('login') }}" class="font-semibold underline">Войдите</a>, чтобы участвовать в торгах.
                    </div>
                @endauth
            </div>

            {{-- Правая колонка: история лучших предложений --}}
            <div class="lg:w-1/2">
                {{-- #232 При скрытых результатах историю конкурентов не показываем. --}}
                <div x-show="resultsHidden" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
                    Результаты аукциона скрыты организатором. История предложений участников будет доступна после завершения торгов.
                </div>
                <h4 class="text-sm font-semibold text-gray-900 mb-2" x-show="!resultsHidden">
                    История лучших предложений (<span x-text="history.length"></span>)
                </h4>
                <div class="border border-gray-200 rounded-lg overflow-hidden" x-show="!resultsHidden">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-2 py-2 text-left">Код</th>
                                <th class="px-2 py-2 text-right">Цена</th>
                                <th class="px-2 py-2 text-right">Срок</th>
                                <th class="px-2 py-2 text-right">Аванс</th>
                                <th class="px-2 py-2 text-right">Рейтинг</th>
                                <th class="px-2 py-2 text-right">Время</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="o in [...history].reverse()" :key="o.id">
                                <tr :class="o.is_mine ? 'bg-emerald-50' : ''">
                                    <td class="px-2 py-2 font-mono" x-text="o.anonymous_code"></td>
                                    <td class="px-2 py-2 text-right" x-text="fmt(o.price)"></td>
                                    <td class="px-2 py-2 text-right" x-text="o.deadline"></td>
                                    <td class="px-2 py-2 text-right" x-text="o.advance + '%'"></td>
                                    <td class="px-2 py-2 text-right" x-text="round2(o.total_score)"></td>
                                    <td class="px-2 py-2 text-right text-gray-400" x-text="o.time"></td>
                                </tr>
                            </template>
                            <template x-if="history.length === 0">
                                <tr><td colspan="6" class="px-2 py-4 text-center text-gray-400">Предложений пока нет</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* #207 Скрываем нативные мелкие стрелки number-инпута цены — управление крупными кнопками −/+ */
    .ca-no-spin::-webkit-outer-spin-button,
    .ca-no-spin::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .ca-no-spin { -moz-appearance: textfield; appearance: textfield; }
</style>
@endpush

@push('scripts')
<script>
function commercialAuction(cfg) {
    const EPS = 1e-6;
    return {
        stateUrl: cfg.stateUrl,
        currency: cfg.currency,
        nmc: cfg.nmc,
        weights: cfg.weights,
        refs: cfg.refs,
        steps: cfg.steps,
        canOffer: cfg.canOffer,
        // #232 Скрытые результаты: детали лидера скрыты, известен только целевой балл (bestScore).
        resultsHidden: cfg.resultsHidden,
        bestScore: null,

        // #210 Референсы нормировки (max срок/аванс) заданы организатором на этапе 1 и не меняются
        // в ходе торгов. Стартовая позиция участника = максимумы (худший балл) → улучшает вниз.
        p: cfg.nmc,
        d: cfg.refs.d,
        a: cfg.refs.a,

        // #207 Строковое представление цены с разделителем разрядов (реальное число — в this.p).
        priceStr: '',

        best: null,
        history: [],
        participants: 0, // #198 количество компаний-участников, сделавших ставку

        // Результаты анализа
        total: 0,
        deficit: 0,
        wouldBeat: true,
        criteria: { price: {}, deadline: {}, advance: {} },

        polling: null,

        get priceStep() {
            // Шаг цены задан в процентах от НМЦ.
            return Math.max(0.01, +(this.nmc * this.steps.p / 100).toFixed(2));
        },

        // #210 Верхние границы полей — организаторские максимумы (100% шкалы критерия).
        get maxDeadline() { return this.refs.d; },
        get maxAdvance() { return this.refs.a; },

        init() {
            this.formatPriceStr();
            this.fetchState();
            this.recalc();
            this.polling = setInterval(() => this.fetchState(), 5000);
            window.addEventListener('beforeunload', () => clearInterval(this.polling));
        },

        async fetchState() {
            try {
                const res = await fetch(this.stateUrl, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) {
                    // Торги завершились — перезагружаем, чтобы показать итог.
                    if (res.status === 400) location.reload();
                    return;
                }
                const s = await res.json();
                if (s.status !== 'trading') { location.reload(); return; }
                // #206 Поля отталкиваются от текущего лучшего предложения: при смене лидера
                // пересеиваем значения полей его цифрами, чтобы участник улучшал от текущего лучшего.
                const prevBestId = this.best ? this.best.id : null;
                this.best = s.best_offer;
                this.history = s.best_offer_history || [];
                this.participants = s.participants_count ?? this.participants; // #198
                // #232 Целевой балл лидерства (при скрытых результатах — единственный ориентир).
                this.resultsHidden = s.results_hidden ?? this.resultsHidden;
                this.bestScore = s.best_score ?? null;
                const newBestId = this.best ? this.best.id : null;
                if (! this.resultsHidden && newBestId !== prevBestId) {
                    // Видимый лидер сменился — пересеиваем поля его цифрами (участник улучшает от лучшего).
                    this.seedFromBest();
                } else {
                    // Скрытые результаты: цифры лидера неизвестны — не пересеиваем, только пересчёт по целевому баллу.
                    this.recalc();
                }
            } catch (e) { /* временная сетевая ошибка — повторим на следующем тике */ }
        },

        // #206 Значения полей = текущее лучшее предложение (или НМЦ/организаторские максимумы, если предложений нет).
        seedFromBest() {
            if (this.best) {
                this.p = this.best.price;
                this.d = this.best.deadline;
                this.a = this.best.advance;
            } else {
                this.p = this.nmc;
                this.d = this.refs.d;
                this.a = this.refs.a;
            }
            this.formatPriceStr();
            this.recalc();
        },

        // #207 Шаг цены крупными кнопками −/+ (курсор остаётся на месте).
        stepPrice(dir) {
            const v = (Number(this.p) || 0) + dir * this.priceStep;
            this.p = Math.min(this.nmc, Math.max(1, +v.toFixed(2)));
            this.formatPriceStr();
            this.recalc();
        },

        // #207 Шаги срока/аванса крупными кнопками −/+ (как у цены).
        stepDeadline(dir) {
            const v = (Number(this.d) || 0) + dir * (this.steps.d || 1);
            this.d = Math.min(this.maxDeadline, Math.max(1, Math.round(v)));
            this.recalc();
        },
        stepAdvance(dir) {
            const v = (Number(this.a) || 0) + dir * (this.steps.a || 1);
            this.a = Math.min(this.maxAdvance, Math.max(0, +v.toFixed(2)));
            this.recalc();
        },

        // #207 Разделитель разрядов в поле цены.
        // Во время ввода строку не трогаем (курсор стабилен) — только парсим в число.
        onPriceInput() {
            const parsed = parseFloat(String(this.priceStr).replace(/\s/g, '').replace(',', '.'));
            this.p = isNaN(parsed) ? 0 : parsed;
            this.recalc();
        },
        // Форматируем строку цены (разряды через пробел). Вызывается на blur, seed и шагах кнопок.
        formatPriceStr() {
            const n = Number(this.p) || 0;
            this.priceStr = n.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },
        // Слайдер меняет число напрямую — синхронизируем видимую строку.
        syncPriceStr() {
            this.formatPriceStr();
            this.recalc();
        },

        normalize(x, ref) {
            if (ref <= 0) return 0;
            return Math.max(0, Math.min(100, 100 * (ref - x) / ref));
        },

        scores(p, d, a) {
            const sp = this.normalize(p, this.nmc);
            const sd = this.normalize(d, this.refs.d);
            const sa = this.normalize(a, this.refs.a);
            const total = (sp * this.weights.p + sd * this.weights.d + sa * this.weights.a) / 100;
            return { price: sp, deadline: sd, advance: sa, total };
        },

        recalc() {
            const sc = this.scores(this.p, this.d, this.a);
            this.total = sc.total;

            // #206 Балл лидера пересчитываем из его критериев на той же точности, что и sc.total.
            // Если брать округлённое best.total_score (decimal(8,4) с сервера), идентичное
            // предложение ложно «превосходит» лидера и кнопка «Подать» активна — так проходили
            // одинаковые предложения подряд.
            // #232 При скрытых результатах цифры лидера неизвестны — берём целевой балл с сервера.
            // Иначе (результаты видны) считаем балл лидера из его критериев на полной точности.
            const bestScore = this.resultsHidden
                ? this.bestScore
                : (this.best ? this.scores(this.best.price, this.best.deadline, this.best.advance).total : null);
            const target = bestScore !== null ? bestScore + EPS : null;
            this.deficit = target !== null ? Math.max(0, target - sc.total) : 0;
            this.wouldBeat = target === null ? true : sc.total > target;

            this.criteria = {
                price: this.criterion(target, sc, 'p', 'price', this.nmc, this.p, this.best ? this.best.price : null),
                deadline: this.criterion(target, sc, 'd', 'deadline', this.refs.d, this.d, this.best ? this.best.deadline : null),
                advance: this.criterion(target, sc, 'a', 'advance', this.refs.a, this.a, this.best ? this.best.advance : null),
            };
        },

        criterion(target, sc, wkey, skey, ref, value, bestValue) {
            const isBest = bestValue !== null && value < bestValue;
            const weight = this.weights[wkey];
            if (target === null || weight <= 0) return { is_best: isBest, threshold: null, reachable: false };

            const others = sc.total * 100 - sc[skey] * weight;
            const targetScore = (target * 100 - others) / weight;
            if (targetScore > 100 + EPS) return { is_best: isBest, threshold: null, reachable: false };

            const threshold = ref * (1 - Math.max(0, targetScore) / 100);
            return { is_best: isBest, threshold, reachable: true };
        },

        hintText(key) {
            const c = this.criteria[key];
            if (!c) return '';
            if (c.is_best) return 'Лучший критерий';
            // #232 Порог «уменьшите до X» показываем при наличии цели (лидера) — в т.ч. при скрытых
            // результатах: X считается от целевого балла и НЕ раскрывает цифры лидера.
            if (this.bestScore === null) return '';
            if (!c.reachable || c.threshold === null) return '';
            const unit = key === 'advance' ? '%' : (key === 'deadline' ? ' дн.' : '');
            const val = key === 'price' ? this.fmt(Math.floor(c.threshold)) : Math.floor(c.threshold);
            return 'Уменьшите до ' + val + unit;
        },

        fmt(n) {
            return Number(n).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        round2(n) { return Math.round(Number(n) * 100) / 100; },
    };
}
</script>
@endpush
