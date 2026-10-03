<?php

declare(strict_types=1);

namespace App\Application\Model;

use App\Domain\Exception\BusinessRuleViolation;
use DateTimeImmutable;

final class DateRange
{
    private DateTimeImmutable $from;
    private DateTimeImmutable $to;

    public function __construct(string|DateTimeImmutable $from, string|DateTimeImmutable $to)
    {
        $startDate = is_string($from) ? new DateTimeImmutable($from) : $from;
        $endDate = is_string($to) ? new DateTimeImmutable($to) : $to;

        if ($endDate < $startDate) {
            throw new class("La fecha final no puede ser anterior a la fecha inicial") extends BusinessRuleViolation {};
        }

        $this->from = $startDate;
        $this->to = $endDate;
    }

    public function getFrom(): DateTimeImmutable
    {
        return $this->from;
    }

    public function getTo(): DateTimeImmutable
    {
        return $this->to;
    }
}
