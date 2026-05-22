<?php

namespace App\Services;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeService
{

    protected EmployeeRepositoryInterface $employeeRepository;

    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function paginateList(){
        return $this->employeeRepository->paginateList();
    }

    public function store(array $data){
        try{
            DB::beginTransaction();

            $company = $this->employeeRepository->create($data);

            DB::commit();

            return $company->fresh();
        } catch (QueryException $e){
            DB::rollBack();

            Log::error($e->getTraceAsString());

            throw $e;
        }
    }

    public function find($id)
    {
        return $this->employeeRepository->find($id);
    }

    public function update(string $id, array $data)
    {
        try {
            $employee = $this->employeeRepository->find($id);
            if (!$employee) return false;

            DB::beginTransaction();

            $this->employeeRepository->update($id, $data);

            DB::commit();

            return $employee->fresh();
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    public function delete($id)
    {
        $employee = $this->employeeRepository->find($id);
        if (!$employee) return false;


        $result = DB::transaction(function () use ($id) {
            return $this->employeeRepository->delete($id);
        });

        return $result;
    }
}
