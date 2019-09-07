<?php 
namespace App\Facades;
use Illuminate\Support\Facades\Facade;

class DailySearchFacade extends Facade
{

    public static function getFacadeAccessor()
    {
        return 'DailySearch';
    }

}