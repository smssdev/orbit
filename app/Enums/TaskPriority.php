<?php

namespace App\Enums;

/**
 * How urgent a card is. A card without one has no priority (null), which sorts below Low.
 */
enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';
}
