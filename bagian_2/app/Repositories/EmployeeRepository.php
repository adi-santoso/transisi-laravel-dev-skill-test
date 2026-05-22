<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EmployeeRepository implements EmployeeRepositoryInterface {

    protected Employee $model;

    public function __construct(Employee $model)
    {
        $this->model = $model;
    }

    public function paginateList(): LengthAwarePaginator
    {
        return $this->model->query()
            ->with('company:id,name')
            ->paginate(5);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model->query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $employee = $this->model->find($id);
        return $employee->update($data);
    }

    public function delete($id): ?bool
    {
        $employee = $this->model->find($id);
        return $employee->delete();
    }
}
