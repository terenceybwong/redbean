<?php

declare(strict_types=1);

namespace AskNicely\RedBean;

use RedBeanPHP\OODBBean;
use RedBeanPHP\R;
use RedBeanPHP\RedException\SQL as SQLException;
use RedBeanPHP\ToolBox;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class WrongSqlUpdateCommand extends Command
{
    private const TABLE_NAME = 'president';
    private const DATA = [
        [
            'name' => 'George Washington',
            'country' => 'U.S.A',
        ],
    ];
    private const UPDATE_DATA = [
        [
            'values' => ['name' => 'John F. Kennedy'],
            'conditions' => ['country' => 'U.S.A'],
        ],
        [
            'values' => ['name' => 'Franklin D. Roosevelt'],
            'conditions' => ['party' => 'Democratic'],
        ],
    ];


    protected static $defaultName = 'wrong-sql-update';

    /** @var ToolBox $dbh */
    private $dbh;

    /** @var SymfonyStyle $io */
    private $io;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->setUp();
    }

    protected function configure(): void
    {
        $this->addOption('freeze', 'f', InputOption::VALUE_NONE, 'Freeze DB schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $freezeDb = $input->getOption('freeze');

        foreach (self::DATA as $row) {
            $table = R::dispense(self::TABLE_NAME);
            foreach ($row as $cell => $value) {
                $table->{$cell} = $value;
            }
            try {
                R::store($table);
            } catch (SQLException $exception) {
                $this->io->error($exception->getMessage());
                exit($exception->getCode());
            }
        }

        $this->io->section('BEFORE UPDATE');
        $this->listTable();

        $updateCount = 0;
        foreach (self::UPDATE_DATA as $set) {
            $values = $set['values'];
            $conditions = $set['conditions'];
            $sql = 'UPDATE ' . self::TABLE_NAME;
            $statements = [];
            $bindValues = [];
            foreach ($values as $field => $value) {
                $statements[] = $field . ' = ?';
                $bindValues[] = $value;
            }
            $sql .= ' SET ' . implode(', ', $statements);
            $clauses = [];
            foreach ($conditions as $field => $condition) {
                $clauses[] = $field . ' = ?';
                $bindValues[] = $condition;
            }
            $sql .= ' WHERE ' . implode(' AND ', $clauses);

            $this->io->section('AFTER UPDATE ' . ++$updateCount);
            $this->io->text($sql . ' (Values: ' . implode(', ', $bindValues) . ')');
            R::freeze($freezeDb ? true : false);
            R::exec($sql, $bindValues);
            $this->listTable();
        }

        R::close();
    }

    private function listTable(): void
    {
        $records= R::find(self::TABLE_NAME);
        $headers = R::inspect(self::TABLE_NAME);
        $rows = [];
        /** @var OODBBean $record */
        foreach ($records as $record) {
            $rows[] = $record->export();
        }
        $this->io->table($headers, $rows);
    }

    private function setUp(): void
    {
        $this->dbConnect();
        R::wipe(self::TABLE_NAME);
    }

    private function dbConnect(): void
    {
        $this->dbh = R::setup('mysql:dbname=redbean', 'redbean', 'redbean');
    }
}
