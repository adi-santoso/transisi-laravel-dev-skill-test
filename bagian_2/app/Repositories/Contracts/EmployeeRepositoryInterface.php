<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface EmployeeRepositoryInterface
{

    public function paginateList(): LengthAwarePaginator;
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
