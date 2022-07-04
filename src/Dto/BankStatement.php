<?php

namespace App\Dto;

use DateTime;

class BankStatement
{
    protected float $openingBalance = 0;
    protected array $credits = [];
    protected array $recurringExpenses = [];
    protected array $adhocExpenses = [];
    protected float $totalIncome;
    protected ?DateTime $periodBegin;
    protected ?DateTime $periodEnd;
    protected int $totalMonths = 0;

    public function setOpeningBalance(float $openingBalance): void
    {
        $this->openingBalance = $openingBalance;
    }

    public function getOpeningBalance(): float
    {
        return $this->openingBalance;
    }

    public function addCreditTransaction(float $cost): void
    {
        $this->credits[] = $cost;
    }

    public function getCreditTransactions(): array
    {
        return $this->credits;
    }

    public function addRecurringExpense(float $cost): void
    {
        $this->recurringExpenses[] = $cost;
    }

    public function getRecurringExpenses(): array
    {
        return $this->recurringExpenses;
    }

    public function setTotalMonths(int $months): void
    {
        $this->totalMonths = $months;
    }

    public function getTotalMonths(): int
    {
        return $this->totalMonths;
    }

    public function calculateTotalIncome(): float
    {
        return array_sum($this->credits);
    }

    public function calculateTotalRecurringExpenses(): float
    {
        return array_sum($this->recurringExpenses);
    }

    public function calculateAverageMonthlyIncome(): float
    {
        return $this->calculateTotalIncome() / $this->totalMonths;
    }

    public function calculateAverageMonthlyRecurringExpenses(): float
    {
        return $this->calculateTotalRecurringExpenses() / $this->totalMonths;
    }
}
