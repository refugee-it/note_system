<?php
/* Copyright (C) 2016  Stephan Kreutzer
 *
 * This file is part of note system for refugee-it.de.
 *
 * note system for refugee-it.de is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3 or any later version,
 * as published by the Free Software Foundation.
 *
 * note system for refugee-it.de is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License 3 for more details.
 *
 * You should have received a copy of the GNU Affero General Public License 3
 * along with note system for refugee-it.de. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * @file $/web/libraries/note_category.inc.php
 * @brief Handling for note categories.
 * @author Stephan Kreutzer
 * @since 2016-12-03
 */



class NoteCategoryDefinition
{
    public function __construct($id, $name, $defaultPriority)
    {
        $this->id = $id;
        $this->name = $name;
        $this->default_priority = $defaultPriority;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDefaultPriority()
    {
        return $this->default_priority;
    }

    protected $id;
    protected $name;
    protected $default_priority;
}

require_once(dirname(__FILE__)."/../custom/note_category.inc.php");

function GetNoteCategoryDisplayNameById($id)
{
    require_once(dirname(__FILE__)."/languagelib.inc.php");
    require_once(getLanguageFile("note_category", dirname(__FILE__)."/../custom"));

    $categories = GetNoteCategoryDefinitions();

    foreach ($categories as $category)
    {
        if ((int)$category->getId() === (int)$id)
        {
            return constant("LANG_CUSTOM_NOTECATEGORY_NAME_".strtoupper($category->getName()));
        }
    }

    return null;
}

function GetNoteCategoryDisplayDescriptionById($id)
{
    require_once(dirname(__FILE__)."/languagelib.inc.php");
    require_once(getLanguageFile("note_category", dirname(__FILE__)."/../custom"));

    $categories = GetNoteCategoryDefinitions();

    foreach ($categories as $category)
    {
        if ((int)$category->getId() === (int)$id)
        {
            return constant("LANG_CUSTOM_NOTECATEGORY_DESCRIPTION_".strtoupper($category->getName()));
        }
    }

    return null;
}

function GetNoteCategoryDisplayNameByName($name)
{
    require_once(dirname(__FILE__)."/languagelib.inc.php");
    require_once(getLanguageFile("note_category", dirname(__FILE__)."/../custom"));

    $categories = GetNoteCategoryDefinitions();

    foreach ($categories as $category)
    {
        if ($category->getName() == $name)
        {
            return constant("LANG_CUSTOM_NOTECATEGORY_NAME_".strtoupper($name));
        }
    }

    return null;
}

function GetNoteCategoryDisplayDescriptionByName($name)
{
    require_once(dirname(__FILE__)."/languagelib.inc.php");
    require_once(getLanguageFile("note_category", dirname(__FILE__)."/../custom"));

    $categories = GetNoteCategoryDefinitions();

    foreach ($categories as $category)
    {
        if ($category->getName() == $name)
        {
            return constant("LANG_CUSTOM_NOTECATEGORY_DESCRIPTION_".strtoupper($name));
        }
    }

    return null;
}
