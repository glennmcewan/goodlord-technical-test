<?php
namespace App\Command;

use App\Service\AffordabilityChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AffordabilityCheckCommand extends Command
{
    protected AffordabilityChecker $affordabilityChecker;

    public function __construct(AffordabilityChecker $affordabilityChecker)
    {
        $this->affordabilityChecker = $affordabilityChecker;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('affordability-check')
            ->setDescription('Checks what properties an applicant can afford based on input bank statement / property list.')
            ->addArgument('bank-statement', InputArgument::REQUIRED, 'Bank statement to evaluate.')
            ->addArgument('properties', InputArgument::REQUIRED, 'List of properties.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_readable($input->getArgument('bank-statement'))) {
            $output->writeln('<error>Unable to read supplied bank statement CSV.</error>');

            return Command::FAILURE;
        } elseif (!is_readable($input->getArgument('properties'))) {
            $output->writeln('<error>Unable to read supplied properties CSV.</error>');

            return Command::FAILURE;
        }

        $affordableProperties = $this->affordabilityChecker->calculateAffordableProperties($input->getArgument('bank-statement'), $input->getArgument('properties'));

        $table = new Table($output);
        $table->setHeaders(['ID', 'Address', 'Price (pcm)']);
        foreach ($affordableProperties as $row) {
            $table->addRow([
                $row['id'],
                $row['address'],
                '£'.$row['price'],
            ]);
        }

        $output->writeln('<comment>Based on the information you provided, below is a list of affordable properties:</comment>');
        $table->render();

        return Command::SUCCESS;
    }
}
