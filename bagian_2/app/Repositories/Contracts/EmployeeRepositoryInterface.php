<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{

    public function paginateList(): LengthAwarePaginator;
    public function getByCompany(int $companyId): Collection;
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
