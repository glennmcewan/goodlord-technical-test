<?php

namespace App\Service;

use App\Dto\BankStatement;
use App\Dto\PaymentTypes;
use DateTime;
use Exception;
use NumberFormatter;

class AffordabilityChecker
{
    protected NumberFormatter $numberFormatter;
    protected string $currencySymbol = 'GBP';

    public function __construct()
    {
        $this->numberFormatter = new NumberFormatter('en-GB', NumberFormatter::DECIMAL);
    }

    /**
     * TODO: move away from UploadedFile in the service; makes CLI & testing difficult
     *
     * @param string $bankStatementPath
     * @param string $propertiesListPath
     *
     * @return array
     */
    public function calculateAffordableProperties(string $bankStatementPath, string $propertiesListPath): array
    {
        // Calculate the customer's salary
        $bankStatement = $this->buildBankStatementModel($bankStatementPath);

        // Run this against the $propertiesList CSV
        return $this->getPropertiesWithinBudget($propertiesListPath, $bankStatement);
    }

    /**
     * Return an array of the available properties, returns an associative array with
     * each element having [id, address, cost] fields.
     *
     * @param string $propertiesListPath
     * @param BankStatement $bankStatement
     *
     * @return array
     */
    public function getPropertiesWithinBudget(string $propertiesListPath, BankStatement $bankStatement): array
    {
        $fileHandle = fopen($propertiesListPath, 'r');

        if (!$fileHandle) {
            throw new Exception('Unable to open Properties List CSV');
        }

        $affordableProperties = [];

        // Read CSV until we find the opening statement balance
        while (($data = fgetcsv($fileHandle)) !== false) {
            if ('id' === strtolower($data[0])) {
                continue;
            }

            $affordabilityThreshold = $data[2] * 1.25;
            $discretionaryIncome = $bankStatement->calculateAverageMonthlyIncome() - $bankStatement->calculateAverageMonthlyRecurringExpenses();

            if (($discretionaryIncome - $affordabilityThreshold) > 0) {
                $affordableProperties[] = [
                    'id' => $data[0],
                    'address' => $data[1],
                    'price' => $data[2],
                    'remainder' => $discretionaryIncome - $affordabilityThreshold,
                ];
            }
        }

        fclose($fileHandle);

        return $affordableProperties;
    }

    public function buildBankStatementModel(string $bankStatementPath): BankStatement
    {
        $fileHandle = fopen($bankStatementPath, 'r');

        if (!$fileHandle) {
            throw new Exception('Unable to open Bank Statement CSV');
        }

        $periodBegin = null;
        $periodEnd = null;

        $bankStatement = new BankStatement;

        // Read CSV until we find the opening statement balance
        while (($data = fgetcsv($fileHandle)) !== false) {
            if ('statement opening balance' === strtolower($data[2])) {
                $openingBalance = $this->numberFormatter->parseCurrency($data[5], $this->currencySymbol);
                
                if (false === $openingBalance) {
                    throw new Exception('Unable to parse opening balance from bank statement');
                }

                $bankStatement->setOpeningBalance($openingBalance);

                break;
            }
        }

        // Continue to read the CSV to handle the different transaction payment types
        while (($data = fgetcsv($fileHandle)) !== false) {
            // Initialise counters, presuming they are unset on the first loop
            if (!$periodBegin) {
                $periodBegin = new DateTime($data[0]);
                $periodEnd = new DateTime($data[0]);
                $bankStatement->setTotalMonths(1);
            }

            // Hacky but does the trick for this execerise...need to factor in "partial" months
            if (($periodEnd->format('m') !== (new DateTime($data[0]))->format('m'))) {
                $bankStatement->setTotalMonths(1 + $bankStatement->getTotalMonths());
            }

            $periodEnd = new DateTime($data[0]);

            // Collect all credits into an array
            $normalisedPaymentType = strtolower($data[1]);

            switch ($normalisedPaymentType) {
                case PaymentTypes::BANK_CREDIT:
                    if (!$data[4]) {
                        throw new Exception('Credit transaction has an empty value');
                    }
                    
                    $bankStatement->addCreditTransaction($this->numberFormatter->parseCurrency($data[4], $this->currencySymbol));

                    break;
                case PaymentTypes::DIRECT_DEBIT:
                    if (!$data[3]) {
                        throw new Exception('Outgoing transaction has an empty value');
                    }

                    $bankStatement->addRecurringExpense($this->numberFormatter->parseCurrency($data[3], $this->currencySymbol));
                    
                    break;
                case PaymentTypes::STANDING_ORDER:
                    // Standing order is likely to be either for rent or savings for this exercise, omit these transactions from affordability score...
                    break;
            }
        }

        fclose($fileHandle);

        return $bankStatement;
    }
}
