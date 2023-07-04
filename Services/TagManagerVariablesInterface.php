<?php
/**
 * Tag Manager
 * Copyright (c) Webmatch GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace WbmTagManager\Services;

/**
 * Interface TagManagerVariablesInterface
 */
interface TagManagerVariablesInterface
{
    /**
     * @param array $viewVariables
     */
    public function setViewVariables($viewVariables);

    /**
     * @return mixed
     */
    public function getVariables();

    /**
     * @param string $module
     */
    public function render($module);

    /**
     * @param $source
     * @param bool $prettyPrint
     *
     * @return string
     */
    public function prependDataLayer($source, $prettyPrint = false);

    /**
     * @param string $module
     */
    public function setModule($module);
}
