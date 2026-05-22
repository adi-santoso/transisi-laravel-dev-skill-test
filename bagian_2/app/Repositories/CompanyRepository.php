<?php

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CompanyRepository implements CompanyRepositoryInterface {

    protected Company $model;

    public function __construct(Company $model)
    {
        $this->model = $model;
    }

    public function paginateList(): LengthAwarePaginator
    {
        return $this->model
            ->query()
            ->withCount('employees')
            ->paginate(5);
    }

    public function paginateForSelect2(?string $search, int $page, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->query()
            ->select(['id', 'name'])
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%');
            })
            ->orderBy('name')
            ->paginate(perPage: $perPage, page: $page);
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
        $company = $this->model->find($id);
        return $company->update($data);
    }

    public function delete($id): ?bool
    {
        $company = $this->model->find($id);
        return $company->delete();
    }
}
