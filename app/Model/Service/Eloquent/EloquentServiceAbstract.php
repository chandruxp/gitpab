<?php

namespace App\Model\Service\Eloquent;

use App\Model\Repository\RepositoryAbstractEloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

abstract class EloquentServiceAbstract
{
    const DEFAULT_ORDER_COLUMN = 'id';
    const DEFAULT_ORDER_DIRECTION = 'desc';
    const DEFAULT_LIMIT = 20;

    protected $useSimplePagination = false;

    /**
     * @var RepositoryAbstractEloquent
     */
    protected $repository;

    public function __construct(RepositoryAbstractEloquent $repository)
    {
        $this->repository = $repository;
    }

    public function getCompleteList(array $parameters): \Traversable
    {
        $query = $this->repository->getListQuery($parameters);
        $this->setQueryOrder($query, $parameters);
        return $query->cursor();
    }

    public function getList(array $parameters)
    {
        $query = $this->repository->getListQuery($parameters);
        $this->setQueryOrder($query, $parameters);
        return $this->paginateListQuery($query, Arr::get($parameters, 'limit', self::DEFAULT_LIMIT));
    }

    protected function setQueryOrder($query, array $params)
    {
        $order = Arr::get($params, 'order', self::DEFAULT_ORDER_COLUMN);
        $orderDirection = Arr::get($params, 'orderDirection', self::DEFAULT_ORDER_DIRECTION);

        if (!empty($order) && !empty($orderDirection))
        {
            $query->orderBy($order, $orderDirection);
        }

        return $query;
    }

    protected function paginateListQuery(Builder $query, $limit)
    {
        $paginateMethod = ($this->useSimplePagination) ? 'simplePaginate' : 'paginate';
        return $query->$paginateMethod($limit);
    }

    /**
     * @param Collection $list
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function storeList(Collection $list)
    {
        foreach ($list as $item) {
            $this->store((array)$item);
        }
    }

    /**
     * @param array $attributes
     * @return mixed
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function store(array $attributes)
    {
        try {
            $result = $this->repository->create($attributes);
        }
        catch (QueryException  $e) {
            // Record exists
            if ($e->getCode() == 23505) {
                $result = $this->repository->update($attributes, $attributes['id']);
            }
            else {
                throw $e;
            }
        }
        return $result;
    }
}