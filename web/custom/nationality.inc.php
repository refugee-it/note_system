<?php
/* Copyright (C) 2016-2017  Stephan Kreutzer
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
 * @file $/web/custom/nationality.inc.php
 * @brief Define a list of nationalities that should be supported by the system.
 * @author Stephan Kreutzer
 * @since 2016-11-27
 */



/**
 * @attention Don't change the order of existing nationality definitions, if you
 *     have their indexes already in the database and don't intent to perform
 *     a conversion.
 */
function GetNationalityDefinitions()
{
    return array(0  => "unknown",
                 1  => "syrian",
                 2  => "iraqi",
                 3  => "afghan",
                 4  => "pakistani",
                 5  => "gambian",
                 6  => "kosovar",
                 7  => "algerian",
                 8  => "somali");
}

function GetNationalityDisplayName($nationality)
{
    require_once(dirname(__FILE__)."/../libraries/languagelib.inc.php");
    require_once(getLanguageFile("nationality", dirname(__FILE__)));

    return constant("LANG_CUSTOM_NATIONALITY_".strtoupper($nationality));
}

function GetNationalityDisplayNameById($id)
{
    $nationalities = GetNationalityDefinitions();

    if ($id < 0 ||
        $id >= count($nationalities))
    {
        $id = 0;
    }

    return GetNationalityDisplayName($nationalities[$id]);
}

?>
