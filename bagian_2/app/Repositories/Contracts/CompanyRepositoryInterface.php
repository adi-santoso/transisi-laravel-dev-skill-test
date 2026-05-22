<?php

namespace App\Repositories\Contracts;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CompanyRepositoryInterface
{

    public function paginateList(): LengthAwarePaginator;
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
