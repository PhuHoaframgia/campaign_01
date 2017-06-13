<?php

namespace App\Repositories\Contracts;

interface EventInterface extends RepositoryInterface
{
    public function delete($eventIds);

    public function update($event, $inputs);
}
