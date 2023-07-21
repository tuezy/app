<?php

namespace Tuezy;

use Illuminate\Support\Facades\Facade;
use Tuezy\Tools;

/**
 * @see Tools
 */
class ToolsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'tools';
    }
}
