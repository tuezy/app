<?php

namespace Tuezy;

use Illuminate\Support\Facades\Facade;
use Tuezy\Tool;

/**
 * @see Tool
 */
class ToolFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'tool';
    }
}
