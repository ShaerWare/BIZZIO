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
     })"
     x-init="init()">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Коммерческий аукцион — торги</h3>
        <p class="text-sm text-gray-500 mb-4">
            НМЦ: {{ number_format((float) $auction->starting_price, 2, ',', ' ') }} {{ $auction->currency_symbol }} ·
            Веса: цена {{ (float) $auction->weight_price }}% / срок {{ (float) $auction->weight_deadline }}% / аванс {{ (float) $auction->weight_advance }}%
        </p>

        <div class="flex flex-col lg:flex-row gap-6">
            {{-- Левая колонка: настройка предложения --}}
            <div class="lg:w-1/2">
                {{-- Текущее лучшее предложение --}}
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-4">
                    <p class="text-sm font-medium text-emerald-800 mb-1">Текущее лучшее предложение</p>
                    <template x-if="best">
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
                    <template x-if="!best">
                        <p class="text-sm text-gray-500">Предложений ещё нет — первое корректное станет лучшим.</p>
                    </template>
                </div>

                <h4 class="text-sm font-semibold text-gray-900 mb-2">Настройка предложения</h4>

                @auth
                @if($myCompanies->isNotEmpty())
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

                        {{-- Цена --}}
                        <div>
                            <div class="flex justify-between items-baseline mb-1">
                                <label class="text-sm font-medium text-gray-700">Ваша цена ({{ $auction->currency_symbol }})</label>
                                <span class="text-xs" :class="criteria.price.is_best ? 'text-emerald-600' : 'text-gray-400'"
                                      x-text="hintText('price')"></span>
                            </div>
                            <input type="number" name="price" x-model.number="p" @input="recalc()" min="1" :max="nmc" :step="priceStep"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <input type="range" x-model.number="p" @input="recalc()" min="1" :max="nmc" :step="priceStep" class="w-full mt-1">
                            <p class="text-xs text-gray-400">Допустимо: до <span x-text="fmt(nmc)"></span></p>
                        </div>

                        {{-- Срок --}}
                        <div>
                            <div class="flex justify-between items-baseline mb-1">
                                <label class="text-sm font-medium text-gray-700">Ваш срок (дни)</label>
                                <span class="text-xs" :class="criteria.deadline.is_best ? 'text-emerald-600' : 'text-gray-400'"
                                      x-text="hintText('deadline')"></span>
                            </div>
                            <input type="number" name="deadline" x-model.number="d" @input="recalc()" min="1" :max="maxDeadline" :step="steps.d"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <input type="range" x-model.number="d" @input="recalc()" min="1" :max="maxDeadline" :step="steps.d" class="w-full mt-1">
                            <p class="text-xs text-gray-400">Допустимо: 1…<span x-text="maxDeadline"></span> дн.</p>
                        </div>

                        {{-- Аванс --}}
                        <div>
                            <div class="flex justify-between items-baseline mb-1">
                                <label class="text-sm font-medium text-gray-700">Ваш аванс (%)</label>
                                <span class="text-xs" :class="criteria.advance.is_best ? 'text-emerald-600' : 'text-gray-400'"
                                      x-text="hintText('advance')"></span>
                            </div>
                            <input type="number" name="advance_percent" x-model.number="a" @input="recalc()" min="0" :max="maxAdvance" :step="steps.a"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <input type="range" x-model.number="a" @input="recalc()" min="0" :max="maxAdvance" :step="steps.a" class="w-full mt-1">
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
                <h4 class="text-sm font-semibold text-gray-900 mb-2">
                    История лучших предложений (<span x-text="history.length"></span>)
                </h4>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
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

        // Ввод участника. До первого предложения этапа 2 референсов ещё нет —
        // используем разумные значения по умолчанию, чтобы форма была валидной.
        p: cfg.nmc,
        d: cfg.refs.d > 0 ? cfg.refs.d : 30,
        a: cfg.refs.a > 0 ? cfg.refs.a : 10,

        best: null,
        history: [],

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

        // Верхние границы полей. До первого предложения референсов нет — даём свободный ввод.
        get maxDeadline() { return this.refs.d > 0 ? this.refs.d : 730; },
        get maxAdvance() { return this.refs.a > 0 ? this.refs.a : 100; },

        init() {
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
                // #179 Референсы нормировки выставляются первым предложением — подхватываем из состояния.
                if (s.refs) {
                    this.refs = {
                        d: s.refs.max_deadline > 0 ? s.refs.max_deadline : this.refs.d,
                        a: s.refs.max_advance > 0 ? s.refs.max_advance : this.refs.a,
                    };
                }
                this.best = s.best_offer;
                this.history = s.best_offer_history || [];
                this.recalc();
            } catch (e) { /* временная сетевая ошибка — повторим на следующем тике */ }
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

            const bestScore = this.best ? this.best.total_score : null;
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
            if (!this.best) return '';
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
