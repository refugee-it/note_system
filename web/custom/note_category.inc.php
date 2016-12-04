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
 * @file $/web/custom/note_category.inc.php
 * @brief Defines categories for notes.
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

/**
 * @attention Don't change the id of an existing category definitions if you
 *     have their indexes already in the database and don't intent to perform
 *     a conversion.
 * @details You can savely change the order and the default priority (the latter
 *     might have no effect on priorities already stored in the database).
 */
function GetNoteCategoryDefinitions()
{
    return array(new NoteCategoryDefinition(0, "unknown", 0),
                 new NoteCategoryDefinition(1, "none", 1),
                 new NoteCategoryDefinition(2, "other", 2),
                 new NoteCategoryDefinition(3, "asylum", 14),
                 new NoteCategoryDefinition(4, "health", 13),
                 new NoteCategoryDefinition(5, "criminal", 12),
                 new NoteCategoryDefinition(6, "appointment", 11),
                 new NoteCategoryDefinition(7, "family", 10),
                 new NoteCategoryDefinition(8, "language_course", 9),
                 new NoteCategoryDefinition(9, "work", 8),
                 new NoteCategoryDefinition(10, "finances", 7),
                 new NoteCategoryDefinition(11, "flat_renting", 6),
                 new NoteCategoryDefinition(12, "errands", 5),
                 new NoteCategoryDefinition(13, "information_request", 4),
                 new NoteCategoryDefinition(14, "freetime_activities", 3));
}

function GetNoteCategoryDisplayNameById($id)
{
    require_once(dirname(__FILE__)."/../libraries/languagelib.inc.php");
    require_once(getLanguageFile("note_category", dirname(__FILE__)));

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
    require_once(dirname(__FILE__)."/../libraries/languagelib.inc.php");
    require_once(getLanguageFile("note_category", dirname(__FILE__)));

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
    require_once(dirname(__FILE__)."/../libraries/languagelib.inc.php");
    require_once(getLanguageFile("note_category", dirname(__FILE__)));

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
    require_once(dirname(__FILE__)."/../libraries/languagelib.inc.php");
    require_once(getLanguageFile("note_category", dirname(__FILE__)));

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
