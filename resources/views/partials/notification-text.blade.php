@php
    $type = $notification->data['type'] ?? '';
@endphp

@switch($type)
    @case('project_invitation')
        Приглашение в проект: <strong>{{ $notification->data['project_title'] ?? '' }}</strong>
        @if(isset($notification->data['company_name']))
            от {{ $notification->data['company_name'] }}
        @endif
        @break

    @case('tender_invitation')
        Приглашение в тендер: <strong>{{ $notification->data['tender_number'] ?? $notification->data['rfq_number'] ?? '' }}</strong>
        @if(isset($notification->data['company_name']))
            от {{ $notification->data['company_name'] }}
        @endif
        @break

    @case('tender_closed')
        Тендер <strong>{{ $notification->data['tender_number'] ?? $notification->data['rfq_number'] ?? '' }}</strong> завершён
        @if(isset($notification->data['is_winner']) && $notification->data['is_winner'])
            - Вы победили!
        @endif
        @break

    @case('new_comment')
        Новый комментарий в проекте <strong>{{ $notification->data['project_title'] ?? '' }}</strong>
        @if(isset($notification->data['author_name']))
            от {{ $notification->data['author_name'] }}
        @endif
        @break

    @case('auction_trading_started')
        Торги начались: <strong>{{ $notification->data['auction_number'] ?? '' }}</strong>
        @break

    @case('join_request')
        Запрос на присоединение к <strong>{{ $notification->data['company_name'] ?? '' }}</strong>
        от {{ $notification->data['user_name'] ?? '' }}
        @break

    @default
        {{ $notification->data['message'] ?? 'Новое уведомление' }}
@endswitch
