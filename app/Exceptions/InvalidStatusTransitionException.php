<?php

namespace App\Exceptions;

use App\Enums\ReportStatus;
use DomainException;

class InvalidStatusTransitionException extends DomainException
{
    public static function between(?string $from, ReportStatus $to): self
    {
        $fromLabel = ReportStatus::tryFrom((string) $from)?->label() ?? ($from ?: 'tidak diketahui');

        return new self("Transisi status dari \"{$fromLabel}\" ke \"{$to->label()}\" tidak diizinkan.");
    }
}
