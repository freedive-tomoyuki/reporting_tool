<?php 
namespace App\Facades;
use Illuminate\Support\Facades\Facade;

class MonthlySearchFacade extends Facade
{

    public static function getFacadeAccessor()
    {
        return 'MonthlySearch';
    }

}