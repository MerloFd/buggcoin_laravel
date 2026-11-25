<?php

namespace App\Console\Commands;

use App\Actions\ConnectBank;
use App\Services\Pluggy\PluggyException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

#[Signature('bank:connect {itemId : Item ID do Meu Pluggy}')]
#[Description('Conecta um banco do Meu Pluggy pelo item ID')]
class ConnectBankCommand extends Command
{
    public function handle(ConnectBank $connectBank): int
    {
        try {
            $connection = $connectBank->handle((string) $this->argument('itemId'));
        } catch (ValidationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (PluggyException $exception) {
            $this->error('Não foi possível consultar o Pluggy: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Banco conectado: {$connection->connector_name}.");

        return self::SUCCESS;
    }
}
