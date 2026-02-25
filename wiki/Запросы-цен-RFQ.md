# Запросы цен (RFQ)

RFQ (Request for Quotation / Запрос цен) — механизм конкурентного отбора поставщиков по критериям цены, сроков и условий.

## Жизненный цикл

```
draft → active → closed
```

| Статус | Описание |
|--------|----------|
| draft | Черновик. Видим только организатору/модераторам. Можно редактировать и удалять |
| active | Приём заявок. Между start_date и end_date |
| closed | Закрыт. CloseRfqJob определил победителя и сгенерировал PDF-протокол |

## Типы

| Тип | Описание |
|-----|----------|
| open | Открытый — любая верифицированная компания может подать заявку |
| closed | Закрытый — только приглашённые компании |

## Система скоринга

Каждому RFQ задаются веса критериев (сумма = 100%):
- **weight_price** — вес цены (по умолчанию 50%)
- **weight_deadline** — вес срока (по умолчанию 30%)
- **weight_advance** — вес аванса (по умолчанию 20%)

### Формулы расчёта (`RfqScoringService`)

```
score_price    = 100 × (min_price / bid_price)
score_deadline = 100 × (min_deadline / bid_deadline)
score_advance  = 100 - ((bid_advance / max_advance) × 100)

total_score = (score_price × weight_price + score_deadline × weight_deadline + score_advance × weight_advance) / 100
```

- Чем ниже цена → выше балл
- Чем меньше срок → выше балл
- Чем меньше аванс → выше балл
- Победитель = максимальный total_score

## Закрытие RFQ (CloseRfqJob)

Запускается автоматически при наступлении `end_date`:

1. Рассчитывает баллы для всех заявок (`RfqScoringService::calculateScores`)
2. Определяет победителя (`RfqScoringService::determineWinner`)
3. Обновляет статус заявки победителя на 'winner'
4. Обновляет RFQ: status='closed', winner_bid_id
5. Генерирует PDF-протокол (`RfqProtocolService::generateProtocol`)
6. Отправляет уведомление `TenderClosed` всем участникам

**Retry:** tries=3, timeout=120 сек.

## PDF-протокол

Генерируется `RfqProtocolService`:
- Шаблон: `resources/views/pdfs/rfq-protocol.blade.php`
- Сохраняется: `storage/app/public/rfq-protocols/protocol_К-ГГММДД-0001_timestamp.pdf`
- Также прикрепляется через Media Library (коллекция 'protocol')

Содержание протокола:
- Реквизиты организатора
- Параметры RFQ (критерии, веса)
- Таблица заявок с баллами
- Победитель

## Заявки (RfqBid)

Одна компания — одна заявка (unique: rfq_id + company_id).

Поля заявки:
- price — цена
- deadline — срок выполнения (дни)
- advance_percent — процент аванса (0-100)
- comment — комментарий

Баллы рассчитываются автоматически при закрытии.

## Приглашения (RfqInvitation)

Для закрытых RFQ организатор приглашает компании:
- Создаётся RfqInvitation (status: pending)
- Отправляется событие TenderInvitationSent
- Приглашённые компании видят RFQ и могут подать заявку

## Маршруты

| Маршрут | Описание |
|---------|----------|
| GET /rfqs | Каталог RFQ (черновики скрыты) |
| GET /rfqs/create | Создание |
| POST /rfqs | Сохранение |
| GET /rfqs/{id} | Просмотр |
| GET /rfqs/{id}/edit | Редактирование (только draft) |
| PUT /rfqs/{id} | Обновление |
| DELETE /rfqs/{id} | Удаление (только draft) |
| POST /rfqs/{id}/activate | Активация из draft |
| POST /rfqs/{id}/bids | Подача заявки |
| POST /rfqs/{id}/invitations | Приглашение компании |
| GET /my-rfqs | Мои RFQ (организатор) |
| GET /my-bids | Мои заявки (участник) |
| GET /my-invitations | Мои приглашения |

## Валидация (StoreRfqRequest)

- company_id: required, exists, проверка что пользователь модератор
- title: required, max 255
- type: required, in:open,closed
- currency: required, in:RUB,USD,CNY
- start_date: required, after_or_equal:today
- end_date: required, after:start_date
- weight_price + weight_deadline + weight_advance = 100% (обязательно)
- technical_specification: PDF, max 10MB (или temp-upload)

## Валидация заявки (StoreBidRequest)

- company_id: required, exists, проверка что пользователь модератор
- price: required, numeric, min:0
- deadline: required, integer, min:1
- advance_percent: required, numeric, 0-100
- comment: nullable, max 1000
- Проверка: компания ещё не подавала заявку
