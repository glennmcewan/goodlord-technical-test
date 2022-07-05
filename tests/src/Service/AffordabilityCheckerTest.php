<?php

namespace App\Tests\Service;

use App\Dto\BankStatement;
use App\Service\AffordabilityChecker;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * NOTE: vfs-stream might make this cleaner/nicer...
 */
class AffordabilityCheckerTest extends TestCase
{
    public function testBankStatementIsGeneratedWithEmptyFile(): void
    {
        $service = new AffordabilityChecker;
        $statement = $service->buildBankStatementModel(__DIR__ . '/../../data/bank_statement_empty.csv');

        $this->assertInstanceOf(BankStatement::class, $statement);
    }

    public function testBankStatementIsGeneratedAsExpected(): void
    {
        $service = new AffordabilityChecker;
        $statement = $service->buildBankStatementModel(__DIR__ . '/../../data/bank_statement.csv');

        // Assert that the statement picks up the correct opening balance
        $this->assertEquals(1183.0, $statement->getOpeningBalance());

        // Assert that the statement calculates 2 months as expected
        $this->assertEquals(2, $statement->getTotalMonths());

        // Assert that the statement collects 10 credits
        $this->assertCount(10, $statement->getCreditTransactions());

        // Assert that the statement collects 14 recurring expenses
        $this->assertCount(14, $statement->getRecurringExpenses());

        $this->assertInstanceOf(BankStatement::class, $statement);
    }

    public function testBankStatementGenerationFailsIfBalanceCannotBeExtrapolated(): void
    {
        $this->expectException(Exception::class);
        $this->expectErrorMessage('Unable to parse opening balance from bank statement');
        
        $service = new AffordabilityChecker;
        $service->buildBankStatementModel(__DIR__ . '/../../data/bank_statement_invalid_balance.csv');
    }

    public function testBankStatementGenerationFailsIfTransactionHasEmptyValue(): void
    {
        $this->expectException(Exception::class);
        $this->expectErrorMessage('Outgoing transaction has an empty value');
        
        $service = new AffordabilityChecker;
        $service->buildBankStatementModel(__DIR__ . '/../../data/bank_statement_invalid_transaction_value.csv');
    }

    // TODO: handle case where the CSV files arguments may be swapped by accident?

    // public function testCalculatingAffordablePropertiesReturnsEmptyArrayForEmptyFile(): void
    public function testCalculatingAffordablePropertiesReturnsExpectedArray(): void
    {
        $service = new AffordabilityChecker;
        $properties = $service->calculateAffordableProperties(__DIR__ . '/../../data/bank_statement.csv', __DIR__ . '/../../data/properties.csv');

        $this->assertCount(9, $properties);

        // This should be the most expensive available property
        $this->assertTrue(in_array(
            [
                'id' => '9',
                'address' => '78  Terrick Rd, EX39 6AX',
                'price' => '710',
                'remainder' => 85.63,
            ],
            $properties
        ));
    }

    public function testCalculatingAffordablePropertiesUsesCorrectThreshold(): void
    {
        $service = new AffordabilityChecker;
        $properties = $service->calculateAffordableProperties(__DIR__ . '/../../data/bank_statement_simple.csv', __DIR__ . '/../../data/properties.csv');

        // Average income 1000, average expenses 100, 900 maximum budget
        // Most expensive rental property will be 710, requiring budget of 887.5 (710 * 1.25), remainder should be 12.5 (900 - 887.5)

        $this->assertCount(9, $properties);

        // This should be the most expensive available property
        $this->assertTrue(in_array(
            [
                'id' => '9',
                'address' => '78  Terrick Rd, EX39 6AX',
                'price' => '710',
                'remainder' => 12.5,
            ],
            $properties
        ));
    }
}
