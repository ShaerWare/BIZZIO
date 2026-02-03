@extends('layouts.app')

@section('title', 'Правила проведения тендеров')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Правила проведения тендеров</h1>
            <p class="mt-2 text-sm text-gray-600">Информация о процедурах запроса котировок и аукционов на платформе Bizzio</p>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 space-y-8">

                {{-- 1. Общие положения --}}
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">1. Общие положения</h2>
                    <div class="prose prose-sm text-gray-700 space-y-2">
                        <p>Платформа Bizzio предоставляет два вида закупочных процедур:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Запрос котировок (RFQ)</strong> — процедура выбора поставщика на основе комплексной оценки по нескольким критериям (цена, срок, условия оплаты).</li>
                            <li><strong>Аукцион</strong> — процедура снижения цены в реальном времени, где участники конкурируют, предлагая более низкую стоимость.</li>
                        </ul>
                        <p>Процедуры могут быть <strong>открытыми</strong> (доступны всем зарегистрированным компаниям) или <strong>закрытыми</strong> (только по приглашению организатора).</p>
                        <p class="text-gray-500 italic">Результаты подведения итогов носят информационный характер и не обязывают стороны к заключению договора.</p>
                    </div>
                </section>

                {{-- 2. Запрос котировок --}}
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">2. Запрос котировок (RFQ)</h2>
                    <div class="prose prose-sm text-gray-700 space-y-2">
                        <h3 class="text-lg font-medium text-gray-800">Этапы проведения</h3>
                        <ol class="list-decimal pl-5 space-y-1">
                            <li>Организатор создаёт запрос котировок с описанием, техническим заданием и критериями оценки.</li>
                            <li>В период приёма заявок участники подают предложения (цена, срок выполнения, размер аванса).</li>
                            <li>После окончания приёма система автоматически рассчитывает итоговый балл каждой заявки и определяет победителя.</li>
                            <li>Формируется протокол подведения итогов (PDF).</li>
                        </ol>

                        <h3 class="text-lg font-medium text-gray-800 mt-4">Формула расчёта итогового балла</h3>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <p class="mb-2">Каждая заявка оценивается по трём критериям с весами, заданными организатором:</p>
                            <ul class="space-y-1 mb-3">
                                <li><strong>Балл за цену</strong> = 100 &times; (минимальная цена / ваша цена)</li>
                                <li><strong>Балл за срок</strong> = 100 &times; (минимальный срок / ваш срок)</li>
                                <li><strong>Балл за аванс</strong> = 100 &minus; (ваш аванс / максимальный аванс) &times; 100</li>
                            </ul>
                            <p class="font-medium">Итоговый балл = (Б<sub>цена</sub> &times; Вес<sub>цена</sub> + Б<sub>срок</sub> &times; Вес<sub>срок</sub> + Б<sub>аванс</sub> &times; Вес<sub>аванс</sub>) / 100</p>
                            <p class="mt-2 text-gray-500">Чем выше итоговый балл — тем лучше заявка. Побеждает заявка с максимальным баллом.</p>
                        </div>

                        <h3 class="text-lg font-medium text-gray-800 mt-4">Анонимность</h3>
                        <p>На этапе приёма заявок все участники обезличены (отображаются как «Участник 1», «Участник 2» и т.д.). Названия компаний раскрываются только после подведения итогов.</p>
                    </div>
                </section>

                {{-- 3. Аукцион --}}
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">3. Аукцион</h2>
                    <div class="prose prose-sm text-gray-700 space-y-2">
                        <h3 class="text-lg font-medium text-gray-800">Этапы проведения</h3>
                        <ol class="list-decimal pl-5 space-y-1">
                            <li><strong>Приём заявок</strong> — участники подают первоначальные заявки с ценой, не превышающей начальную максимальную цену (НМЦ).</li>
                            <li><strong>Торги</strong> — участники снижают цену в реальном времени. Каждая ставка должна быть ниже текущей минимальной на 0,5%–5%.</li>
                            <li><strong>Завершение</strong> — аукцион автоматически закрывается через 20 минут после последней ставки. Формируется протокол.</li>
                        </ol>

                        <h3 class="text-lg font-medium text-gray-800 mt-4">Правила торгов</h3>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Минимальный шаг снижения — 0,5% от текущей цены.</li>
                            <li>Максимальный шаг снижения — 5% от текущей цены.</li>
                            <li>Один участник не может сделать две ставки подряд.</li>
                            <li>Все участники обезличены на протяжении всей процедуры — названия компаний раскрываются только в итоговом протоколе.</li>
                        </ul>

                        <h3 class="text-lg font-medium text-gray-800 mt-4">Определение победителя</h3>
                        <p>Победителем признаётся участник, предложивший наименьшую цену на момент завершения торгов.</p>
                    </div>
                </section>

                {{-- 4. Общие правила --}}
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">4. Общие правила для участников</h2>
                    <div class="prose prose-sm text-gray-700 space-y-2">
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Для участия в тендерах необходимо зарегистрировать компанию на платформе.</li>
                            <li>Организатор не может участвовать в собственной процедуре.</li>
                            <li>Все сроки указываются по московскому времени (UTC+3).</li>
                            <li>Организатор вправе отменить процедуру до подведения итогов.</li>
                        </ul>
                    </div>
                </section>

            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('tenders.index') }}"
               class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition">
                Перейти к тендерам
            </a>
        </div>

    </div>
</div>
@endsection
